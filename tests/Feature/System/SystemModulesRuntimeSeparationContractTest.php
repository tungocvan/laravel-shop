<?php

namespace Tests\Feature\System;

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

    public function test_queue_manager_is_runtime_agnostic_and_controls_laravel_workers(): void
    {
        $component = file_get_contents(base_path('Modules/System/Livewire/Settings/QueueManager.php'));
        $view = file_get_contents(base_path('Modules/System/resources/views/livewire/settings/queue-manager.blade.php'));
        $registry = file_get_contents(base_path('Modules/System/Services/QueueRegistryService.php'));
        $tabs = file_get_contents(base_path('Modules/System/config/system_tabs.php'));

        $this->assertIsString($component);
        $this->assertIsString($view);
        $this->assertIsString($registry);
        $this->assertIsString($tabs);

        $this->assertStringContainsString('use Illuminate\\Support\\Facades\\Artisan;', $component);
        $this->assertStringContainsString('function restartWorkers', $component);
        $this->assertStringContainsString("authorizePermission('system.settings.update')", $component);
        $this->assertStringContainsString("Artisan::call('queue:restart')", $component);
        $this->assertStringContainsString('QueueProbeJob::dispatch($queue)', $component);
        $this->assertStringNotContainsString('SystemProcessManagerService', $component);
        $this->assertStringNotContainsString('$e->getMessage()', $component);

        $this->assertStringContainsString('Restart queue workers', $view);
        $this->assertStringContainsString('Không phụ thuộc PM2', $view);
        $this->assertStringContainsString('Trạng thái Queue', $view);
        $this->assertStringContainsString('Runtime discovered', $view);
        $this->assertStringContainsString('<x-realtime-control', $view);
        $this->assertStringContainsString('wire:poll.5s="$refresh"', $view);
        $this->assertStringNotContainsString('Dịch vụ nền PM2', $view);
        $this->assertStringNotContainsString('processAction(', $view);

        $this->assertStringContainsString("config('queue.connections.'.config('queue.default').'.queue', 'default')", $registry);
        $this->assertStringContainsString("DB::table('jobs')->distinct()->pluck('queue')", $registry);
        $this->assertStringContainsString("DB::table('failed_jobs')->distinct()->pluck('queue')", $registry);
        $this->assertStringContainsString("'state' =>", $registry);

        $this->assertFileDoesNotExist(base_path('Modules/System/Services/SystemProcessManagerService.php'));
        $this->assertStringContainsString("'id' => 'queues'", $tabs);
        $this->assertStringContainsString("'component' => 'system.settings.queue-manager'", $tabs);
    }
}
