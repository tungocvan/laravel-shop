<?php

namespace Tests\Feature\Role;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleRouteAuthorizationTest extends TestCase
{
    #[DataProvider('roleRoutesProvider')]
    public function test_role_routes_enforce_admin_guard_and_named_permissions(
        string $routeName,
        string $uri,
        string $permission,
    ): void {
        $route = Route::getRoutes()->getByName($routeName);

        $this->assertNotNull($route);
        $this->assertSame($uri, $route->uri());
        $this->assertContains('auth:admin', $route->gatherMiddleware());
        $this->assertContains("permission:{$permission},admin", $route->gatherMiddleware());
    }

    public function test_role_edit_route_requires_numeric_id(): void
    {
        $route = Route::getRoutes()->getByName('admin.role.edit');

        $this->assertNotNull($route);
        $this->assertSame('[0-9]+', $route->wheres['id'] ?? null);
    }

    public static function roleRoutesProvider(): array
    {
        return [
            ['admin.role.index', 'admin/roles', 'view_role'],
            ['admin.role.create', 'admin/roles/create', 'create_role'],
            ['admin.role.edit', 'admin/roles/{id}/edit', 'edit_role'],
        ];
    }
}
