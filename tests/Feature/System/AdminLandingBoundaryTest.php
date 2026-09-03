<?php

namespace Tests\Feature\System;

use App\Models\User;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\System\Services\AdminLoginRedirectService;
use Modules\System\Services\ApplicationRootRedirectService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class AdminLandingBoundaryTest extends TestCase
{
    public function test_application_root_fallback_redirects_to_configured_target(): void
    {
        Route::middleware('web')->get('/configured-root-target', fn () => 'ok')->name('root.configured-target');
        Route::getRoutes()->refreshNameLookups();

        $this->mock(ApplicationRootRedirectService::class, function ($mock): void {
            $mock->shouldReceive('configuredRoute')
                ->once()
                ->andReturn('root.configured-target');
        });

        $fallback = collect(Route::getRoutes()->getRoutes())
            ->first(static fn ($route): bool => $route->isFallback);

        $this->assertNotNull($fallback);

        $uses = $fallback->getAction('uses');
        $this->assertInstanceOf(Closure::class, $uses);

        $response = $uses(Request::create('/', 'GET'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(route('root.configured-target'), $response->getTargetUrl());
    }

    public function test_non_root_fallback_still_returns_not_found(): void
    {
        $fallback = collect(Route::getRoutes()->getRoutes())
            ->first(static fn ($route): bool => $route->isFallback);

        $this->assertNotNull($fallback);

        $uses = $fallback->getAction('uses');
        $this->assertInstanceOf(Closure::class, $uses);

        $this->expectException(NotFoundHttpException::class);

        $uses(Request::create('/missing-page', 'GET'));
    }

    public function test_authenticated_admin_login_entry_honors_configured_landing(): void
    {
        $user = new User;
        $user->id = 2001;

        $this->mock(AdminLoginRedirectService::class, function ($mock): void {
            $mock->shouldReceive('configuredRoute')
                ->once()
                ->andReturn('admin.profile');
        });

        $this->actingAs($user, 'admin')
            ->get('/admin/login')
            ->assertRedirect(route('admin.profile'));
    }

    public function test_authenticated_admin_login_entry_falls_back_to_admin_dashboard(): void
    {
        $user = new User;
        $user->id = 2002;

        $this->mock(AdminLoginRedirectService::class, function ($mock): void {
            $mock->shouldReceive('configuredRoute')
                ->once()
                ->andReturn(AdminLoginRedirectService::DEFAULT_ROUTE);
        });

        $this->actingAs($user, 'admin')
            ->get('/admin/login')
            ->assertRedirect(route('admin.dashboard'));
    }
}
