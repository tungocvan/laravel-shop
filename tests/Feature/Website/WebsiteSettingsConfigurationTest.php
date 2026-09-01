<?php

namespace Tests\Feature\Website;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\System\Models\Setting;
use Modules\System\Services\SettingsService;
use Tests\TestCase;

class WebsiteSettingsConfigurationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            return;
        }

        foreach (['settings', 'wp_settings'] as $tableName) {
            Schema::dropIfExists($tableName);
            Schema::create($tableName, function (Blueprint $table): void {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('group_name')->default('general');
                $table->string('type')->default('text');
                $table->string('label')->nullable();
                $table->timestamps();
            });
        }
        Cache::flush();
    }

    public function test_get_set_and_update_many_use_one_consistent_cache_contract(): void
    {
        $this->requireSqlite();
        $service = app(SettingsService::class);

        $service->set('site_name', 'Before');
        $this->assertSame('Before', $service->get('site_name'));

        Cache::forever('setting_site_name', 'legacy-stale');
        $service->updateMany([
            'site_name' => 'After',
            'home_items' => [1, 2, 3],
        ], 'website');

        $this->assertFalse(Cache::has('setting_site_name'));
        $this->assertSame('After', $service->get('site_name'));
        $this->assertSame([1, 2, 3], $service->get('home_items'));
        $this->assertSame('json', DB::table('settings')->where('key', 'home_items')->value('type'));
        $this->assertNull(DB::table('wp_settings')->where('key', 'home_items')->value('type'));
        $this->assertSame('After', Setting::query()->where('key', 'site_name')->value('value'));
    }

    public function test_update_many_rolls_back_and_rethrows_controlled_failure(): void
    {
        $this->requireSqlite();
        $service = app(SettingsService::class);
        try {
            $service->updateMany(['first' => 'written', 'invalid' => ["\xB1\x31"]]);
            $this->fail('The invalid setting value should have thrown.');
        } catch (\JsonException $exception) {
            $this->assertDatabaseMissing('settings', ['key' => 'first']);
        }
    }

    public function test_phase_1c_has_no_debug_termination_or_direct_blade_setting_queries(): void
    {
        $service = file_get_contents(base_path('Modules/System/Services/SettingsService.php'));
        $layout = file_get_contents(base_path('Modules/Website/resources/views/layouts/frontend.blade.php'));
        $runtimeHead = file_get_contents(base_path('Modules/Website/resources/views/partials/layout/runtime-head.blade.php'));
        $home = file_get_contents(base_path('Modules/Website/resources/views/pages/home/index.blade.php'));
        $help = file_get_contents(base_path('Modules/Website/resources/views/pages/help/index.blade.php'));

        $this->assertDoesNotMatchRegularExpression('/\b(dd|dump|die)\s*\(/', $service);
        $this->assertStringNotContainsString('Setting::', $layout.$runtimeHead.$home.$help);
        $this->assertStringContainsString("@include('Website::partials.layout.runtime-head')", $layout);
        $this->assertStringContainsString('$headerScript', $runtimeHead);
        $this->assertStringContainsString('SettingsService::class', file_get_contents(base_path('Modules/Website/Providers/WebsiteServiceProvider.php')));
    }

    public function test_header_script_and_upload_paths_keep_phase_1c_security_guards(): void
    {
        $header = file_get_contents(base_path('Modules/Website/Livewire/Admin/Header/GeneralSettings.php'));
        $home = file_get_contents(base_path('Modules/Website/Livewire/Admin/Home/HomeSettings.php'));
        $banner = file_get_contents(base_path('Modules/Website/Services/BannerService.php'));

        $this->assertStringContainsString("authorizeAdminPermission('website.settings.manage')", $header);
        $this->assertStringContainsString("'header_script'", $header);
        $this->assertStringContainsString('image|mimes:jpg,jpeg,png,webp|max:3072', $home);
        $this->assertStringContainsString('catch (\\Throwable $exception)', $home);
        $this->assertStringContainsString('DB::transaction', $banner);
        $this->assertStringContainsString('throw $exception', $banner);
        $this->assertGreaterThan(
            strpos($banner, 'DB::transaction'),
            strrpos($banner, '$this->deleteImage($oldDesktop)')
        );
    }

    public function test_header_settings_livewire_view_has_a_single_root_wrapper(): void
    {
        $view = trim(file_get_contents(base_path(
            'Modules/Website/resources/views/livewire/admin/header/general-settings.blade.php'
        )));

        $this->assertStringStartsWith('<div class="space-y-6">', $view);
        $this->assertStringEndsWith('</div>', $view);
        $this->assertSame(1, preg_match('/^<div class="space-y-6">[\s\S]*<\/div>$/', $view));
    }

    private function requireSqlite(): void
    {
        if (! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('PDO SQLite is not installed in the current PHP CLI runtime.');
        }
    }
}
