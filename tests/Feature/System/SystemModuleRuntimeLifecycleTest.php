<?php

namespace Tests\Feature\System;

use App\Modules\ModuleLifecycleManager;
use App\Modules\ModulePermissionManager;
use App\Modules\ModuleStateRepository;
use LogicException;
use Modules\System\Services\SystemModuleControlService;
use RuntimeException;
use Tests\TestCase;

class SystemModuleRuntimeLifecycleTest extends TestCase
{
    public function test_successful_archive_forgets_runtime_state_and_removes_registry_entry(): void
    {
        config(['modules.registry.Demo' => $this->moduleConfig(false)]);

        [$service, $lifecycle, $states] = $this->service();
        $lifecycle->shouldReceive('archive')->once()->andReturn(storage_path('app/module-trash/Demo-20260814-120000'));
        $states->shouldReceive('forget')->once()->with('Demo');

        $result = $service->archive('Demo', 1);

        $this->assertSame('Demo', $result['module']);
        $this->assertSame('Demo-20260814-120000', $result['archive']);
        $this->assertNull(config('modules.registry.Demo'));
    }

    public function test_failed_archive_does_not_forget_runtime_state(): void
    {
        config(['modules.registry.Demo' => $this->moduleConfig(false)]);

        [$service, $lifecycle, $states] = $this->service();
        $lifecycle->shouldReceive('archive')->once()->andThrow(new RuntimeException('archive failed'));
        $states->shouldNotReceive('forget');

        $this->expectException(RuntimeException::class);
        $service->archive('Demo', 1);
    }

    public function test_required_module_archive_does_not_forget_runtime_state(): void
    {
        config(['modules.registry.Demo' => $this->moduleConfig(false, true)]);

        [$service, $lifecycle, $states] = $this->service();
        $lifecycle->shouldReceive('archive')->once()->andThrow(new LogicException('required'));
        $states->shouldNotReceive('forget');

        $this->expectException(LogicException::class);
        $service->archive('Demo', 1);
    }

    private function service(): array
    {
        $lifecycle = $this->mock(ModuleLifecycleManager::class);
        $permissions = $this->mock(ModulePermissionManager::class);
        $states = $this->mock(ModuleStateRepository::class);

        return [new SystemModuleControlService($lifecycle, $permissions, $states), $lifecycle, $states];
    }

    private function moduleConfig(bool $enabled, bool $required = false): array
    {
        return [
            'name' => 'Demo',
            'type' => $required ? 'shell' : 'domain',
            'enabled' => $enabled,
            'required' => $required,
            'depends' => [],
            'path' => base_path('Modules/Demo'),
            'source' => 'runtime',
        ];
    }
}
