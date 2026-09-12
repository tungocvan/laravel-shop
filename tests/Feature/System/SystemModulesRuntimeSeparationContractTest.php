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
        $this->assertStringNotContainsString('refreshRealtimeStatus', $component);

        $this->assertStringContainsString('Bật/tắt Module và kiểm tra dependency, trạng thái database.', $view);
        $this->assertStringContainsString("wire:click=\"toggleModule('", $view);
        $this->assertStringContainsString('Đang được sử dụng bởi:', $view);
        $this->assertStringContainsString('Database đã sẵn sàng', $view);
        $this->assertStringNotContainsString('<x-realtime-control', $view);
        $this->assertStringNotContainsString('<x-module-routes-table', $view);
        $this->assertStringNotContainsString('GET Routes của Modules', $view);
    }

    public function test_queue_manager_owns_realtime_and_queue_runtime_controls(): void
    {
        $component = file_get_contents(base_path('Modules/System/Livewire/Settings/QueueManager.php'));
        $view = file_get_contents(base_path('Modules/System/resources/views/livewire/settings/queue-manager.blade.php'));
        $tabs = file_get_contents(base_path('Modules/System/config/system_tabs.php'));

        $this->assertIsString($component);
        $this->assertIsString($view);
        $this->assertIsString($tabs);

        $this->assertStringContainsString('use App\\Services\\RealtimeManager;', $component);
        $this->assertStringContainsString('use Modules\\System\\Services\\SystemRealtimeControlService;', $component);
        $this->assertStringContainsString('public bool $realtimeEnabled = false;', $component);
        $this->assertStringContainsString('public array $realtimeStatus = [];', $component);
        $this->assertStringContainsString('function toggleRealtime', $component);
        $this->assertStringContainsString('function refreshRealtimeStatus', $component);
        $this->assertStringContainsString("can('system.modules.update')", $component);
        $this->assertStringContainsString('QueueProbeJob::dispatch($queue)', $component);
        $this->assertStringNotContainsString('$e->getMessage()', $component);

        $this->assertStringContainsString('<x-realtime-control', $view);
        $this->assertStringContainsString(':can-update="$canUpdateRealtime"', $view);
        $this->assertStringContainsString('Realtime / Socket.IO', $view);
        $this->assertStringContainsString('Kiểm tra worker', $view);
        $this->assertStringContainsString('wire:poll.5s="$refresh"', $view);

        $this->assertStringContainsString("'id' => 'queues'", $tabs);
        $this->assertStringContainsString("'component' => 'system.settings.queue-manager'", $tabs);
    }
}
