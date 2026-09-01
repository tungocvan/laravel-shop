<?php

namespace Tests\Feature\System;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\System\Models\Setting;
use Modules\System\Services\SettingsService;
use Tests\TestCase;

class CanonicalSettingsServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('wp_settings');
        Schema::dropIfExists('admin_settings');
        Schema::dropIfExists('website_settings');

        parent::tearDown();
    }

    public function test_system_settings_service_reads_and_writes_canonical_table(): void
    {
        $this->createSettingsTable('settings');

        $service = app(SettingsService::class);
        $service->set('site_name', 'FlexBiz', 'general');

        $this->assertSame('FlexBiz', $service->get('site_name'));
        $this->assertDatabaseHas('settings', [
            'key' => 'site_name',
            'value' => 'FlexBiz',
            'group_name' => 'general',
        ]);
    }

    public function test_wp_settings_is_not_a_runtime_read_or_write_fallback(): void
    {
        $this->createSettingsTable('settings');
        $this->createSettingsTable('wp_settings');

        DB::table('wp_settings')->insert([
            'key' => 'home_hero_title',
            'value' => 'Legacy hero',
            'group_name' => 'homepage',
            'type' => 'text',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(SettingsService::class);

        $this->assertSame('fallback', $service->get('home_hero_title', 'fallback'));
        $this->assertSame([], $service->getGroup('homepage'));

        $service->set('home_hero_title', 'Canonical hero', 'homepage');

        $this->assertDatabaseHas('settings', [
            'key' => 'home_hero_title',
            'value' => 'Canonical hero',
            'group_name' => 'homepage',
        ]);
        $this->assertDatabaseHas('wp_settings', [
            'key' => 'home_hero_title',
            'value' => 'Legacy hero',
        ]);
    }

    public function test_legacy_settings_tables_are_not_runtime_import_contracts(): void
    {
        $this->createSettingsTable('settings');
        $this->createSettingsTable('admin_settings');
        $this->createSettingsTable('website_settings');

        $service = app(SettingsService::class);

        $this->assertFalse(method_exists($service, 'importLegacyRows'));
        $this->assertSame('fallback', $service->get('legacy.only', 'fallback'));
    }

    public function test_setting_model_reads_canonical_settings_table(): void
    {
        $this->createSettingsTable('settings');

        Setting::query()->create([
            'key' => 'timezone',
            'value' => 'Asia/Ho_Chi_Minh',
            'group_name' => 'general',
            'type' => 'text',
        ]);

        $this->assertSame('settings', (new Setting)->getTable());
        $this->assertDatabaseHas('settings', [
            'key' => 'timezone',
            'value' => 'Asia/Ho_Chi_Minh',
        ]);
    }

    public function test_admin_and_website_use_system_owned_settings_contracts(): void
    {
        $this->assertFileDoesNotExist(base_path('Modules/Admin/Models/Setting.php'));
        $this->assertFileDoesNotExist(base_path('Modules/Admin/Services/SettingsService.php'));
        $this->assertFileDoesNotExist(base_path('Modules/Admin/Services/HomeSettingService.php'));
        $this->assertFileDoesNotExist(base_path('Modules/Website/Services/SettingsService.php'));

        $websiteSetting = file_get_contents(base_path('Modules/Website/Models/Setting.php'));
        $this->assertStringContainsString('extends \\Modules\\System\\Models\\Setting', $websiteSetting);
        $this->assertStringNotContainsString("protected $table = 'wp_settings'", $websiteSetting);

        foreach ([
            'Modules/Admin/Support/ThemeManager.php',
            'Modules/Admin/Support/AdminLayoutManager.php',
            'app/Services/RealtimeManager.php',
        ] as $file) {
            $contents = file_get_contents(base_path($file));
            $this->assertStringContainsString('Modules\\System\\Models\\Setting', $contents, $file);
            $this->assertStringNotContainsString('Modules\\Admin\\Models\\Setting', $contents, $file);
        }

        foreach ([
            'AdvancedConfig.php',
            'DatabaseConfig.php',
            'EnvManager.php',
            'MailConfig.php',
            'ModulesForm.php',
            'MomoConfig.php',
            'SettingForm.php',
            'SocialConfig.php',
            'StorageConfig.php',
        ] as $adapter) {
            $this->assertFileDoesNotExist(base_path('Modules/Admin/Livewire/Settings/'.$adapter));
        }

        $this->assertFileExists(base_path('Modules/System/Livewire/Settings/SettingForm.php'));
        $this->assertFileExists(base_path('Modules/Admin/Livewire/Settings/AdminLayoutConfig.php'));
        $this->assertFileExists(base_path('Modules/Admin/Livewire/Settings/AdminLayoutDashboard.php'));
        $this->assertFileExists(base_path('Modules/Admin/Livewire/Settings/AdminThemeEditor.php'));

        $websiteHome = file_get_contents(base_path('Modules/Website/resources/views/pages/admin/home/index.blade.php'));
        $this->assertStringContainsString("@livewire('website.admin.home.home-settings')", $websiteHome);
        $this->assertFileDoesNotExist(base_path('Modules/Admin/resources/views/pages/home/index.blade.php'));
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
}
