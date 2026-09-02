<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Services\LoginPresentationService;
use Modules\System\Services\SettingsService;
use Tests\TestCase;

class LoginThemePresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_client_login_presentations_are_independently_configurable(): void
    {
        $settings = app(SettingsService::class);
        $settings->updateMany([
            'auth_login_admin_theme' => 'hero-overlay',
            'auth_login_admin_title_line_2' => 'Admin Portal',
            'auth_login_admin_primary_color' => '#123456',
            'auth_login_admin_show_google' => false,
            'auth_login_client_theme' => 'minimal',
            'auth_login_client_title_line_2' => 'Client Portal',
            'auth_login_client_primary_color' => '#654321',
            'auth_login_client_show_google' => true,
        ], 'auth_login');

        $service = app(LoginPresentationService::class);
        $admin = $service->forGuard('admin');
        $client = $service->forGuard('web');

        $this->assertSame('hero-overlay', $admin['theme']);
        $this->assertSame('Admin Portal', $admin['title_line_2']);
        $this->assertSame('#123456', $admin['primary_color']);
        $this->assertFalse($admin['show_google']);

        $this->assertSame('minimal', $client['theme']);
        $this->assertSame('Client Portal', $client['title_line_2']);
        $this->assertSame('#654321', $client['primary_color']);
        $this->assertTrue($client['show_google']);
    }

    public function test_invalid_theme_color_and_opacity_fall_back_to_safe_values(): void
    {
        $settings = app(SettingsService::class);
        $settings->updateMany([
            'auth_login_admin_theme' => 'unknown-theme',
            'auth_login_admin_primary_color' => 'not-a-color',
            'auth_login_admin_overlay_opacity' => 150,
        ], 'auth_login');

        $presentation = app(LoginPresentationService::class)->forGuard('admin');

        $this->assertSame('classic-card', $presentation['theme']);
        $this->assertSame('#0f172a', $presentation['primary_color']);
        $this->assertSame(90, $presentation['overlay_opacity']);
    }

    public function test_admin_login_renders_configured_branding_without_changing_auth_route_contract(): void
    {
        app(SettingsService::class)->updateMany([
            'auth_login_admin_theme' => 'split-brand',
            'auth_login_admin_title_line_1' => 'INTERNAL SYSTEM',
            'auth_login_admin_title_line_2' => 'Administration',
            'auth_login_admin_description' => 'Secure administration portal',
            'auth_login_admin_primary_color' => '#112233',
            'auth_login_admin_show_google' => false,
        ], 'auth_login');

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('INTERNAL SYSTEM')
            ->assertSee('Administration')
            ->assertSee('Secure administration portal')
            ->assertDontSee('Đăng nhập bằng Google Workspace');

        $this->assertSame('/admin/login', route('admin.login', absolute: false));
    }
}
