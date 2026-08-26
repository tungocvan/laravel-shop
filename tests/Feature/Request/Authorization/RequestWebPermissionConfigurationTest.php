<?php

namespace Tests\Feature\Request\Authorization;

use App\Modules\ModulePermissionManager;
use ReflectionMethod;
use Tests\TestCase;

class RequestWebPermissionConfigurationTest extends TestCase
{
    public function test_request_module_declares_client_and_requester_permissions_for_web_guard(): void
    {
        $config = require base_path('Modules/Request/config/module.php');
        $webPermissions = $config['permissions_by_guard']['web'] ?? [];

        $this->assertContains('client.request.access', $webPermissions);
        $this->assertContains('client.request.create.view', $webPermissions);
        $this->assertContains('client.request.mine.view', $webPermissions);
        $this->assertContains('request.instance.view-own', $webPermissions);
        $this->assertContains('request.instance.create', $webPermissions);
        $this->assertContains('request.instance.update-own', $webPermissions);
        $this->assertContains('request.instance.submit', $webPermissions);
        $this->assertContains('request.instance.cancel-own', $webPermissions);
        $this->assertContains('request.comment.create', $webPermissions);
        $this->assertContains('request.attachment.upload', $webPermissions);
        $this->assertContains('request.attachment.download', $webPermissions);
    }

    public function test_module_permission_manager_preserves_admin_permissions_and_discovers_web_permissions(): void
    {
        $method = new ReflectionMethod(ModulePermissionManager::class, 'permissionsByGuardFromPath');
        $permissionsByGuard = $method->invoke(app(ModulePermissionManager::class), base_path('Modules/Request'));

        $this->assertContains('request.dashboard.view', $permissionsByGuard['admin']);
        $this->assertContains('request.instance.submit', $permissionsByGuard['admin']);
        $this->assertContains('client.request.access', $permissionsByGuard['web']);
        $this->assertContains('request.instance.submit', $permissionsByGuard['web']);
        $this->assertNotContains('request.dashboard.view', $permissionsByGuard['web']);
    }
}
