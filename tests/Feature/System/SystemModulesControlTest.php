<?php

namespace Tests\Feature\System;

use App\Modules\ModuleLifecycleManager;
use App\Modules\ModulePermissionManager;
use App\Modules\ModuleStateRepository;
use Illuminate\Support\Facades\Route;
use LogicException;
use Modules\System\Services\SystemModuleControlService;
use RuntimeException;
use Tests\TestCase;

class SystemModulesControlTest extends TestCase
{
    public function test_modules_route_and_admin_menu_use_view_permission(): void
    {
        $route = Route::getRoutes()->getByName('admin.system.modules');

        $this->assertNotNull($route);
        $this->assertSame('admin/system/modules', $route->uri());
        $this->assertContains('auth:admin', $route->gatherMiddleware());
        $this->assertContains('permission:system.modules.view,admin', $route->gatherMiddleware());

        $menus = json_decode(file_get_contents(base_path('Modules/Admin/data/menus.json')), true, flags: JSON_THROW_ON_ERROR);
        $systemMenu = collect($menus)->firstWhere('name', 'Công cụ Hệ thống');
        $modulesMenu = collect($systemMenu['children'] ?? [])->firstWhere('url', '/admin/system/modules');

        $this->assertNotNull($modulesMenu);
        $this->assertSame('Quản lý Modules', $modulesMenu['name']);
        $this->assertSame('system.modules.view', $modulesMenu['can']);
    }

    public function test_modules_form_enforces_update_permission_on_every_mutation(): void
    {
        $source = file_get_contents(base_path('Modules/System/Livewire/Settings/ModulesForm.php'));

        $this->assertStringContainsString('AuthorizesSystemActions', $source);

        foreach (['toggleRealtime', 'toggleModule', 'deleteModule', 'saveRouteTitle', 'addRouteToMenu'] as $method) {
            $start = strpos($source, 'function '.$method.'(');
            $this->assertNotFalse($start, "Missing {$method} method.");
            $next = strpos($source, '\n    public function ', $start + 1);
            $methodSource = substr($source, $start, $next === false ? null : $next - $start);
            $this->assertStringContainsString("authorizePermission('system.modules.update')", $methodSource);
        }

        $this->assertStringNotContainsString('updateModuleManifest(', $source);
        $this->assertStringNotContainsString('File::put(', $source);
    }

    public function test_required_module_cannot_be_disabled(): void
    {
        config(['modules.registry.RequiredDemo' => $this->moduleConfig(true, true)]);

        [$service, $lifecycle, $permissions, $states] = $this->service();
        $lifecycle->shouldNotReceive('migrateIfNeeded');
        $permissions->shouldNotReceive('sync');
        $states->shouldNotReceive('set');

        $this->expectException(LogicException::class);
        $service->toggle('RequiredDemo', 1);
    }

    public function test_disabled_dependency_blocks_enable_before_migration(): void
    {
        config([
            'modules.registry.Dependency' => $this->moduleConfig(false, false, [], 'Dependency'),
            'modules.registry.Demo' => $this->moduleConfig(false, false, ['Dependency']),
        ]);

        [$service, $lifecycle, $permissions, $states] = $this->service();
        $lifecycle->shouldNotReceive('migrateIfNeeded');
        $permissions->shouldNotReceive('sync');
        $states->shouldNotReceive('set');

        $this->expectException(LogicException::class);
        $service->toggle('Demo', 1);
    }

    public function test_enabled_dependent_blocks_disable(): void
    {
        config([
            'modules.registry.Demo' => $this->moduleConfig(true),
            'modules.registry.Consumer' => $this->moduleConfig(true, false, ['Demo'], 'Consumer'),
        ]);

        [$service, , , $states] = $this->service();
        $states->shouldNotReceive('set');

        $this->expectException(LogicException::class);
        $service->toggle('Demo', 1);
    }

    public function test_failed_migration_does_not_persist_runtime_state(): void
    {
        config(['modules.registry.Demo' => $this->moduleConfig(false)]);

        [$service, $lifecycle, $permissions, $states] = $this->service();
        $lifecycle->shouldReceive('migrateIfNeeded')->once()->andThrow(new RuntimeException('migration failed'));
        $permissions->shouldNotReceive('sync');
        $states->shouldNotReceive('set');

        $this->expectException(RuntimeException::class);
        $service->toggle('Demo', 1);
    }

