<?php

namespace Tests\Feature\System;

use Illuminate\Support\Facades\Route;
use Modules\System\Services\AdminLoginRedirectService;
use Modules\System\Services\SettingsService;
use Tests\TestCase;

class AdminLoginRedirectSettingTest extends TestCase
{
    public function test_default_route_prefers_registered_root_route_when_setting_is_missing(): void
    {
        $settings = $this->mock(SettingsService::class);
        $settings->shouldReceive('get')
            ->with(AdminLoginRedirectService::SETTING_KEY, AdminLoginRedirectService::DEFAULT_ROUTE)
            ->once()
            ->andReturn(null);

        $redirect = app(AdminLoginRedirectService::class);

        $this->assertSame('home', $redirect->configuredRoute());
        $this->assertTrue($redirect->isAllowedRoute('home'));
        $this->assertArrayHasKey('home', $redirect->availableRoutes());
        $this->assertSame('Trang gốc — /', $redirect->availableRoutes()['home']);
    }

    public function test_valid_named_admin_get_route_remains_selectable_for_backward_compatibility(): void
    {
        Route::middleware('web')->get('/admin/test-login-target', fn () => 'ok')->name('admin.test-login-target');
        Route::getRoutes()->refreshNameLookups();

        $settings = $this->mock(SettingsService::class);
        $settings->shouldReceive('get')->once()->andReturn('admin.test-login-target');

        $redirect = app(AdminLoginRedirectService::class);

        $this->assertTrue($redirect->isAllowedRoute('admin.test-login-target'));
        $this->assertSame('admin.test-login-target', $redirect->configuredRoute());
        $this->assertArrayHasKey('admin.test-login-target', $redirect->availableRoutes());
    }

    public function test_invalid_non_root_public_route_falls_back_to_root_route(): void
    {
        Route::middleware('web')->get('/public-test-login-target', fn () => 'ok')->name('public.test-login-target');
        Route::getRoutes()->refreshNameLookups();

        $settings = $this->mock(SettingsService::class);
        $settings->shouldReceive('get')->once()->andReturn('public.test-login-target');

        $redirect = app(AdminLoginRedirectService::class);

        $this->assertFalse($redirect->isAllowedRoute('public.test-login-target'));
        $this->assertSame('home', $redirect->configuredRoute());
    }

    public function test_parameterized_admin_route_is_not_selectable(): void
    {
        Route::middleware('web')->get('/admin/test-login-target/{id}', fn (string $id) => $id)->name('admin.test-login-target.show');
        Route::getRoutes()->refreshNameLookups();

        $settings = $this->mock(SettingsService::class);
        $settings->shouldReceive('get')->never();

        $redirect = app(AdminLoginRedirectService::class);

        $this->assertFalse($redirect->isAllowedRoute('admin.test-login-target.show'));
        $this->assertArrayNotHasKey('admin.test-login-target.show', $redirect->availableRoutes());
    }
}
