<?php

namespace Tests\Feature\Admin;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminRouteConfigurationTest extends TestCase
{
    public function test_admin_api_stub_is_not_registered(): void
    {
        $route = collect(Route::getRoutes())->first(function ($route): bool {
            return $route->uri() === 'api/admin' && in_array('GET', $route->methods(), true);
        });

        $this->assertNull($route);
    }

    #[DataProvider('adminRoutesProvider')]
    public function test_active_admin_routes_enforce_named_permissions(string $routeName, string $uri, string $permission): void
    {
        $route = Route::getRoutes()->getByName($routeName);

        $this->assertNotNull($route);
        $this->assertSame($uri, $route->uri());
        $this->assertContains('auth:admin', $route->gatherMiddleware());
        $this->assertContains("permission:{$permission},admin", $route->gatherMiddleware());
    }

    public static function adminRoutesProvider(): array
    {
        return [
            ['admin.dashboard', 'admin', 'admin.dashboard.view'],
            ['admin.menus.index', 'admin/menus', 'admin.menu.view'],
            ['admin.menus.create', 'admin/menus/create', 'admin.menu.create'],
            ['admin.menus.edit', 'admin/menus/{id}/edit', 'admin.menu.update'],
            ['admin.profile', 'admin/profile', 'admin.profile.view'],
            ['admin.themes', 'admin/themes', 'admin.theme.view'],
            ['admin.layout', 'admin/layout', 'admin.layout.view'],
            ['admin.header', 'admin/admin-header', 'admin.header.view'],
        ];
    }
}
