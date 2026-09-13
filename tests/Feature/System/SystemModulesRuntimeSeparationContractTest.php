<?php

namespace Tests\Feature\System;

use App\Modules\ModuleRegistry;
use Modules\System\Services\QueueRegistryService;
use Tests\TestCase;

class SystemModulesRuntimeSeparationContractTest extends TestCase
{
    public function test_modules_workspace_is_lifecycle_only(): void
    {
        $component = file_get_contents(base_path('Modules/System/Livewire/Settings/ModulesForm.php'));
        $view = file_get_contents(base_path('Modules/System/resources/views/livewire/settings/modules-form.blade.php'));

        $this->assertIsString($component);
        $this->assertIsString($view);

        $this->assertStringContainsString('SystemModuleControlService', $component);
        $this->assertStringContainsString('SystemModuleOverviewService', $component);
        $this->assertStringContainsString('function toggleModule', $component);
        $this->assertStringContainsString("authorizePermission('system.modules.update')", $component);

        $this->assertStringNotContainsString('RealtimeManager', $component);
        $this->assertStringNotContainsString('SystemRealtimeControlService', $component);
        $this->assertStringNotContainsString('ModuleRouteManager', $component);
        $this->assertStringNotContainsString('moduleRoutes', $component);
        $this->assertStringNotContainsString('toggleRealtime', $component);

        $this->assertStringContainsString('Bật/tắt Module và kiểm tra dependency, trạng thái database.', $view);
        $this->assertStringContainsString("wire:click=\"toggleModule('", $view);
        $this->assertStringNotContainsString('<x-realtime-control', $view);
        $this->assertStringNotContainsString('<x-module-routes-table', $view);
        $this->assertStringNotContainsString('GET Routes của Modules', $view);
    }

    public function test_queue_manager_is_runtime_agnostic_and_manages_failed_history(): void
    {
        $component = file_get_contents(base_path('Modules/System/Livewire/Settings/QueueManager.php'));
        $view = file_get_contents(base_path('Modules/System/resources/views/livewire/settings/queue-manager.blade.php'));
        $registry = file_get_contents(base_path('Modules/System/Services/QueueRegistryService.php'));
        $failedJobs = file_get_contents(base_path('Modules/System/Services/QueueFailedJobService.php'));
        $tabs = file_get_contents(base_path('Modules/System/config/system_tabs.php'));

        $this->assertIsString($component);
        $this->assertIsString($view);
        $this->assertIsString($registry);
        $this->assertIsString($failedJobs);
        $this->assertIsString($tabs);

        $this->assertStringContainsString('use Illuminate\\Support\\Facades\\Artisan;', $component);
        $this->assertStringContainsString('use Modules\\System\\Services\\QueueFailedJobService;', $component);
        $this->assertStringContainsString('function restartWorkers', $component);
        $this->assertStringContainsString('function openFailedJobs', $component);
        $this->assertStringContainsString('function retrySelectedFailed', $component);
        $this->assertStringContainsString('function forgetSelectedFailed', $component);
        $this->assertStringContainsString('function clearFailedHistory', $component);
        $this->assertStringContainsString("authorizePermission('system.settings.update')", $component);
        $this->assertStringContainsString("Artisan::call('queue:restart')", $component);
        $this->assertStringContainsString('QueueProbeJob::dispatch($queue)', $component);
        $this->assertStringNotContainsString('SystemProcessManagerService', $component);
        $this->assertStringNotContainsString('$e->getMessage()', $component);

        $this->assertStringContainsString('Restart queue workers', $view);
        $this->assertStringContainsString('Không phụ thuộc PM2', $view);
        $this->assertStringContainsString('Trạng thái Queue', $view);
        $this->assertStringContainsString('Failed history', $view);
        $this->assertStringContainsString('Xem {{ $status[\'failed\'] }} lỗi', $view);
        $this->assertStringContainsString('Clear lịch sử', $view);
        $this->assertStringContainsString('Retry đã chọn', $view);
        $this->assertStringContainsString('Xóa đã chọn', $view);
        $this->assertStringContainsString('Clear toàn bộ lịch sử', $view);
        $this->assertStringContainsString('<x-realtime-control', $view);
        $this->assertStringContainsString('wire:poll.5s="$refresh"', $view);
        $this->assertStringNotContainsString('Dịch vụ nền PM2', $view);
        $this->assertStringNotContainsString('processAction(', $view);

        $this->assertStringContainsString('use App\\Modules\\ModuleRegistry;', $registry);
        $this->assertStringContainsString('$runtimeModules = $this->modules->current()->keyBy(\'name\');', $registry);
        $this->assertStringContainsString('$disabledOwnedQueues[$name] = true;', $registry);
        $this->assertStringContainsString('if (isset($disabledOwnedQueues[$name]))', $registry);
        $this->assertStringContainsString("config('queue.connections.'.config('queue.default').'.queue', 'default')", $registry);
        $this->assertStringContainsString("DB::table('jobs')->distinct()->pluck('queue')", $registry);
        $this->assertStringContainsString("DB::table('failed_jobs')->distinct()->pluck('queue')", $registry);
        $this->assertStringContainsString("'state' =>", $registry);

        $this->assertStringContainsString("Artisan::call('queue:retry'", $failedJobs);
        $this->assertStringContainsString('DB::table(\'failed_jobs\')->where(\'queue\', $queue)->delete()', $failedJobs);
        $this->assertStringContainsString('exceptionSummary', $failedJobs);
        $this->assertStringNotContainsString('shell_exec(', $failedJobs);
        $this->assertStringNotContainsString('exec(', $failedJobs);

        $this->assertFileDoesNotExist(base_path('Modules/System/Services/SystemProcessManagerService.php'));
        $this->assertStringContainsString("'id' => 'queues'", $tabs);
        $this->assertStringContainsString("'component' => 'system.settings.queue-manager'", $tabs);
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
