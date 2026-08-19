<?php

namespace Tests\Feature\ClientApps;

use App\Services\ClientApplicationPermissionService;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ClientApplicationAdminTest extends TestCase
{
    public function test_permission_definitions_are_built_from_application_manifest(): void
    {
        $definitions = app(ClientApplicationPermissionService::class)->definitions();

        $this->assertTrue($definitions->contains('name', 'client.muasamcong.access'));
        $this->assertTrue($definitions->contains('name', 'client.muasamcong.drug-pricing.view'));
        $this->assertTrue($definitions->contains('name', 'client.muasamcong.history.view'));
        $this->assertTrue($definitions->contains('name', 'client.muasamcong.wishlist.view'));
        $this->assertTrue($definitions->contains('name', 'client.muasamcong.contractors.view'));
        $this->assertTrue($definitions->contains('name', 'client.muasamcong.analytics.view'));
    }

    public function test_client_application_admin_routes_are_protected_by_admin_guard_and_named_permissions(): void
    {
        $expectations = [
            'admin.client-apps.index' => ['admin/client-apps', 'permission:view_role,admin'],
            'admin.client-apps.sync-permissions' => ['admin/client-apps/sync-permissions', 'permission:edit_role,admin'],
            'admin.client-apps.sync-super-admin' => ['admin/client-apps/sync-super-admin', 'permission:edit_role,admin'],
            'admin.client-apps.users.edit' => ['admin/client-apps/users/{user}', 'permission:edit_user,admin'],
            'admin.client-apps.users.update' => ['admin/client-apps/users/{user}', 'permission:edit_user,admin'],
            'admin.client-apps.roles.edit' => ['admin/client-apps/roles/{role}', 'permission:edit_role,admin'],
            'admin.client-apps.roles.update' => ['admin/client-apps/roles/{role}', 'permission:edit_role,admin'],
        ];

        foreach ($expectations as $name => [$uri, $permission]) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route, $name);
            $this->assertSame($uri, $route->uri(), $name);
            $this->assertContains('auth:admin', $route->gatherMiddleware(), $name);
            $this->assertContains($permission, $route->gatherMiddleware(), $name);
        }
    }
}
