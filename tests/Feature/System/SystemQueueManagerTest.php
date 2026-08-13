<?php

namespace Tests\Feature\System;

use Modules\System\Jobs\QueueProbeJob;
use Modules\System\Services\QueueRegistryService;
use Tests\TestCase;

class SystemQueueManagerTest extends TestCase
{
    public function test_admission_declares_document_queue_contract(): void
    {
        $config = require base_path('Modules/Admission/config/module.php');
        $queue = collect($config['queues'] ?? [])->firstWhere('name', 'admission-documents');

        $this->assertNotNull($queue);
        $this->assertSame(1, $queue['workers']);
        $this->assertSame(180, $queue['timeout']);
        $this->assertSame(3, $queue['tries']);
    }

    public function test_registry_discovers_enabled_module_queues_and_builds_worker_command(): void
    {
        $registry = app(QueueRegistryService::class);
        $queue = collect($registry->queues())->firstWhere('name', 'admission-documents');

        $this->assertNotNull($queue);
        $this->assertSame('Admission', $queue['module']);
        $this->assertStringContainsString('--queue=admission-documents', $registry->command($queue));
        $this->assertStringContainsString('--timeout=180', $registry->command($queue));
        $this->assertStringContainsString('--max-jobs=100', $registry->command($queue));
    }

    public function test_probe_job_targets_requested_queue(): void
    {
        $job = new QueueProbeJob('admission-documents');

        $this->assertSame('admission-documents', $job->queue);
        $this->assertSame(1, $job->tries);
        $this->assertSame(30, $job->timeout);
    }

    public function test_system_settings_exposes_queue_manager_tab(): void
    {
        $tabs = require base_path('Modules/System/config/system_tabs.php');
        $tab = collect($tabs)->firstWhere('id', 'queues');

        $this->assertNotNull($tab);
        $this->assertSame('system.settings.queue-manager', $tab['component']);
        $this->assertTrue($tab['enabled']);
    }
}
