<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Support\Facades\Route;
use Modules\ClientPortal\Services\ApplicationPermissionService;
use Tests\TestCase;

class ClientApplicationAdminTest extends TestCase
{
    public function test_permission_definitions_include_application_and_domain_web_permissions(): void
    {
        $definitions = app(ApplicationPermissionService::class)->definitions();

        $this->assertTrue($definitions->contains('name', 'client.muasamcong.access'));
        $this->assertTrue($definitions->contains('name', 'client.muasamcong.drug-pricing.view'));
        $this->assertTrue($definitions->contains('name', 'client.muasamcong.drug-pricing.sync'));
        $this->assertTrue($definitions->contains('name', 'client.muasamcong.history.view'));
        $this->assertTrue($definitions->contains('name', 'client.muasamcong.wishlist.view'));
        $this->assertTrue($definitions->contains('name', 'client.muasamcong.contractors.view'));
        $this->assertTrue($definitions->contains('name', 'client.muasamcong.analytics.view'));

        $requestCreate = $definitions->firstWhere('name', 'request.instance.create');
        $this->assertNotNull($requestCreate);
        $this->assertSame('domain', $requestCreate['source']);
        $this->assertSame('Request', $requestCreate['group']);
        $this->assertTrue($definitions->contains('name', 'request.instance.submit'));
        $this->assertTrue($definitions->contains('name', 'request.comment.create'));
        $this->assertFalse($definitions->contains('name', 'request.dashboard.view'));
    }

    public function test_web_permission_admin_contract_preserves_guard_boundary(): void
    {
        $service = file_get_contents(base_path('Modules/ClientPortal/Services/ApplicationPermissionService.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Http/Controllers/Admin/ApplicationAdminController.php'));
        $userView = file_get_contents(base_path('Modules/ClientPortal/resources/views/admin/user-permissions.blade.php'));
        $roleView = file_get_contents(base_path('Modules/ClientPortal/resources/views/admin/role-permissions.blade.php'));

        $this->assertStringContainsString("data_get(\$config, 'permissions_by_guard.web'", $service);
        $this->assertStringContainsString("where('guard_name', 'web')", $controller);
        $this->assertStringContainsString("abort_unless(\$role->guard_name === 'web'", $controller);
        $this->assertStringContainsString("intersect(\$managed)", $controller);
        $this->assertStringContainsString('Quyền nghiệp vụ Domain', $userView);
        $this->assertStringContainsString('Quyền nghiệp vụ Domain', $roleView);
        $this->assertStringContainsString('quyền guard admin không bị thay đổi', $userView);
        $this->assertStringContainsString('quyền guard admin không bị thay đổi', $roleView);
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
