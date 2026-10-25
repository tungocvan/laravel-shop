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
 * CP29: reversal-origin unique-index contention and fail-closed service boundary.
 * There is no approved reversal writer yet. Do not interpret this as a
 * concurrent reversal service test or settlement eligibility.
 */
final class FinanceDebtReversalOwnershipRaceTest extends TestCase
{
    public function test_reversal_origin_contention_rollback_and_gate_denial(): void
    {
        $dsn = (string) getenv('FINANCE_CP22_TEST_DSN');
        if ($dsn === '') {
            $this->markTestSkipped('CP29 NOT RUN: isolated MySQL test DSN required.');
        }
        $authorization = (string) getenv('FINANCE_CP22_ALLOW_ISOLATED_DB');
        $guard = new FinanceDebtTestDatabaseGuard;
        $guard->assertAllowed($dsn, $authorization);
        if (! str_starts_with($dsn, 'mysql:')) {
            $this->markTestSkipped('CP29 requires MySQL/InnoDB.');
        }

        $user = (string) getenv('FINANCE_CP22_TEST_USER');
        $password = (string) getenv('FINANCE_CP22_TEST_PASSWORD');
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3,
        ]);
        $guard->assertConnectedDatabase($dsn, $authorization, (string) $pdo->query('SELECT DATABASE()')->fetchColumn());

        $tables = ['finance_debt_settlement_mappings', 'finance_debt_obligations', 'finance_vouchers'];
        foreach ($tables as $table) {
            $statement = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
            $statement->execute([$table]);
            if ((int) $statement->fetchColumn() !== 0) {
                throw new \RuntimeException('CP29 refuses to touch existing Finance table: '.$table);
            }
        }

        $previousDefault = DB::getDefaultConnection();
        $name = 'finance_cp29_isolated';
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
            $pdo->exec('CREATE TABLE finance_vouchers (id BIGINT UNSIGNED PRIMARY KEY, status VARCHAR(20) NOT NULL) ENGINE=InnoDB');
            $created[] = 'finance_vouchers';
            $pdo->exec('CREATE TABLE finance_debt_settlement_mappings (
                id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
                obligation_id BIGINT UNSIGNED NOT NULL,
                voucher_id BIGINT UNSIGNED NOT NULL,
                operation VARCHAR(20) NOT NULL,
                amount DECIMAL(20,2) NOT NULL,
                reverses_mapping_id BIGINT UNSIGNED NULL,
                UNIQUE KEY cp29_reversal_once_unique (reverses_mapping_id),
                UNIQUE KEY cp29_mapping_once_unique (obligation_id, voucher_id, operation)
            ) ENGINE=InnoDB');
            $created[] = 'finance_debt_settlement_mappings';
            $pdo->exec('INSERT INTO finance_debt_obligations (id) VALUES (1)');
            $pdo->exec("INSERT INTO finance_vouchers (id, status) VALUES (1, 'draft'), (2, 'reversed'), (3, 'draft')");
            $pdo->exec("INSERT INTO finance_debt_settlement_mappings
                (id, obligation_id, voucher_id, operation, amount, reverses_mapping_id)
                VALUES (1, 1, 1, 'post', 30.00, NULL)");

            $laravel->statement('SET SESSION innodb_lock_wait_timeout = 1');
            $pdo->beginTransaction();
            $pdo->exec("INSERT INTO finance_debt_settlement_mappings
                (id, obligation_id, voucher_id, operation, amount, reverses_mapping_id)
                VALUES (2, 1, 2, 'reverse', 30.00, 1)");
            try {
                try {
                    $laravel->table('finance_debt_settlement_mappings')->insert([
                        'id' => 3, 'obligation_id' => 1, 'voucher_id' => 3,
                        'operation' => 'reverse', 'amount' => '30.00', 'reverses_mapping_id' => 1,
                    ]);
                    self::fail('Competing reversal bypassed the unique origin.');
                } catch (QueryException $exception) {
                    self::assertSame(1205, (int) ($exception->errorInfo[1] ?? 0),
                        'Expected InnoDB lock timeout, not unrelated SQL failure.');
                }
                self::assertSame(1, (int) $laravel->table('finance_debt_settlement_mappings')->count());
            } finally {
                $pdo->rollBack();
            }

            self::assertSame(1, (int) $pdo->query('SELECT COUNT(*) FROM finance_debt_settlement_mappings')->fetchColumn());
            $laravel->table('finance_debt_settlement_mappings')->insert([
                'id' => 3, 'obligation_id' => 1, 'voucher_id' => 3,
                'operation' => 'reverse', 'amount' => '30.00', 'reverses_mapping_id' => 1,
            ]);
            try {
                $laravel->table('finance_debt_settlement_mappings')->insert([
                    'id' => 2, 'obligation_id' => 1, 'voucher_id' => 2,
                    'operation' => 'reverse', 'amount' => '30.00', 'reverses_mapping_id' => 1,
                ]);
                self::fail('Committed reversal origin was reused.');
            } catch (QueryException $exception) {
                self::assertSame(1062, (int) ($exception->errorInfo[1] ?? 0));
            }

            // The actual gate rejects an incompatible voucher lifecycle before any\n            // downstream service could mutate the artificial reversal mapping.
            try {
                (new FinanceDebtAtomicSettlementGate)->execute(1, 3, 'cp29-reversal-gate', 'reverse');
                self::fail('Settlement gate unexpectedly authorized reversal.');
            } catch (ValidationException $exception) {
                self::assertNotEmpty($exception->errors()['settlement'] ?? []);
            }
            self::assertSame(2, (int) $pdo->query('SELECT COUNT(*) FROM finance_debt_settlement_mappings')->fetchColumn());
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
