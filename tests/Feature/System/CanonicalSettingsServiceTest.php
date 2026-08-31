<?php

namespace Tests\Feature\System;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Admin\Livewire\Settings\SettingForm as AdminSettingForm;
use Modules\System\Livewire\Settings\SettingForm as SystemSettingForm;
use Modules\System\Models\Setting;
use Modules\System\Services\SettingsService;
use Tests\TestCase;

class CanonicalSettingsServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Schema::dropIfExists('settings');
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

    public function test_legacy_rows_are_imported_into_canonical_table_once(): void
    {
        $this->createSettingsTable('settings');
        $this->createSettingsTable('admin_settings');
        $this->createSettingsTable('website_settings');

        DB::table('admin_settings')->insert($this->row('site_name', 'Admin Legacy', 'general'));
        DB::table('website_settings')->insert($this->row('site_name', 'Website Legacy', 'general'));
        DB::table('website_settings')->insert($this->row('website.appearance', json_encode(['theme_color' => '#112233']), 'website'));

        $service = app(SettingsService::class);
        $service->importLegacyRows();

        $this->assertSame('Admin Legacy', $service->get('site_name'));
        $this->assertSame(['theme_color' => '#112233'], $service->get('website.appearance'));

        DB::table('admin_settings')->where('key', 'site_name')->update(['value' => 'Changed Legacy']);
        $service->importLegacyRows();

        $this->assertSame('Admin Legacy', $service->get('site_name'));
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

        foreach ([
            'Modules/Admin/Support/ThemeManager.php',
            'Modules/Admin/Support/AdminLayoutManager.php',
            'app/Services/RealtimeManager.php',
        ] as $file) {
            $contents = file_get_contents(base_path($file));
            $this->assertStringContainsString('Modules\\System\\Models\\Setting', $contents, $file);
            $this->assertStringNotContainsString('Modules\\Admin\\Models\\Setting', $contents, $file);
        }

        $this->assertTrue(is_subclass_of(
            AdminSettingForm::class,
            SystemSettingForm::class,
        ));

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

    private function row(
        string $key,
        string $value,
        string $group = 'general',
    ): array {
        return [
            'key' => $key,
            'value' => $value,
            'group_name' => $group,
            'type' => 'text',
            'label' => $key,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
