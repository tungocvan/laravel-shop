<?php

namespace Modules\Finance\Tests\Feature;

use Modules\Finance\Services\FinanceDebtTestDatabaseGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class FinanceDebtTestDatabaseGuardTest extends TestCase
{
    public function test_local_explicit_test_databases_are_allowed(): void
    {
        $guard = new FinanceDebtTestDatabaseGuard;
        foreach ([
            'mysql:host=127.0.0.1;dbname=finance_test',
            'pgsql:host=localhost;port=5432;dbname=finance_test',
        ] as $dsn) {
            self::assertTrue($guard->inspect($dsn, 'yes')['allowed']);
            $guard->assertAllowed($dsn, 'yes');
        }
    }

    public function test_actual_database_identity_must_match_configured_test_database(): void
    {
        $guard = new FinanceDebtTestDatabaseGuard;
        $dsn = 'mysql:host=127.0.0.1;dbname=finance_test';
        $guard->assertConnectedDatabase($dsn, 'yes', 'finance_test');
        foreach (['finance', 'other_test', ''] as $actual) {
            try {
                $guard->assertConnectedDatabase($dsn, 'yes', $actual);
                self::fail('Mismatched database was accepted.');
            } catch (\RuntimeException $exception) {
                self::assertStringContainsString('identity mismatch', $exception->getMessage());
            }
        }
    }

    public function test_remote_production_ambiguous_and_unauthorized_targets_are_blocked(): void
    {
        $guard = new FinanceDebtTestDatabaseGuard;
        foreach ([
            ['mysql:host=127.0.0.1;dbname=finance_test', ''],
            ['mysql:host=prod.example.com;dbname=finance_test', 'yes'],
            ['pgsql:host=127.0.0.1;dbname=finance', 'yes'],
            ['mysql:host=127.0.0.1;dbname=finance_test;host=prod.example.com', 'yes'],
            ['mysql:unix_socket=/tmp/mysql.sock;dbname=finance_test', 'yes'],
            ['sqlite:database=:memory:', 'yes'],
            ['pgsql:dbname=finance_test', 'yes'],
            ['mysql:host=127.0.0.1;dbname=finance_test;sslmode=disable', 'yes'],
        ] as [$dsn, $optIn]) {
            self::assertFalse($guard->inspect($dsn, $optIn)['allowed'], $dsn);
            try {
                $guard->assertAllowed($dsn, $optIn);
                self::fail('Unsafe target accepted: '.$dsn);
            } catch (\RuntimeException $exception) {
                self::assertStringContainsString('CP23 database test blocked', $exception->getMessage());
            }
        }
    }
}
