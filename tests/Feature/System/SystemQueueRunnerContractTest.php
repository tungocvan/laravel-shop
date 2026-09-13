<?php

namespace Tests\Feature\System;

use Tests\TestCase;

class SystemQueueRunnerContractTest extends TestCase
{
    public function test_run_queue_discovers_enabled_module_queues_and_recreates_general_worker(): void
    {
        $script = file_get_contents(base_path('run-queue.sh'));

        $this->assertIsString($script);
        $this->assertStringContainsString('App\\Modules\\ModuleRegistry::class', $script);
        $this->assertStringContainsString('$module["enabled"] ?? false', $script);
        $this->assertStringContainsString('$config["queues"] ?? []', $script);
        $this->assertStringContainsString('$queues = ["default"]', $script);
        $this->assertStringContainsString('GENERAL_QUEUES', $script);
        $this->assertStringContainsString('--queue="$GENERAL_QUEUES"', $script);
        $this->assertStringContainsString('--timeout="$GENERAL_TIMEOUT"', $script);
        $this->assertStringContainsString('--tries="$GENERAL_TRIES"', $script);
        $this->assertStringContainsString('pm2 delete "$DEFAULT_QUEUE_NAME"', $script);
        $this->assertStringContainsString('--queue=request-outbox,request-notifications,request-exports', $script);
        $this->assertStringContainsString('pm2 save', $script);
    }
}
