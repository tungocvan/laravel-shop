<?php

namespace Tests\Feature\System;

use App\Modules\ModuleLifecycleManager;
use App\Modules\ModulePermissionManager;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use LogicException;
use Modules\System\Services\SystemModuleControlService;
use RuntimeException;
use Tests\TestCase;

class SystemModulesControlTest extends TestCase
{
    private string $fixtureRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtureRoot = storage_path('framework/testing/system-module-control');
        File::deleteDirectory($this->fixtureRoot);
        File::ensureDirectoryExists($this->fixtureRoot.'/config');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->fixtureRoot);
        parent::tearDown();
    }

    public function test_modules_route_and_admin_menu_use_view_permission(): void
    {
        $route = Route::getRoutes()->getByName('admin.system.modules');

        $this->assertNotNull($route);
        $this->assertSame('admin/system/modules', $route->uri());
        $this->assertContains('auth:admin', $route->gatherMiddleware());
        $this->assertContains('permission:system.modules.view,admin', $route->gatherMiddleware());

        $menus = json_decode(
            file_get_contents(base_path('Modules/Admin/data/menus.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
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
            $this->assertStringContainsString("authorizePermission('system.modules.update')", $methodSource, "{$method} must authorize update permission.");
        }

        $this->assertStringNotContainsString('updateModuleManifest(', $source);
        $this->assertStringNotContainsString('File::put(', $source);
    }

    public function test_required_module_cannot_be_disabled(): void
    {
        $this->writeManifest(true);
        config(['modules.registry.RequiredDemo' => $this->moduleConfig(enabled: true, required: true)]);

        $lifecycle = $this->mock(ModuleLifecycleManager::class);
        $permissions = $this->mock(ModulePermissionManager::class);
        $lifecycle->shouldNotReceive('migrateIfNeeded');
        $permissions->shouldNotReceive('sync');

        $service = new SystemModuleControlService($lifecycle, $permissions);

        $this->expectException(LogicException::class);
        $service->toggle('RequiredDemo', 1);
    }

    public function test_disabled_dependency_blocks_enable_before_migration(): void
    {
        $this->writeManifest(false);
        config([
            'modules.registry.Dependency' => [
                'name' => 'Dependency',
                'type' => 'support',
                'enabled' => false,
                'required' => false,
                'depends' => [],
                'path' => $this->fixtureRoot,
                'source' => 'manifest',
            ],
            'modules.registry.Demo' => $this->moduleConfig(enabled: false, depends: ['Dependency']),
        ]);

        $lifecycle = $this->mock(ModuleLifecycleManager::class);
        $permissions = $this->mock(ModulePermissionManager::class);
        $lifecycle->shouldNotReceive('migrateIfNeeded');
        $permissions->shouldNotReceive('sync');

        $service = new SystemModuleControlService($lifecycle, $permissions);

        $this->expectException(LogicException::class);
        $service->toggle('Demo', 1);
    }

    public function test_enabled_dependent_blocks_disable(): void
    {
        $this->writeManifest(true);
        config([
            'modules.registry.Demo' => $this->moduleConfig(enabled: true),
            'modules.registry.Consumer' => [
                'name' => 'Consumer',
                'type' => 'domain',
                'enabled' => true,
                'required' => false,
                'depends' => ['Demo'],
                'path' => $this->fixtureRoot,
                'source' => 'manifest',
            ],
        ]);

        $lifecycle = $this->mock(ModuleLifecycleManager::class);
        $permissions = $this->mock(ModulePermissionManager::class);
        $service = new SystemModuleControlService($lifecycle, $permissions);

        $this->expectException(LogicException::class);
        $service->toggle('Demo', 1);
    }

    public function test_failed_migration_does_not_sync_permissions_or_enable_manifest(): void
    {
        $this->writeManifest(false);
        config(['modules.registry.Demo' => $this->moduleConfig(enabled: false)]);

        $lifecycle = $this->mock(ModuleLifecycleManager::class);
        $permissions = $this->mock(ModulePermissionManager::class);
        $lifecycle->shouldReceive('migrateIfNeeded')->once()->andThrow(new RuntimeException('migration failed'));
        $permissions->shouldNotReceive('sync');

        $service = new SystemModuleControlService($lifecycle, $permissions);

        try {
            $service->toggle('Demo', 1);
            $this->fail('Expected migration failure.');
        } catch (RuntimeException) {
            $this->assertFalse((bool) ((require $this->fixtureRoot.'/config/module.php')['enabled'] ?? true));
        }
    }

    public function test_failed_permission_sync_does_not_enable_manifest(): void
    {
        $this->writeManifest(false);
        config(['modules.registry.Demo' => $this->moduleConfig(enabled: false)]);

        $lifecycle = $this->mock(ModuleLifecycleManager::class);
        $permissions = $this->mock(ModulePermissionManager::class);
        $lifecycle->shouldReceive('migrateIfNeeded')->once()->andReturn(['migrated' => false]);
        $permissions->shouldReceive('sync')->once()->andThrow(new RuntimeException('permission failed'));

        $service = new SystemModuleControlService($lifecycle, $permissions);

        try {
            $service->toggle('Demo', 1);
            $this->fail('Expected permission sync failure.');
        } catch (RuntimeException) {
            $this->assertFalse((bool) ((require $this->fixtureRoot.'/config/module.php')['enabled'] ?? true));
        }
    }

    public function test_successful_enable_writes_manifest_after_migration_and_permission_sync(): void
    {
        $this->writeManifest(false);
        config(['modules.registry.Demo' => $this->moduleConfig(enabled: false)]);

        $lifecycle = $this->mock(ModuleLifecycleManager::class);
        $permissions = $this->mock(ModulePermissionManager::class);
        $lifecycle->shouldReceive('migrateIfNeeded')->once()->andReturn(['migrated' => true]);
        $permissions->shouldReceive('sync')->once()->andReturn(3);

        $service = new SystemModuleControlService($lifecycle, $permissions);
        $result = $service->toggle('Demo', 1);

        $this->assertTrue($result['enabled']);
        $this->assertTrue($result['migrated']);
        $this->assertSame(3, $result['permission_count']);
        $this->assertTrue((bool) ((require $this->fixtureRoot.'/config/module.php')['enabled'] ?? false));
    }

    public function test_control_service_uses_per_module_lock_and_livewire_validates_route_title(): void
    {
        $serviceSource = file_get_contents(base_path('Modules/System/Services/SystemModuleControlService.php'));
        $livewireSource = file_get_contents(base_path('Modules/System/Livewire/Settings/ModulesForm.php'));
        $routeManagerSource = file_get_contents(base_path('Modules/Admin/Services/ModuleRouteManager.php'));

        $this->assertStringContainsString("Cache::lock(", $serviceSource);
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

    private function moduleConfig(bool $enabled, bool $required = false, array $depends = []): array
    {
        return [
            'name' => 'Demo',
            'type' => 'domain',
            'enabled' => $enabled,
            'required' => $required,
            'depends' => $depends,
            'path' => $this->fixtureRoot,
            'source' => 'manifest',
        ];
    }

    private function writeManifest(bool $enabled): void
    {
        File::put(
            $this->fixtureRoot.'/config/module.php',
            "<?php\n\nreturn ".var_export([
                'name' => 'Demo',
                'enabled' => $enabled,
                'permissions' => ['demo.view'],
            ], true).";\n"
        );
    }
}
