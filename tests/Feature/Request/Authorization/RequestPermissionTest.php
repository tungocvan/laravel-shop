<?php

namespace Tests\Feature\Request\Authorization;

use App\Modules\ModulePermissionManager;
use Tests\TestCase;

class RequestPermissionTest extends TestCase
{
    public function test_manifest_declares_the_exact_approved_permission_catalog(): void
    {
        $permissions = (require base_path('Modules/Request/config/module.php'))['permissions'];

        $this->assertCount(35, $permissions);
        $this->assertSame($permissions, array_values(array_unique($permissions)));
        $this->assertContains('request.type.audience.manage', $permissions);
        $this->assertContains('request.type.delete', $permissions);
        $this->assertContains('request.type.publish', $permissions);
        $this->assertContains('request.instance.submit', $permissions);
        $this->assertContains('request.instance.delete', $permissions);
        $this->assertContains('request.task.decide', $permissions);
        $this->assertContains('request.operation.retry', $permissions);
        $this->assertContains('request.operation.delete', $permissions);
        $this->assertTrue(collect($permissions)->every(fn (string $permission): bool => str_starts_with($permission, 'request.')));
    }

    public function test_permission_manager_includes_request_only_when_runtime_enabled(): void
    {
        $module = [
            'name' => 'Request',
            'type' => 'domain',
            'enabled' => false,
            'required' => false,
            'depends' => ['Admin', 'Auth', 'User', 'Role', 'Shared'],
            'path' => base_path('Modules/Request'),
            'source' => 'manifest',
        ];

        config(['modules.registry' => ['Request' => $module]]);
        $this->assertArrayNotHasKey('Request', app(ModulePermissionManager::class)->activeGroups());

        config(['modules.registry.Request.enabled' => true]);
        $this->assertSame(
            (require base_path('Modules/Request/config/module.php'))['permissions'],
            app(ModulePermissionManager::class)->activeGroups()['Request'],
        );
    }
}
