<?php

namespace Modules\Finance\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class FinanceDemoSeederContractTest extends TestCase
{
    public function test_demo_seed_is_opt_in_local_only_and_replaces_only_owned_data(): void
    {
        $base = dirname(__DIR__, 2);
        $seeder = file_get_contents($base.'/database/seeders/FinanceDemoSeeder.php');
        $rootSeeder = file_get_contents($base.'/../../database/seeders/DatabaseSeeder.php');
        $this->assertStringContainsString("environment(['local', 'testing'])", $seeder);
        $this->assertStringContainsString('private const PREFIX', $seeder);
        $this->assertStringContainsString('function clearOwnedDemo()', $seeder);
        $this->assertStringContainsString('Refusing to delete any data.', $seeder);
        $this->assertStringContainsString('createManualDraft(', $seeder);
        $this->assertStringContainsString('->post($id, null)', $seeder);
        $this->assertStringContainsString('$month <= 10', $seeder);
        $this->assertStringNotContainsString('FinanceDemoSeeder', $rootSeeder);
        $this->assertStringNotContainsString('truncate(', $seeder);
    }
}
