<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Services\FinanceDebtAtomicSettlementGate;
use Modules\Finance\Services\FinanceDebtTestDatabaseGuard;
use PDO;
use Tests\TestCase;

/**
 * CP27 opt-in, real MySQL service-boundary lock contention.
 * Proves the existing fail-closed gate cannot bypass an independently held
 * debt row lock. Does NOT prove settlement writer concurrency or deadlock freedom.
 */
final class FinanceDebtServiceContentionTest extends TestCase
{
    public function test_atomic_gate_waits_for_external_debt_lock_and_never_writes(): void
    {
        $dsn = (string) getenv('FINANCE_CP22_TEST_DSN');
        if ($dsn === '') {
            $this->markTestSkipped('CP27 NOT RUN: isolated MySQL opt-in DSN is required.');
        }

        $authorization = (string) getenv('FINANCE_CP22_ALLOW_ISOLATED_DB');
        $guard = new FinanceDebtTestDatabaseGuard;
        $guard->assertAllowed($dsn, $authorization);
        if (! str_starts_with($dsn, 'mysql:')) {
            $this->markTestSkipped('CP27 currently supports real MySQL/InnoDB only.');
        }

        $user = (string) getenv('FINANCE_CP22_TEST_USER');
        $password = (string) getenv('FINANCE_CP22_TEST_PASSWORD');
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3,
        ]);
        $guard->assertConnectedDatabase(
            $dsn, $authorization, (string) $pdo->query('SELECT DATABASE()')->fetchColumn()
        );

        // This test must NEVER use or modify an existing application schema.
        foreach (['finance_debt_obligations', 'finance_vouchers'] as $table) {
            $check = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
            $check->execute([$table]);
            if ((int) $check->fetchColumn() !== 0) {
                throw new \RuntimeException('CP27 requires an empty isolated Finance test schema; existing table: '.$table);
            }
        }

        $previousDefault = DB::getDefaultConnection();
        $connectionName = 'finance_cp27_isolated';
        $previousConfig = config('database.connections.'.$connectionName);
        $created = [];
        try {
            // The gate uses Laravel's default DB connection; redirect ONLY inside
            // this test, to the already verified isolated test database.
            $settings = [];
            foreach (explode(';', substr($dsn, strlen('mysql:'))) as $entry) {
                [$key, $value] = explode('=', $entry, 2);
                $settings[$key] = $value;
            }
            config(['database.connections.'.$connectionName => [
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
            DB::purge($connectionName);
            DB::setDefaultConnection($connectionName);
            $laravel = DB::connection($connectionName);
            $guard->assertConnectedDatabase(
                $dsn, $authorization, (string) $laravel->selectOne('SELECT DATABASE() AS db')->db
            );

            // Minimal scratch schema: voucher is draft, so the gate must deny
            // before reaching source, idempotency or mapping services.
            $pdo->exec('CREATE TABLE finance_debt_obligations (id BIGINT PRIMARY KEY, status VARCHAR(20) NOT NULL) ENGINE=InnoDB');
            $created[] = 'finance_debt_obligations';
            $pdo->exec('CREATE TABLE finance_vouchers (id BIGINT PRIMARY KEY, status VARCHAR(20) NOT NULL) ENGINE=InnoDB');
            $created[] = 'finance_vouchers';
            $pdo->exec("INSERT INTO finance_debt_obligations (id, status) VALUES (1, 'confirmed')");
            $pdo->exec("INSERT INTO finance_vouchers (id, status) VALUES (1, 'draft')");

            $pdo->beginTransaction();
            $pdo->query('SELECT id FROM finance_debt_obligations WHERE id = 1 FOR UPDATE')->fetchAll();
            $laravel->statement('SET SESSION innodb_lock_wait_timeout = 1');
            try {
                try {
                    (new FinanceDebtAtomicSettlementGate)->execute(1, 1, 'cp27-lock-held');
                    self::fail('Settlement unexpectedly executed while debt lock was held.');
                } catch (QueryException $exception) {
                    $code = (int) ($exception->errorInfo[1] ?? 0);
                    self::assertSame(1205, $code, 'Expected InnoDB lock timeout, not an unrelated SQL error.');
                }
            } finally {
                $pdo->rollBack();
            }

            // With lock released, the real gate still rejects a draft voucher.
            try {
                (new FinanceDebtAtomicSettlementGate)->execute(1, 1, 'cp27-lock-released');
                self::fail('Draft voucher unexpectedly settled.');
            } catch (ValidationException $exception) {
                self::assertNotEmpty($exception->errors()['settlement'] ?? []);
            }
            self::assertSame('draft', $pdo->query('SELECT status FROM finance_vouchers WHERE id = 1')->fetchColumn());
            self::assertSame(1, (int) $pdo->query('SELECT COUNT(*) FROM finance_debt_obligations')->fetchColumn());
        } finally {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            DB::setDefaultConnection($previousDefault);
            DB::disconnect($connectionName);
            DB::purge($connectionName);
            config(['database.connections.'.$connectionName => $previousConfig]);
            foreach (array_reverse($created) as $table) {
                $pdo->exec('DROP TABLE IF EXISTS '.$table);
            }
        }
    }
}
