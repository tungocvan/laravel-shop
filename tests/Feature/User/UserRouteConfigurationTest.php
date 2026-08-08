<?php

namespace Tests\Feature\User;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class UserRouteConfigurationTest extends TestCase
{
    public function test_user_admin_routes_keep_expected_names_and_permission_middleware(): void
    {
        $this->assertRouteHasMiddleware('admin.user.index', 'permission:view_user,admin');
        $this->assertRouteHasMiddleware('admin.user.create', 'permission:create_user,admin');
        $this->assertRouteHasMiddleware('admin.user.edit', 'permission:edit_user,admin');
    }

    public function test_user_module_declares_import_export_permissions(): void
    {
        $permissions = config('user.module.permissions', []);

        $this->assertContains('import_user', $permissions);
        $this->assertContains('export_user', $permissions);
    }

    private function assertRouteHasMiddleware(string $name, string $middleware): void
    {
        $route = Route::getRoutes()->getByName($name);

        $this->assertNotNull($route, "Route [{$name}] was not registered.");
        $this->assertContains('auth:admin', $route->gatherMiddleware());
        $this->assertContains($middleware, $route->gatherMiddleware());
    }
}