    public function test_failed_permission_sync_does_not_persist_runtime_state(): void
    {
        config(['modules.registry.Demo' => $this->moduleConfig(false)]);

        [$service, $lifecycle, $permissions, $states] = $this->service();
        $lifecycle->shouldReceive('migrateIfNeeded')->once()->andReturn(['migrated' => false]);
        $permissions->shouldReceive('sync')->once()->andThrow(new RuntimeException('permission failed'));
        $states->shouldNotReceive('set');

        $this->expectException(RuntimeException::class);
        $service->toggle('Demo', 1);
    }

    public function test_successful_enable_writes_runtime_state_after_migration_and_permission_sync(): void
    {
        config(['modules.registry.Demo' => $this->moduleConfig(false)]);

        [$service, $lifecycle, $permissions, $states] = $this->service();
        $lifecycle->shouldReceive('migrateIfNeeded')->once()->andReturn(['migrated' => true]);
        $permissions->shouldReceive('sync')->once()->andReturn(3);
        $states->shouldReceive('set')->once()->with('Demo', true);

        $result = $service->toggle('Demo', 1);

        $this->assertTrue($result['enabled']);
        $this->assertTrue($result['migrated']);
        $this->assertSame(3, $result['permission_count']);
        $this->assertTrue(config('modules.registry.Demo.enabled'));
        $this->assertSame('runtime', config('modules.registry.Demo.source'));
    }

    public function test_control_service_no_longer_writes_module_manifests(): void
    {
        $source = file_get_contents(base_path('Modules/System/Services/SystemModuleControlService.php'));

        $this->assertStringContainsString('ModuleStateRepository', $source);
        $this->assertStringContainsString("->set(\$moduleName, \$newEnabled)", $source);
        $this->assertStringNotContainsString('writeEnabledManifest', $source);
        $this->assertStringNotContainsString('File::put(', $source);
    }

    public function test_control_service_uses_per_module_lock_and_livewire_validates_route_title(): void
    {
        $serviceSource = file_get_contents(base_path('Modules/System/Services/SystemModuleControlService.php'));
        $livewireSource = file_get_contents(base_path('Modules/System/Livewire/Settings/ModulesForm.php'));
        $routeManagerSource = file_get_contents(base_path('Modules/Admin/Services/ModuleRouteManager.php'));

        $this->assertStringContainsString('Cache::lock(', $serviceSource);
        $this->assertStringContainsString("'system:module-control:'", $serviceSource);
        $this->assertStringContainsString("'routeTitle' => ['required', 'string', 'max:255']", $livewireSource);
        $this->assertStringContainsString("->pluck('url')", $routeManagerSource);
        $this->assertStringContainsString('normalizeMenuUrl', $routeManagerSource);
    }

    public function test_browser_messages_do_not_append_raw_internal_exceptions(): void
    {
        $source = file_get_contents(base_path('Modules/System/Livewire/Settings/ModulesForm.php'));

        $this->assertStringNotContainsString("'Không thể cập nhật realtime: ' . \$e->getMessage()", $source);
        $this->assertStringNotContainsString("session()->flash('error', \$e->getMessage())", $source);
        $this->assertStringContainsString('Vui lòng kiểm tra log hệ thống.', $source);
    }

    private function service(): array
    {
        $lifecycle = $this->mock(ModuleLifecycleManager::class);
        $permissions = $this->mock(ModulePermissionManager::class);
        $states = $this->mock(ModuleStateRepository::class);

        return [new SystemModuleControlService($lifecycle, $permissions, $states), $lifecycle, $permissions, $states];
    }

    private function moduleConfig(bool $enabled, bool $required = false, array $depends = [], string $name = 'Demo'): array
    {
        return [
            'name' => $name,
            'type' => $required ? 'shell' : 'domain',
            'enabled' => $enabled,
            'required' => $required,
            'depends' => $depends,
            'path' => base_path('Modules/'.$name),
            'source' => 'manifest',
        ];
    }
}
