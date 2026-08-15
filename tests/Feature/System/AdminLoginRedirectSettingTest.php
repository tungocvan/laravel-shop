<?php

namespace Tests\Feature\System;

use Illuminate\Support\Facades\Route;
use Modules\System\Services\AdminLoginRedirectService;
use Modules\System\Services\SettingsService;
use Tests\TestCase;

class AdminLoginRedirectSettingTest extends TestCase
{
    public function test_default_route_is_admin_dashboard_when_setting_is_missing(): void
    {
        $settings = $this->mock(SettingsService::class);
        $settings->shouldReceive('get')
            ->with(AdminLoginRedirectService::SETTING_KEY, AdminLoginRedirectService::DEFAULT_ROUTE)
            ->once()
            ->andReturn(null);

        $this->assertSame(
            AdminLoginRedirectService::DEFAULT_ROUTE,
            app(AdminLoginRedirectService::class)->configuredRoute()
        );
    }

    public function test_valid_named_admin_get_route_can_be_selected(): void
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

    public function test_invalid_or_non_admin_route_falls_back_to_admin_dashboard(): void
    {
        Route::middleware('web')->get('/public-test-login-target', fn () => 'ok')->name('public.test-login-target');
        Route::getRoutes()->refreshNameLookups();

        $settings = $this->mock(SettingsService::class);
        $settings->shouldReceive('get')->once()->andReturn('public.test-login-target');

        $redirect = app(AdminLoginRedirectService::class);

        $this->assertFalse($redirect->isAllowedRoute('public.test-login-target'));
        $this->assertSame(AdminLoginRedirectService::DEFAULT_ROUTE, $redirect->configuredRoute());
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
