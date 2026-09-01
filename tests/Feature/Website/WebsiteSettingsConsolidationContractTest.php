<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteSettingsConsolidationContractTest extends TestCase
{
    public function test_website_settings_persistence_delegates_to_system(): void
    {
        $websiteSetting = file_get_contents(base_path('Modules/Website/Models/Setting.php'));
        $systemService = file_get_contents(base_path('Modules/System/Services/SettingsService.php'));

        $this->assertStringContainsString('extends \\Modules\\System\\Models\\Setting', $websiteSetting);
        $this->assertStringNotContainsString('wp_settings', $websiteSetting);
        $this->assertStringNotContainsString("DB::table('wp_settings')", $systemService);
        $this->assertStringNotContainsString('isLegacyHomepageKey', $systemService);
        $this->assertStringContainsString("Schema::hasTable('settings')", $systemService);
    }

    public function test_consolidation_migration_is_additive_and_non_destructive(): void
    {
        $migration = file_get_contents(base_path('Modules/Website/database/migrations/2026_09_01_000001_consolidate_wp_settings_into_settings.php'));

        $this->assertStringContainsString("Schema::hasTable('settings')", $migration);
        $this->assertStringContainsString("Schema::hasTable('wp_settings')", $migration);
        $this->assertStringContainsString("DB::table('wp_settings')", $migration);
        $this->assertStringContainsString("DB::table('settings')", $migration);
        $this->assertStringContainsString("where('key', $legacy->key)->exists()", $migration);
        $this->assertStringNotContainsString("dropIfExists('wp_settings')", $migration);
        $this->assertStringNotContainsString("drop('wp_settings')", $migration);
        $this->assertStringNotContainsString("truncate", $migration);
    }
}
