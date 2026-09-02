<?php

namespace Tests\Feature\System;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\System\Services\AdminLoginRedirectService;
use Tests\TestCase;

class AdminLandingBoundaryTest extends TestCase
{
    public function test_application_root_fallback_redirects_to_admin_dashboard(): void
    {
        $fallback = collect(Route::getRoutes()->getRoutes())
            ->first(static fn ($route): bool => $route->isFallback);

        $this->assertNotNull($fallback);

        $uses = $fallback->getAction('uses');
        $this->assertInstanceOf(\Closure::class, $uses);

        $response = $uses(Request::create('/', 'GET'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(route('admin.dashboard'), $response->getTargetUrl());
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
