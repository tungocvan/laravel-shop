<?php

namespace Tests\Feature\System;

use Illuminate\Support\Facades\Route;
use Modules\Admin\Services\Database as AdminDatabaseServices;
use Modules\Admin\Services\Env as AdminEnvServices;
use Modules\System\Services\Database as SystemDatabaseServices;
use Modules\System\Services\Env as SystemEnvServices;
use ReflectionClass;
use Tests\TestCase;

class SystemSettingsOwnershipTest extends TestCase
{
    public function test_legacy_admin_livewire_settings_adapters_are_retired(): void
    {
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
            'Placeholder.php',
        ] as $file) {
            $this->assertFileDoesNotExist(base_path('Modules/Admin/Livewire/Settings/'.$file));
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
        ] as $file) {
            $this->assertFileExists(base_path('Modules/System/Livewire/Settings/'.$file));
        }
    }

    public function test_legacy_admin_services_delegate_to_system_services(): void
    {
        $adapters = [
            AdminEnvServices\EnvBackupService::class => SystemEnvServices\EnvBackupService::class,
            AdminEnvServices\EnvManagerService::class => SystemEnvServices\EnvManagerService::class,
            AdminEnvServices\MailConfigService::class => SystemEnvServices\MailConfigService::class,
            AdminEnvServices\SocialConfigService::class => SystemEnvServices\SocialConfigService::class,
            AdminEnvServices\SystemConfigService::class => SystemEnvServices\SystemConfigService::class,
            AdminDatabaseServices\DbConnectionService::class => SystemDatabaseServices\DbConnectionService::class,
        ];

        foreach ($adapters as $legacy => $canonical) {
            $this->assertTrue(is_subclass_of($legacy, $canonical), "{$legacy} must delegate to {$canonical}.");
            $source = file_get_contents((new ReflectionClass($legacy))->getFileName());
            $this->assertStringNotContainsString('function ', $source, "{$legacy} must remain a thin adapter.");
        }
    }

    public function test_legacy_admin_settings_controllers_are_retired(): void
    {
        $this->assertFileDoesNotExist(base_path('Modules/Admin/Http/Controllers/SettingController.php'));
        $this->assertFileDoesNotExist(base_path('Modules/Admin/Http/Controllers/EnvConfigController.php'));
    }

    public function test_legacy_settings_url_redirects_under_the_canonical_permission(): void
    {
        $route = Route::getRoutes()->getByName('admin.system.settings.legacy');

        $this->assertNotNull($route);
        $this->assertSame('admin/settings', $route->uri());
        $this->assertSame('/admin/system/settings', $route->defaults['destination'] ?? null);
        $this->assertContains('auth:admin', $route->gatherMiddleware());
        $this->assertContains('permission:system.settings.view,admin', $route->gatherMiddleware());
    }

    public function test_legacy_admin_settings_views_are_retired(): void
    {
        foreach ([
            'livewire/settings/advanced-config.blade.php',
            'livewire/settings/database-config.blade.php',
            'livewire/settings/env-manager.blade.php',
            'livewire/settings/mail-config.blade.php',
            'livewire/settings/modules-form.blade.php',
            'livewire/settings/momo-config.blade.php',
            'livewire/settings/setting-form.blade.php',
            'livewire/settings/social-config.blade.php',
            'livewire/settings/storage-config.blade.php',
            'pages/settings/env.blade.php',
            'pages/settings/index.blade.php',
            'pages/settings/modules.blade.php',
            'pages/settings/placeholder.blade.php',
        ] as $view) {
            $this->assertFileDoesNotExist(base_path('Modules/Admin/resources/views/'.$view));
        }

        $this->assertFileExists(base_path('Modules/Admin/resources/views/livewire/settings/admin-layout-config.blade.php'));
        $this->assertFileExists(base_path('Modules/Admin/resources/views/livewire/settings/admin-theme-editor.blade.php'));
    }

    public function test_seeded_settings_link_and_admission_import_use_system_ownership(): void
    {
        $headerSeeder = file_get_contents(base_path('Modules/Website/database/Seeders/HeaderSeeder.php'));
        $admission = file_get_contents(base_path('Modules/Admission/Livewire/Admin/SchoolSettingsForm.php'));

        $this->assertStringContainsString("'url' => '/admin/system/settings'", $headerSeeder);
        $this->assertStringNotContainsString("'url' => '/admin/settings'", $headerSeeder);
        $this->assertStringContainsString('use Modules\\System\\Models\\Setting;', $admission);
        $this->assertStringNotContainsString('use Modules\\Admin\\Models\\Setting;', $admission);
    }
}
