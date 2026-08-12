<?php

namespace Tests\Feature\System;

use Illuminate\Support\Facades\Route;
use Modules\System\Services\Env\EnvManagerService;
use Modules\System\Services\Env\MailConfigService;
use Modules\System\Services\Env\SystemMailConfigService;
use Tests\TestCase;

class SystemMailConfigTest extends TestCase
{
    public function test_env_route_and_menu_remain_canonical(): void
    {
        $route = Route::getRoutes()->getByName('admin.system.settings.env');
        $this->assertNotNull($route);
        $this->assertSame('admin/system/settings/env', $route->uri());
        $this->assertContains('permission:system.env.view,admin', $route->gatherMiddleware());

        $menus = json_decode(file_get_contents(base_path('Modules/Admin/data/menus.json')), true, flags: JSON_THROW_ON_ERROR);
        $systemMenu = collect($menus)->firstWhere('name', 'Công cụ Hệ thống');
        $envMenu = collect($systemMenu['children'] ?? [])->firstWhere('url', '/admin/system/settings/env');

        $this->assertNotNull($envMenu);
        $this->assertSame('system.env.view', $envMenu['can']);
    }

    public function test_livewire_enforces_update_permission_on_send_and_save(): void
    {
        $source = file_get_contents(base_path('Modules/System/Livewire/Settings/MailConfig.php'));

        $this->assertStringContainsString('AuthorizesSystemActions', $source);
        $this->assertSame(2, substr_count($source, "authorizePermission('system.env.update')"));
        $this->assertStringNotContainsString('EnvManagerService', $source);
        $this->assertStringNotContainsString('$e->getMessage()', $source);
    }

    public function test_public_config_never_returns_existing_password(): void
    {
        $env = $this->mock(EnvManagerService::class);
        $mailer = $this->mock(MailConfigService::class);
        $env->shouldReceive('getValues')->once()->andReturn([
            'MAIL_MAILER' => 'smtp',
            'MAIL_HOST' => 'smtp.example.com',
            'MAIL_PORT' => '587',
            'MAIL_USERNAME' => 'mailer',
            'MAIL_PASSWORD' => 'super-secret',
            'MAIL_ENCRYPTION' => 'tls',
            'MAIL_FROM_ADDRESS' => 'noreply@example.com',
            'MAIL_FROM_NAME' => 'Example',
        ]);

        $service = new SystemMailConfigService($env, $mailer);
        $config = $service->publicConfig();

        $this->assertArrayNotHasKey('MAIL_PASSWORD', $config);
        $this->assertNotContains('super-secret', $config, true);
        $this->assertSame('smtp', $config['MAIL_MAILER']);
    }

    public function test_public_none_encryption_uses_semantic_ui_value(): void
    {
        $env = $this->mock(EnvManagerService::class);
        $mailer = $this->mock(MailConfigService::class);
        $env->shouldReceive('getValues')->once()->andReturn(['MAIL_ENCRYPTION' => '']);

        $service = new SystemMailConfigService($env, $mailer);

        $this->assertSame('none', $service->publicConfig()['MAIL_ENCRYPTION']);
    }

    public function test_validation_allowlists_smtp_port_encryption_and_secret_bound(): void
    {
        $source = file_get_contents(base_path('Modules/System/Livewire/Settings/MailConfig.php'));

        $this->assertStringContainsString("'form.MAIL_MAILER' => ['required', 'in:smtp']", $source);
        $this->assertStringContainsString("'form.MAIL_PORT' => ['required', 'integer', 'min:1', 'max:65535']", $source);
        $this->assertStringContainsString("'form.MAIL_ENCRYPTION' => ['nullable', 'in:tls,ssl,none']", $source);
        $this->assertStringContainsString("'form.MAIL_PASSWORD' => ['nullable', 'string', 'max:4096']", $source);
        $this->assertStringContainsString("'testEmail' => ['required', 'email', 'max:255']", $source);
    }

    public function test_orchestration_service_preserves_secret_server_side_and_uses_guards(): void
    {
        $source = file_get_contents(base_path('Modules/System/Services/Env/SystemMailConfigService.php'));

        $this->assertStringContainsString("\$env['MAIL_PASSWORD'] ?? ''", $source);
        $this->assertStringContainsString("Cache::add('system:mail-config:test-cooldown:'", $source);
        $this->assertStringContainsString("Cache::lock('system:mail-config:test:'", $source);
        $this->assertStringContainsString("Cache::lock('system:mail-config:update'", $source);
        $this->assertStringContainsString("Artisan::call('config:clear')", $source);
        $this->assertStringNotContainsString('MAIL_PASSWORD' . "' => \$candidate['MAIL_PASSWORD']", $source);
    }

    public function test_mail_service_restores_runtime_config_and_redacts_transport_error(): void
    {
        $source = file_get_contents(base_path('Modules/System/Services/Env/MailConfigService.php'));

        $this->assertStringContainsString('finally', $source);
        $this->assertStringContainsString("Config::set(\$key, \$value)", $source);
        $this->assertStringContainsString("purge('smtp')", $source);
        $this->assertStringNotContainsString('$e->getMessage()', $source);
        $this->assertStringContainsString('Không thể gửi email kiểm tra.', $source);
    }

    public function test_blade_has_write_only_secret_guidance_validation_confirmation_and_read_only_state(): void
    {
        $blade = file_get_contents(base_path('Modules/System/resources/views/livewire/settings/mail-config.blade.php'));

        $this->assertStringContainsString('Để trống để giữ mật khẩu SMTP hiện tại', $blade);
        $this->assertStringContainsString('Mật khẩu hiện tại không được tải về trình duyệt', $blade);
        $this->assertStringContainsString('wire:confirm=', $blade);
        $this->assertStringContainsString('system.env.update', $blade);
        $this->assertStringContainsString('<option value="smtp">', $blade);
        $this->assertStringContainsString('<option value="none">', $blade);
        $this->assertStringContainsString("@error('testEmail')", $blade);
        $this->assertStringContainsString('wire:target="save"', $blade);
        $this->assertStringContainsString('wire:target="sendTest"', $blade);
    }

    public function test_no_duplicate_mail_route_or_menu_is_introduced(): void
    {
        $routes = collect(Route::getRoutes())->filter(fn ($route) => str_contains($route->uri(), 'mail-config'));
        $this->assertCount(0, $routes);

        $menus = json_decode(file_get_contents(base_path('Modules/Admin/data/menus.json')), true, flags: JSON_THROW_ON_ERROR);
        $json = json_encode($menus, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('/admin/system/settings/mail', $json);
    }
}
