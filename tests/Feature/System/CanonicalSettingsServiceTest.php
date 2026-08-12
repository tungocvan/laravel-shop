<?php

namespace Tests\Feature\System;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\System\Services\LegacySettingsAuditService;
use Modules\System\Services\LegacySettingsMigrationService;
use Modules\System\Services\SettingsService;
use Tests\TestCase;

class CanonicalSettingsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createSettingsTable('settings');
        $this->createSettingsTable('wp_settings');
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('wp_settings');
        Schema::dropIfExists('settings');
        parent::tearDown();
    }

    public function test_canonical_value_wins_and_legacy_value_is_only_a_read_fallback(): void
    {
        DB::table('wp_settings')->insert($this->row('site_name', 'Legacy'));
        $service = app(SettingsService::class);

        $this->assertSame('Legacy', $service->get('site_name'));

        $service->set('site_name', 'Canonical');

        $this->assertSame('Canonical', $service->get('site_name'));
        $this->assertDatabaseHas('settings', ['key' => 'site_name', 'value' => 'Canonical']);
        $this->assertDatabaseHas('wp_settings', ['key' => 'site_name', 'value' => 'Legacy']);
    }

    public function test_arrays_are_normalized_and_bulk_update_is_atomic(): void
    {
        $service = app(SettingsService::class);
        $service->updateMany([
            'theme.palette' => ['primary' => '#123456'],
            'site_email' => 'test@example.test',
        ], 'general');

        $this->assertSame(['primary' => '#123456'], $service->get('theme.palette'));
        $this->assertSame('test@example.test', $service->get('site_email'));
        $this->assertDatabaseHas('settings', ['key' => 'theme.palette', 'type' => 'json']);
    }

    public function test_homepage_keys_remain_on_legacy_table_until_structured_schema_is_ready(): void
    {
        $service = app(SettingsService::class);
        $service->updateMany([
            'home_featured_ids' => [4, 8],
            'site_name' => 'Canonical site',
        ], 'homepage');

        $this->assertSame([4, 8], $service->get('home_featured_ids'));
        $this->assertDatabaseHas('wp_settings', ['key' => 'home_featured_ids', 'type' => 'json']);
        $this->assertDatabaseMissing('settings', ['key' => 'home_featured_ids']);
        $this->assertDatabaseHas('settings', ['key' => 'site_name']);
    }

    public function test_audit_classifies_conflicts_without_changing_either_table(): void
    {
        DB::table('settings')->insert([
            $this->row('site_name', 'Canonical'),
            $this->row('same_key', 'same'),
        ]);
        DB::table('wp_settings')->insert([
            $this->row('site_name', 'Legacy'),
            $this->row('same_key', 'same'),
            $this->row('home_featured_ids', '[1,2]', 'homepage', 'json'),
        ]);

        $report = app(LegacySettingsAuditService::class)->audit();

        $this->assertSame(1, $report['summary']['conflict']);
        $this->assertSame(1, $report['summary']['identical']);
        $this->assertSame(1, $report['summary']['legacy_only']);
        $this->assertSame(1, $report['summary']['structured_homepage']);
        $this->assertSame(2, DB::table('settings')->count());
        $this->assertSame(3, DB::table('wp_settings')->count());
    }

    public function test_legacy_migration_is_dry_run_by_default_and_skips_homepage_keys(): void
    {
        DB::table('wp_settings')->insert([
            $this->row('site_name', 'Website'),
            $this->row('home_featured_ids', '[1,2]', 'homepage', 'json'),
        ]);
        $service = app(LegacySettingsMigrationService::class);

        $dryRun = $service->migrate();
        $this->assertSame(1, $dryRun['inserted']);
        $this->assertSame(1, $dryRun['skipped_homepage']);
        $this->assertSame(0, DB::table('settings')->count());

        $applied = $service->migrate(true);
        $this->assertTrue($applied['applied']);
        $this->assertDatabaseHas('settings', ['key' => 'site_name', 'value' => 'Website']);
        $this->assertDatabaseMissing('settings', ['key' => 'home_featured_ids']);

        $repeated = $service->migrate(true);
        $this->assertSame(0, $repeated['inserted']);
        $this->assertSame(1, $repeated['identical']);
        $this->assertSame(1, DB::table('settings')->count());
    }

    public function test_admin_and_website_use_system_owned_settings_contracts(): void
    {
        $this->assertFileDoesNotExist(base_path('Modules/Admin/Models/Setting.php'));
        $this->assertFileDoesNotExist(base_path('Modules/Admin/Services/SettingsService.php'));
        $this->assertFileDoesNotExist(base_path('Modules/Admin/Services/HomeSettingService.php'));
        $this->assertFileDoesNotExist(base_path('Modules/Website/Services/SettingsService.php'));

        foreach ([
            'Modules/Admin/Support/ThemeManager.php',
            'Modules/Admin/Support/AdminLayoutManager.php',
            'Modules/Admin/Livewire/Settings/SettingForm.php',
            'app/Services/RealtimeManager.php',
        ] as $file) {
            $contents = file_get_contents(base_path($file));
            $this->assertStringContainsString('Modules\\System\\Models\\Setting', $contents, $file);
            $this->assertStringNotContainsString('Modules\\Admin\\Models\\Setting', $contents, $file);
        }

        $adminHome = file_get_contents(base_path('Modules/Admin/resources/views/pages/home/index.blade.php'));
        $this->assertStringContainsString("@livewire('website.admin.home.home-settings')", $adminHome);
    }

    private function createSettingsTable(string $name): void
    {
        Schema::create($name, function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group_name')->default('general');
            $table->string('type')->default('text');
            $table->string('label')->nullable();
            $table->timestamps();
        });
    }

    private function row(
        string $key,
        string $value,
        string $group = 'general',
        string $type = 'text',
    ): array {
        return [
            'key' => $key,
            'value' => $value,
            'group_name' => $group,
            'type' => $type,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
