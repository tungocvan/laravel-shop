<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteHeaderMenuSeederConfigurationTest extends TestCase
{
    public function test_account_header_menu_seeder_is_idempotent_and_non_destructive(): void
    {
        $seeder = file_get_contents(base_path('Modules/Website/database/Seeders/HeaderMenuSeeder.php'));

        $this->assertStringContainsString("namespace Modules\\Website\\database\\Seeders;", $seeder);
        $this->assertStringContainsString("['location' => 'account']", $seeder);
        $this->assertStringContainsString('updateOrCreate(', $seeder);
        $this->assertStringContainsString('Ứng dụng của tôi', $seeder);
        $this->assertStringContainsString('Hồ sơ cá nhân', $seeder);
        $this->assertStringContainsString('Đơn hàng của tôi', $seeder);
        $this->assertStringNotContainsString('truncate()', $seeder);
        $this->assertStringNotContainsString('FOREIGN_KEY_CHECKS', $seeder);
    }
}
