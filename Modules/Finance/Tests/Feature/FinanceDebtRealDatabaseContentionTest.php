<?php

namespace Modules\Finance\Tests\Feature;

use PDO;
use PDOException;
use Tests\TestCase;
use Modules\Finance\Services\FinanceDebtTestDatabaseGuard;
use Modules\Finance\Services\FinanceDebtConcurrencyEvidence;

/**
 * CP22 opt-in two-connection database contention smoke test.
 * Uses isolated scratch tables, NEVER the application Finance tables.
 *
 * Run with FINANCE_CP22_TEST_DSN=mysql:host=127.0.0.1;dbname=finance_test
 * (or pgsql:host=127.0.0.1;dbname=finance_test), plus USER and PASSWORD.
 */
final class FinanceDebtRealDatabaseContentionTest extends TestCase
{
    private function connections(): array
    {
        $dsn = (string) getenv('FINANCE_CP22_TEST_DSN');
        if ($dsn === '') {
            $this->markTestSkipped('CP22 real DB test NOT RUN: set FINANCE_CP22_TEST_DSN for isolated *_test database.');
        }
        (new FinanceDebtTestDatabaseGuard)->assertAllowed(
            $dsn,
            (string) getenv('FINANCE_CP22_ALLOW_ISOLATED_DB')
        );
        $driver = str_starts_with($dsn, 'mysql:') ? 'mysql' : 'pgsql';

        $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3];
        $user = (string) getenv('FINANCE_CP22_TEST_USER');
        $password = (string) getenv('FINANCE_CP22_TEST_PASSWORD');
        $first = new PDO($dsn, $user, $password, $options);
        $second = new PDO($dsn, $user, $password, $options);
        $this->assertNotSame($first, $second);
        $this->assertSame($driver, $first->getAttribute(PDO::ATTR_DRIVER_NAME));
        // CP24: confirm the database actually selected by both connections.
        $identitySql = $driver === 'mysql' ? 'SELECT DATABASE()' : 'SELECT current_database()';
        $guard = new FinanceDebtTestDatabaseGuard;
        foreach ([$first, $second] as $connection) {
            $guard->assertConnectedDatabase(
                $dsn,
                (string) getenv('FINANCE_CP22_ALLOW_ISOLATED_DB'),
                (string) $connection->query($identitySql)->fetchColumn()
            );
        }
        if ($driver === 'mysql') {
            foreach ([$first, $second] as $connection) {
                $version = (string) $connection->query('SELECT VERSION()')->fetchColumn();
                $isMaria = stripos($version, 'mariadb') !== false;
                if (! preg_match('/^(\\d+)\\.(\\d+)/', $version, $parts)
                    || ($isMaria && ((int) $parts[1] < 10 || ((int) $parts[1] === 10 && (int) $parts[2] < 3)))
                    || (! $isMaria && (int) $parts[1] < 8)) {
                    throw new \RuntimeException('CP26 requires MySQL 8+ or MariaDB 10.3+ for NOWAIT locking.');
                }
            }
        }
        return [$first, $second, $driver];
    }

    public function test_two_connections_lock_uniqueness_reversal_and_rollback(): void
    {
        [$a, $b, $driver] = $this->connections();
        $table = 'cp22_'.bin2hex(random_bytes(6));
        // Generated identifier is strictly hexadecimal with a fixed prefix.
        $a->exec("CREATE TABLE {$table} (id INTEGER PRIMARY KEY, obligation_id INTEGER NOT NULL, operation VARCHAR(20) NOT NULL, idempotency_key VARCHAR(128) NOT NULL, reverses_mapping_id INTEGER NULL, UNIQUE (obligation_id, operation, idempotency_key), UNIQUE (reverses_mapping_id))".($driver === 'mysql' ? ' ENGINE=InnoDB' : ''));

        try {
            $a->exec("INSERT INTO {$table} (id, obligation_id, operation, idempotency_key) VALUES (1, 1, 'post', 'original')");
            $a->beginTransaction();
            $a->query("SELECT id FROM {$table} WHERE id = 1 FOR UPDATE")->fetchAll();
            $b->beginTransaction();
            try {
                if ($driver === 'pgsql') {
                    $b->exec("SET LOCAL lock_timeout = '500ms'");
                } else {
                    $b->exec('SET innodb_lock_wait_timeout = 1');
                }
                $blocked = false;
                try {
                    $b->query("SELECT id FROM {$table} WHERE id = 1 FOR UPDATE NOWAIT")->fetchAll();
                } catch (PDOException $exception) {
                    // A syntax error or missing privilege must NOT count as lock contention.
                    $sqlState = (string) $exception->getCode();
                    $vendorCode = (int) ($exception->errorInfo[1] ?? 0);
                    $blocked = $driver === 'pgsql'
                        ? in_array($sqlState, ['55P03', '57014'], true)
                        : ($sqlState === 'HY000' && in_array($vendorCode, [3572, 1205], true));
                    if (! $blocked) {
                        throw $exception;
                    }
                }
                self::assertTrue($blocked, 'Second connection unexpectedly acquired locked row.');
            } finally {
                $b->rollBack();
                $a->rollBack();
            }

            // Real unique indexes, exercised from two independent connections.
            $b->exec("INSERT INTO {$table} (id, obligation_id, operation, idempotency_key) VALUES (2, 1, 'post', 'reserved')");
            $duplicateRejected = false;
            try {
                $a->exec("INSERT INTO {$table} (id, obligation_id, operation, idempotency_key) VALUES (3, 1, 'post', 'reserved')");
            } catch (PDOException $exception) {
                $duplicateRejected = true;
            }
            self::assertTrue($duplicateRejected, 'Duplicate idempotency scope was accepted.');

            $a->exec("INSERT INTO {$table} (id, obligation_id, operation, idempotency_key, reverses_mapping_id) VALUES (4, 1, 'reverse', 'reverse-a', 1)");
            $doubleReverseRejected = false;
            try {
                $b->exec("INSERT INTO {$table} (id, obligation_id, operation, idempotency_key, reverses_mapping_id) VALUES (5, 1, 'reverse', 'reverse-b', 1)");
            } catch (PDOException $exception) {
                $doubleReverseRejected = true;
            }
            self::assertTrue($doubleReverseRejected, 'Duplicate reversal origin was accepted.');

            $a->beginTransaction();
            $a->exec("INSERT INTO {$table} (id, obligation_id, operation, idempotency_key) VALUES (6, 1, 'post', 'rollback')");
            $a->rollBack();
            self::assertSame(0, (int) $b->query("SELECT COUNT(*) FROM {$table} WHERE id = 6")->fetchColumn());
            // CP25: emit only a sanitized, explicitly unverified evidence summary.
            $report = (new FinanceDebtConcurrencyEvidence)->inspect([
                'driver' => $driver,
                'database' => (string) $a->query($driver === 'mysql'
                    ? 'SELECT DATABASE()' : 'SELECT current_database()')->fetchColumn(),
                'independent_connections' => true,
                'checks' => [
                    'row_lock' => $blocked,
                    'idempotency_unique' => $duplicateRejected,
                    'reversal_unique' => $doubleReverseRejected,
                    'rollback' => true,
                ],
            ]);
            self::assertSame('REPORTED_PASS_UNVERIFIED', $report['status']);
            self::assertFalse($report['settlement_eligible']);
            fwrite(STDOUT, PHP_EOL.'CP25 evidence: '.json_encode($report, JSON_THROW_ON_ERROR).PHP_EOL);
        } finally {
            if ($a->inTransaction()) {
                $a->rollBack();
            }
            if ($b->inTransaction()) {
                $b->rollBack();
            }
            $a->exec("DROP TABLE IF EXISTS {$table}");
        }
    }
}
