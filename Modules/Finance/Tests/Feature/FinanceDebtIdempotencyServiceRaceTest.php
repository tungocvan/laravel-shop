<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Services\FinanceDebtIdempotencyRegistry;
use Modules\Finance\Services\FinanceDebtTestDatabaseGuard;
use PDO;
use Tests\TestCase;

/**
 * CP28: real InnoDB contention against the actual reservation service.
 * An uncommitted reservation blocks a competing insert; rollback releases it.
 * This is NOT a settlement writer test or proof of end-to-end race safety.
 */
final class FinanceDebtIdempotencyServiceRaceTest extends TestCase
{
    public function test_uncommitted_key_blocks_registry_then_rollback_allows_single_reservation(): void
    {
        $dsn = (string) getenv('FINANCE_CP22_TEST_DSN');
        if ($dsn === '') {
            $this->markTestSkipped('CP28 NOT RUN: isolated MySQL opt-in DSN required.');
        }
        $authorization = (string) getenv('FINANCE_CP22_ALLOW_ISOLATED_DB');
        $guard = new FinanceDebtTestDatabaseGuard;
        $guard->assertAllowed($dsn, $authorization);
        if (! str_starts_with($dsn, 'mysql:')) {
            $this->markTestSkipped('CP28 currently requires MySQL/InnoDB.');
        }

        $user = (string) getenv('FINANCE_CP22_TEST_USER');
        $password = (string) getenv('FINANCE_CP22_TEST_PASSWORD');
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3,
        ]);
        $guard->assertConnectedDatabase($dsn, $authorization, (string) $pdo->query('SELECT DATABASE()')->fetchColumn());

        $tables = ['finance_debt_idempotency_keys', 'finance_debt_obligations'];
        foreach ($tables as $table) {
            $query = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
            $query->execute([$table]);
            if ((int) $query->fetchColumn() !== 0) {
                throw new \RuntimeException('CP28 refuses to touch an existing Finance table: '.$table);
            }
        }

        $previousDefault = DB::getDefaultConnection();
        $name = 'finance_cp28_isolated';
        $previousConfig = config('database.connections.'.$name);
        $created = [];
        try {
            $settings = [];
            foreach (explode(';', substr($dsn, strlen('mysql:'))) as $part) {
                [$key, $value] = explode('=', $part, 2);
                $settings[$key] = $value;
            }
            config(['database.connections.'.$name => [
                'driver' => 'mysql',
                'host' => $settings['host'],
                'port' => $settings['port'] ?? '3306',
                'database' => $settings['dbname'],
                'username' => $user,
                'password' => $password,
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
            ]]);
            DB::purge($name);
            DB::setDefaultConnection($name);
            $laravel = DB::connection($name);
            $guard->assertConnectedDatabase(
                $dsn, $authorization, (string) $laravel->selectOne('SELECT DATABASE() AS db')->db
            );

            $pdo->exec('CREATE TABLE finance_debt_obligations (id BIGINT UNSIGNED PRIMARY KEY) ENGINE=InnoDB');
            $created[] = 'finance_debt_obligations';
            $pdo->exec('CREATE TABLE finance_debt_idempotency_keys (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                obligation_id BIGINT UNSIGNED NOT NULL,
                operation VARCHAR(20) NOT NULL,
                idempotency_key VARCHAR(128) NOT NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                CONSTRAINT cp28_debt_fk FOREIGN KEY (obligation_id) REFERENCES finance_debt_obligations(id),
                UNIQUE KEY cp28_scope_unique (obligation_id, operation, idempotency_key)
            ) ENGINE=InnoDB');
            $created[] = 'finance_debt_idempotency_keys';
            $pdo->exec('INSERT INTO finance_debt_obligations (id) VALUES (1)');
            $laravel->statement('SET SESSION innodb_lock_wait_timeout = 1');

            // A holds a unique key without committing. B calls the real service,
            // whose pre-check cannot see A's row and whose INSERT must wait.
            $pdo->beginTransaction();
            $pdo->exec("INSERT INTO finance_debt_idempotency_keys
                (obligation_id, operation, idempotency_key) VALUES (1, 'post', 'cp28-race-key')");
            try {
                try {
                    (new FinanceDebtIdempotencyRegistry)->reserve(1, 'post', 'cp28-race-key');
                    self::fail('Concurrent reservation bypassed the uncommitted unique key.');
                } catch (QueryException $exception) {
                    self::assertSame(1205, (int) ($exception->errorInfo[1] ?? 0),
                        'Expected genuine InnoDB lock timeout, not a different database error.');
                }
                self::assertSame(0, (int) $laravel->table('finance_debt_idempotency_keys')->count(),
                    'Uncommitted reservation must not be visible to the competing connection.');
            } finally {
                $pdo->rollBack();
            }

            // Rollback releases the unique scope: one real service reservation wins.
            $reservationId = (new FinanceDebtIdempotencyRegistry)->reserve(1, 'post', 'cp28-race-key');
            self::assertGreaterThan(0, $reservationId);
            self::assertSame(1, (int) $pdo->query('SELECT COUNT(*) FROM finance_debt_idempotency_keys')->fetchColumn());

            // Repeating the same scope must fail closed without additional rows.
            try {
                (new FinanceDebtIdempotencyRegistry)->reserve(1, 'post', 'cp28-race-key');
                self::fail('Duplicate reservation unexpectedly succeeded.');
            } catch (ValidationException $exception) {
                self::assertNotEmpty($exception->errors()['idempotency'] ?? []);
            }
            self::assertSame(1, (int) $pdo->query('SELECT COUNT(*) FROM finance_debt_idempotency_keys')->fetchColumn());

            // Registry alone is not settlement; no voucher, mapping or ledger tables
            // are created by this test and the settlement gate remains unchanged.
        } finally {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            DB::setDefaultConnection($previousDefault);
            DB::disconnect($name);
            DB::purge($name);
            config(['database.connections.'.$name => $previousConfig]);
            foreach (array_reverse($created) as $table) {
                $pdo->exec('DROP TABLE IF EXISTS '.$table);
            }
        }
    }
}
