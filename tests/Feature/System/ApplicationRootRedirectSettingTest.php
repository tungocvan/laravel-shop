<?php

namespace Tests\Feature\System;

use Illuminate\Support\Facades\Route;
use Modules\System\Services\ApplicationRootRedirectService;
use Modules\System\Services\SettingsService;
use Tests\TestCase;

class ApplicationRootRedirectSettingTest extends TestCase
{
    public function test_default_route_is_admin_entry_when_setting_is_missing(): void
    {
        $settings = $this->mock(SettingsService::class);
        $settings->shouldReceive('get')
            ->with(ApplicationRootRedirectService::SETTING_KEY, ApplicationRootRedirectService::DEFAULT_ROUTE)
            ->once()
            ->andReturn(null);

        $redirect = app(ApplicationRootRedirectService::class);

        $this->assertSame('admin.entry', $redirect->configuredRoute());
        $this->assertTrue($redirect->isAllowedRoute('admin.entry'));
        $this->assertArrayHasKey('admin.entry', $redirect->availableRoutes());
    }

    public function test_public_named_get_route_without_parameters_is_selectable(): void
    {
        Route::middleware('web')->get('/root-fallback-public-target', fn () => 'ok')->name('root.fallback.public');
        Route::getRoutes()->refreshNameLookups();

        $settings = $this->mock(SettingsService::class);
        $settings->shouldReceive('get')->once()->andReturn('root.fallback.public');

        $redirect = app(ApplicationRootRedirectService::class);

        $this->assertTrue($redirect->isAllowedRoute('root.fallback.public'));
        $this->assertSame('root.fallback.public', $redirect->configuredRoute());
        $this->assertStringContainsString('Công khai', $redirect->availableRoutes()['root.fallback.public']);
    }

    public function test_authenticated_route_remains_selectable_and_is_labeled(): void
    {
        Route::middleware(['web', 'auth:web'])
            ->get('/root-fallback-auth-target', fn () => 'ok')
            ->name('root.fallback.auth');
        Route::getRoutes()->refreshNameLookups();

        $settings = $this->mock(SettingsService::class);
        $settings->shouldReceive('get')->never();

        $redirect = app(ApplicationRootRedirectService::class);

        $this->assertTrue($redirect->isAllowedRoute('root.fallback.auth'));
        $this->assertStringContainsString('Yêu cầu đăng nhập', $redirect->availableRoutes()['root.fallback.auth']);
    }

    public function test_root_route_is_not_selectable_to_prevent_redirect_loop(): void
    {
        Route::middleware('web')->get('/', fn () => 'root')->name('root.fallback.loop');
        Route::getRoutes()->refreshNameLookups();

        $settings = $this->mock(SettingsService::class);
        $settings->shouldReceive('get')->never();

        $redirect = app(ApplicationRootRedirectService::class);

        $this->assertFalse($redirect->isAllowedRoute('root.fallback.loop'));
        $this->assertArrayNotHasKey('root.fallback.loop', $redirect->availableRoutes());
    }

    public function test_post_and_parameterized_routes_are_not_selectable(): void
    {
        Route::middleware('web')->post('/root-fallback-post', fn () => 'ok')->name('root.fallback.post');
        Route::middleware('web')->get('/root-fallback-param/{id}', fn (string $id) => $id)->name('root.fallback.param');
        Route::getRoutes()->refreshNameLookups();

        $settings = $this->mock(SettingsService::class);
        $settings->shouldReceive('get')->never();

        $redirect = app(ApplicationRootRedirectService::class);

        $this->assertFalse($redirect->isAllowedRoute('root.fallback.post'));
        $this->assertFalse($redirect->isAllowedRoute('root.fallback.param'));
        $this->assertArrayNotHasKey('root.fallback.post', $redirect->availableRoutes());
        $this->assertArrayNotHasKey('root.fallback.param', $redirect->availableRoutes());
    }

    public function test_removed_configured_route_falls_back_to_admin_entry(): void
    {
        $settings = $this->mock(SettingsService::class);
        $settings->shouldReceive('get')->once()->andReturn('root.fallback.missing');

        $redirect = app(ApplicationRootRedirectService::class);

        $this->assertSame('admin.entry', $redirect->configuredRoute());
    }
}
