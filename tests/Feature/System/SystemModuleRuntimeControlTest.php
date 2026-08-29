<?php

namespace Tests\Feature\System;

use App\Modules\ModuleCatalog;
use App\Modules\ModuleGraphValidator;
use App\Modules\ModuleLifecycleManager;
use App\Modules\ModulePermissionManager;
use App\Modules\ModuleRegistry;
use App\Modules\ModuleStateRepository;
use LogicException;
use Modules\System\Services\SystemModuleControlService;
use RuntimeException;
use Tests\TestCase;

class SystemModuleRuntimeControlTest extends TestCase
{
    public function test_successful_enable_persists_runtime_state_and_updates_current_registry(): void
    {
        config(['modules.registry.Demo' => $this->moduleConfig(false)]);

        $lifecycle = $this->mock(ModuleLifecycleManager::class);
        $permissions = $this->mock(ModulePermissionManager::class);
        $states = $this->mock(ModuleStateRepository::class);

        $lifecycle->shouldReceive('migrateIfNeeded')->once()->andReturn(['migrated' => true]);
        $permissions->shouldReceive('sync')->once()->andReturn(3);
        $states->shouldReceive('set')->once()->with('Demo', true);

        $result = $this->service($lifecycle, $permissions, $states)->toggle('Demo', 1);

        $this->assertTrue($result['enabled']);
        $this->assertTrue($result['migrated']);
        $this->assertSame(3, $result['permission_count']);
        $this->assertTrue(config('modules.registry.Demo.enabled'));
        $this->assertSame('runtime', config('modules.registry.Demo.source'));
    }

    public function test_failed_migration_does_not_persist_runtime_state(): void
    {
        config(['modules.registry.Demo' => $this->moduleConfig(false)]);

        $lifecycle = $this->mock(ModuleLifecycleManager::class);
        $permissions = $this->mock(ModulePermissionManager::class);
        $states = $this->mock(ModuleStateRepository::class);

        $lifecycle->shouldReceive('migrateIfNeeded')->once()->andThrow(new RuntimeException('migration failed'));
        $permissions->shouldNotReceive('sync');
        $states->shouldNotReceive('set');

        $this->expectException(RuntimeException::class);
        $this->service($lifecycle, $permissions, $states)->toggle('Demo', 1);
    }

    public function test_failed_permission_sync_does_not_persist_runtime_state(): void
    {
        config(['modules.registry.Demo' => $this->moduleConfig(false)]);

        $lifecycle = $this->mock(ModuleLifecycleManager::class);
        $permissions = $this->mock(ModulePermissionManager::class);
        $states = $this->mock(ModuleStateRepository::class);

        $lifecycle->shouldReceive('migrateIfNeeded')->once()->andReturn(['migrated' => false]);
        $permissions->shouldReceive('sync')->once()->andThrow(new RuntimeException('permission failed'));
        $states->shouldNotReceive('set');

        $this->expectException(RuntimeException::class);
        $this->service($lifecycle, $permissions, $states)->toggle('Demo', 1);
    }

    public function test_disable_persists_false_and_clears_permission_cache(): void
    {
        config(['modules.registry.Demo' => $this->moduleConfig(true)]);

        $lifecycle = $this->mock(ModuleLifecycleManager::class);
        $permissions = $this->mock(ModulePermissionManager::class);
        $states = $this->mock(ModuleStateRepository::class);

        $lifecycle->shouldNotReceive('migrateIfNeeded');
        $permissions->shouldNotReceive('sync');
        $permissions->shouldReceive('forgetCache')->once();
        $states->shouldReceive('set')->once()->with('Demo', false);

        $result = $this->service($lifecycle, $permissions, $states)->toggle('Demo', 1);

        $this->assertFalse($result['enabled']);
        $this->assertFalse(config('modules.registry.Demo.enabled'));
        $this->assertSame('runtime', config('modules.registry.Demo.source'));
    }

    public function test_required_module_cannot_be_disabled_or_written(): void
    {
        config(['modules.registry.Demo' => $this->moduleConfig(true, required: true)]);

        $lifecycle = $this->mock(ModuleLifecycleManager::class);
        $permissions = $this->mock(ModulePermissionManager::class);
        $states = $this->mock(ModuleStateRepository::class);

        $states->shouldNotReceive('set');

        $this->expectException(LogicException::class);
        $this->service($lifecycle, $permissions, $states)->toggle('Demo', 1);
    }

    private function service(
        ModuleLifecycleManager $lifecycle,
        ModulePermissionManager $permissions,
        ModuleStateRepository $states,
    ): SystemModuleControlService {
        $catalog = $this->mock(ModuleCatalog::class);
        $catalog->shouldReceive('discover')
            ->once()
            ->andReturnUsing(fn () => collect(config('modules.registry', []))->values());
        $validator = new ModuleGraphValidator;
        $registry = new ModuleRegistry($catalog, $validator);

        return new SystemModuleControlService($registry, $validator, $lifecycle, $permissions, $states);
    }

    private function moduleConfig(bool $enabled, bool $required = false, array $depends = []): array
    {
        return [
            'name' => 'Demo',
            'type' => $required ? 'shell' : 'domain',
            'enabled' => $enabled,
            'required' => $required,
            'depends' => $depends,
            'path' => base_path('Modules/Demo'),
            'source' => 'manifest',
        ];
    }
}
