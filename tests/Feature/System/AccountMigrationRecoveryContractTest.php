<?php

namespace Tests\Feature\System;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AccountMigrationRecoveryContractTest extends TestCase
{
    public function test_account_ownership_contract_covers_every_account_migration(): void
    {
        $contract = require base_path('Modules/Account/config/migration_ownership.php');

        $migrationNames = array_map(
            static fn ($file): string => $file->getBasename('.php'),
            File::files(base_path('Modules/Account/database/migrations')),
        );
        $contractMigrations = array_keys($contract);

        sort($migrationNames);
        sort($contractMigrations);

        $this->assertSame($migrationNames, $contractMigrations);
    }

    public function test_account_ownership_contract_maps_each_migration_to_its_schema_artifact(): void
    {
        $contract = require base_path('Modules/Account/config/migration_ownership.php');

        $this->assertSame([
            'columns' => [
                'users' => ['account_type'],
            ],
        ], $contract['2026_05_26_143653_update_users_for_account']);

        $this->assertSame(
            ['tables' => ['employee_profiles']],
            $contract['2026_05_26_143725_employee_profiles'],
        );
        $this->assertSame(
            ['tables' => ['customer_profiles']],
            $contract['2026_05_26_143744_customer_profiles'],
        );
        $this->assertSame(
            ['tables' => ['user_metas']],
            $contract['2026_05_26_143758_user_metas'],
        );
        $this->assertSame(
            ['tables' => ['user_identity_profiles']],
            $contract['2026_05_27_000005_create_user_identity_profiles_table'],
        );
    }
}
