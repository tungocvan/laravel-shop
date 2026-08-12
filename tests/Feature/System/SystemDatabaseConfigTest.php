<?php

namespace Tests\Feature\System;

use Illuminate\Support\Facades\Route;
use Modules\System\Services\Database\DatabaseConfigService;
use Modules\System\Services\Database\DbConnectionService;
use Modules\System\Services\Env\EnvManagerService;
use Tests\TestCase;

class SystemDatabaseConfigTest extends TestCase
{
    public function test_env_route_and_admin_menu_use_system_env_view_permission(): void
    {
        $route = Route::getRoutes()->getByName('admin.system.settings.env');

        $this->assertNotNull($route);
        $this->assertSame('admin/system/settings/env', $route->uri());
        $this->assertContains('auth:admin', $route->gatherMiddleware());
        $this->assertContains('permission:system.env.view,admin', $route->gatherMiddleware());

        $menus = json_decode(file_get_contents(base_path('Modules/Admin/data/menus.json')), true, flags: JSON_THROW_ON_ERROR);
        $systemMenu = collect($menus)->firstWhere('name', 'Công cụ Hệ thống');
        $envMenu = collect($systemMenu['children'] ?? [])->firstWhere('url', '/admin/system/settings/env');

        $this->assertNotNull($envMenu);
        $this->assertSame('Quản lý ENV', $envMenu['name']);
        $this->assertSame('system.env.view', $envMenu['can']);
    }

    public function test_livewire_enforces_update_permission_and_uses_system_owned_view(): void
    {
        $source = file_get_contents(base_path('Modules/System/Livewire/Settings/DatabaseConfig.php'));

        $this->assertStringContainsString('AuthorizesSystemActions', $source);
        $this->assertStringContainsString("authorizePermission('system.env.update')", $source);
        $this->assertSame(2, substr_count($source, "authorizePermission('system.env.update')"));
        $this->assertStringContainsString("view('System::livewire.settings.database-config')", $source);
        $this->assertStringNotContainsString('EnvBackupService', $source);
        $this->assertStringNotContainsString("Artisan::call('config:clear')", $source);
    }

    public function test_public_config_never_returns_existing_password(): void
    {
        $env = $this->mock(EnvManagerService::class);
        $db = $this->mock(DbConnectionService::class);
        $env->shouldReceive('getValues')->once()->andReturn([
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => 'db',
            'DB_PORT' => '3306',
            'DB_DATABASE' => 'app',
            'DB_USERNAME' => 'appuser',
            'DB_PASSWORD' => 'super-secret',
        ]);

        $service = new DatabaseConfigService($env, $db);
        $config = $service->publicConfig();

        $this->assertSame('mysql', $config['DB_CONNECTION']);
        $this->assertArrayNotHasKey('DB_PASSWORD', $config);
        $this->assertNotContains('super-secret', $config, true);
    }

    public function test_blank_replacement_password_is_resolved_server_side_for_connection_test(): void
    {
        $env = $this->mock(EnvManagerService::class);
        $db = $this->mock(DbConnectionService::class);
        $env->shouldReceive('getValues')->once()->andReturn(['DB_PASSWORD' => 'existing-secret']);
        $db->shouldReceive('testConnection')->once()->withArgs(function (array $candidate): bool {
            return $candidate['DB_PASSWORD'] === 'existing-secret'
                && $candidate['DB_CONNECTION'] === 'mysql';
        })->andReturn(['success' => true, 'message' => 'ok']);

        $service = new DatabaseConfigService($env, $db);
        $result = $service->test($this->validForm(''));

        $this->assertTrue($result['success']);
    }

    public function test_explicit_replacement_password_is_used_for_connection_test(): void
    {
        $env = $this->mock(EnvManagerService::class);
        $db = $this->mock(DbConnectionService::class);
        $env->shouldReceive('getValues')->once()->andReturn(['DB_PASSWORD' => 'old-secret']);
        $db->shouldReceive('testConnection')->once()->withArgs(
            fn (array $candidate): bool => $candidate['DB_PASSWORD'] === 'new-secret'
        )->andReturn(['success' => true, 'message' => 'ok']);

        $service = new DatabaseConfigService($env, $db);
        $result = $service->test($this->validForm('new-secret'));

        $this->assertTrue($result['success']);
    }

    public function test_livewire_validation_allowlists_driver_and_port_and_bounds_password(): void
    {
        $source = file_get_contents(base_path('Modules/System/Livewire/Settings/DatabaseConfig.php'));

        $this->assertStringContainsString("'form.DB_CONNECTION' => ['required', 'in:mysql,pgsql']", $source);
        $this->assertStringContainsString("'form.DB_PORT' => ['required', 'integer', 'min:1', 'max:65535']", $source);
        $this->assertStringContainsString("'form.DB_PASSWORD' => ['nullable', 'string', 'max:4096']", $source);
    }

    public function test_connection_service_redacts_raw_infrastructure_errors_and_purges_temp_connection(): void
    {
        $source = file_get_contents(base_path('Modules/System/Services/Database/DbConnectionService.php'));

        $this->assertStringContainsString("['mysql', 'pgsql']", $source);
        $this->assertStringContainsString('DB::purge($tempConnection)', $source);
        $this->assertStringContainsString('Config::set("database.connections.{$tempConnection}", null)', $source);
        $this->assertStringNotContainsString('$e->getMessage()', $source);
        $this->assertStringContainsString('Không thể kết nối cơ sở dữ liệu với cấu hình đã nhập.', $source);
    }

    public function test_orchestration_service_uses_lock_and_canonical_env_manager_backup_workflow(): void
    {
        $source = file_get_contents(base_path('Modules/System/Services/Database/DatabaseConfigService.php'));

        $this->assertStringContainsString("Cache::lock('system:database-config:update'", $source);
        $this->assertStringContainsString('$this->envManager->update($candidate)', $source);
        $this->assertStringContainsString("Artisan::call('config:clear')", $source);
        $this->assertStringNotContainsString('EnvBackupService', $source);
    }

    public function test_system_blade_has_write_only_password_guidance_confirmation_and_read_only_state(): void
    {
        $blade = file_get_contents(base_path('Modules/System/resources/views/livewire/settings/database-config.blade.php'));

        $this->assertStringContainsString('Để trống để giữ mật khẩu hiện tại', $blade);
        $this->assertStringContainsString('Mật khẩu hiện tại không được gửi về trình duyệt', $blade);
        $this->assertStringContainsString('wire:confirm=', $blade);
        $this->assertStringContainsString('system.env.update', $blade);
        $this->assertStringContainsString('<option value="mysql">', $blade);
        $this->assertStringContainsString('<option value="pgsql">', $blade);
        $this->assertStringContainsString("@error('form.DB_PASSWORD')", $blade);
    }

    public function test_component_resets_connection_status_when_form_changes(): void
    {
        $source = file_get_contents(base_path('Modules/System/Livewire/Settings/DatabaseConfig.php'));

        $this->assertStringContainsString('function updatedForm()', $source);
        $this->assertStringContainsString("\$this->connectionStatus = '';", $source);
    }

    private function validForm(string $password): array
    {
        return [
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '3306',
            'DB_DATABASE' => 'app',
            'DB_USERNAME' => 'appuser',
            'DB_PASSWORD' => $password,
        ];
    }
}
