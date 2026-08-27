<?php

namespace Tests\Feature\Modules;

use Tests\TestCase;

class RequestSchedulerModuleGateTest extends TestCase
{
    public function test_request_scheduler_entries_are_gated_by_effective_module_state(): void
    {
        $source = file_get_contents(base_path('routes/console.php'));

        $this->assertIsString($source);
        $this->assertStringContainsString("if (config('modules.registry.Request.enabled', false)) {", $source);

        $guardStart = strpos($source, "if (config('modules.registry.Request.enabled', false)) {");
        $guardEnd = strpos($source, "Schedule::command('system:cloud-backup')", $guardStart);

        $this->assertNotFalse($guardStart);
        $this->assertNotFalse($guardEnd);

        $requestScheduleBlock = substr($source, $guardStart, $guardEnd - $guardStart);

        $this->assertStringContainsString('DispatchRequestOutboxBatch', $requestScheduleBlock);
        $this->assertStringContainsString("Schedule::command('request:sla-enforce')", $requestScheduleBlock);
    }
}
