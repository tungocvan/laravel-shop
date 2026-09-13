<?php

namespace Tests\Feature\System;

use App\Modules\ModuleRegistry;
use Modules\System\Services\QueueRegistryService;
use Tests\TestCase;

class SystemModulesRuntimeSeparationContractTest extends TestCase
{
    public function test_modules_workspace_is_lifecycle_only_and_uses_preflight_modal(): void
    {
        $component = file_get_contents(base_path('Modules/System/Livewire/Settings/ModulesForm.php'));
        $view = file_get_contents(base_path('Modules/System/resources/views/livewire/settings/modules-form.blade.php'));
        $preview = file_get_contents(base_path('Modules/System/Services/SystemModuleLifecyclePreviewService.php'));
        $control = file_get_contents(base_path('Modules/System/Services/SystemModuleControlService.php'));

        $this->assertIsString($component);
        $this->assertIsString($view);
        $this->assertIsString($preview);
        $this->assertIsString($control);

        $this->assertStringContainsString('SystemModuleControlService', $component);
        $this->assertStringContainsString('SystemModuleLifecyclePreviewService', $component);
        $this->assertStringContainsString('function toggleModule', $component);
        $this->assertStringContainsString('function confirmLifecycleToggle', $component);
        $this->assertStringContainsString("authorizePermission('system.modules.update')", $component);
        $this->assertStringNotContainsString('$e->getMessage()', $component);

        $this->assertStringContainsString('Module Lifecycle Control', $view);
        $this->assertStringContainsString('Database / Migration', $view);
        $this->assertStringContainsString('Permissions', $view);
        $this->assertStringContainsString('Queue impact', $view);
        $this->assertStringContainsString('System queue <code>default</code> luôn được giữ active.', $view);
        $this->assertStringContainsString('wire:click="confirmLifecycleToggle"', $view);
        $this->assertStringContainsString("wire:click=\"toggleModule('", $view);
        $this->assertStringNotContainsString('wire:confirm=', $view);
        $this->assertStringNotContainsString('<x-realtime-control', $view);
        $this->assertStringNotContainsString('<x-module-routes-table', $view);
        $this->assertStringNotContainsString('GET Routes của Modules', $view);

        $this->assertStringContainsString('migrationDiagnosis($module)', $preview);
        $this->assertStringContainsString('permissions->discoverModules()', $preview);
        $this->assertStringContainsString("DB::table('jobs')->where('queue', $queue['name'])", $preview);
        $this->assertStringContainsString("DB::table('failed_jobs')->where('queue', $queue['name'])", $preview);
        $this->assertStringContainsString('$name !== $defaultQueue', $control);
        $this->assertStringContainsString("Artisan::call('queue:restart')", $control);
        $this->assertStringContainsString('SystemModuleLifecycleException', $control);
    }

    public function test_queue_manager_is_runtime_agnostic_and_manages_failed_history(): void
    {
        $component = file_get_contents(base_path('Modules/System/Livewire/Settings/QueueManager.php'));
        $view = file_get_contents(base_path('Modules/System/resources/views/livewire/settings/queue-manager.blade.php'));
        $registry = file_get_contents(base_path('Modules/System/Services/QueueRegistryService.php'));
        $failedJobs = file_get_contents(base_path('Modules/System/Services/QueueFailedJobService.php'));

        $this->assertStringContainsString("Artisan::call('queue:restart')", $component);
        $this->assertStringContainsString('markProbeSent($queue)', $component);
        $this->assertStringNotContainsString('SystemProcessManagerService', $component);
        $this->assertStringNotContainsString('$e->getMessage()', $component);

        $this->assertStringContainsString('wire:poll.5s="$refresh"', $view);
        $this->assertStringContainsString('wire:poll.30s="$refresh"', $view);
        $this->assertStringContainsString('Theo dõi nhanh · 5 giây', $view);
        $this->assertStringContainsString('Hệ thống rảnh · 30 giây', $view);
        $this->assertStringContainsString('Pending quá 5 phút sẽ được cảnh báo.', $view);
        $this->assertStringContainsString('Worker health', $view);
        $this->assertStringContainsString('Failed history', $view);
        $this->assertStringContainsString('Clear toàn bộ lịch sử', $view);
        $this->assertStringNotContainsString('Dịch vụ nền PM2', $view);

        $this->assertStringContainsString('$disabledOwnedQueues[$name] = true;', $registry);
        $this->assertStringContainsString('private const STALE_PENDING_SECONDS = 300;', $registry);
        $this->assertStringContainsString("'probe_state' =>", $registry);
        $this->assertStringContainsString("Artisan::call('queue:retry'", $failedJobs);
        $this->assertStringContainsString('exceptionSummary', $failedJobs);
    }

    public function test_queue_owned_by_disabled_module_is_hidden_even_when_runtime_history_exists(): void
    {
        $registry = $this->mock(ModuleRegistry::class);
        $registry->shouldReceive('current')->once()->andReturn(collect([
            ['name' => 'Admission', 'enabled' => false],
        ]));

        $queues = collect((new QueueRegistryService($registry))->queues())->pluck('name');

        $this->assertFalse($queues->contains('admission-documents'));
    }
}
