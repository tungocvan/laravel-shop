<?php

namespace Tests\Feature\System;

use Tests\TestCase;

class RealtimeBridgePlacementContractTest extends TestCase
{
    public function test_realtime_control_is_rendered_in_nodejs_bridge_not_queue_manager(): void
    {
        $advanced = file_get_contents(base_path('Modules/System/resources/views/livewire/settings/advanced-config.blade.php'));
        $queue = file_get_contents(base_path('Modules/System/resources/views/livewire/settings/queue-manager.blade.php'));
        $bridge = file_get_contents(base_path('Modules/System/resources/views/livewire/settings/realtime-bridge-control.blade.php'));

        $this->assertStringContainsString('NodeJS Server Bridge (Realtime)', $advanced);
        $this->assertStringContainsString("@livewire('system.settings.realtime-bridge-control')", $advanced);
        $this->assertStringNotContainsString('<x-realtime-control', $queue);
        $this->assertStringContainsString('<x-realtime-control', $bridge);
    }

    public function test_bridge_control_retains_server_side_realtime_update_permission(): void
    {
        $component = file_get_contents(base_path('Modules/System/Livewire/Settings/RealtimeBridgeControl.php'));

        $this->assertStringContainsString("authorizePermission('system.modules.update')", $component);
        $this->assertStringContainsString('SystemRealtimeControlService', $component);
    }
}
