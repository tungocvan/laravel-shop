<?php

namespace Tests\Feature\Request\Architecture;

use Tests\TestCase;

class RequestOperationalDeploymentContractTest extends TestCase
{
    public function test_root_compose_has_dedicated_request_worker_and_scheduler(): void
    {
        $compose = file_get_contents(base_path('compose.yaml'));

        $this->assertIsString($compose);
        $this->assertStringContainsString('queue-request:', $compose);
        $this->assertStringContainsString('--queue=request-outbox,request-notifications,request-exports', $compose);
        $this->assertStringContainsString('REQUEST_QUEUE_SLEEP', $compose);
        $this->assertStringContainsString('REQUEST_QUEUE_TRIES', $compose);
        $this->assertStringContainsString('REQUEST_QUEUE_TIMEOUT', $compose);
        $this->assertStringContainsString('scheduler:', $compose);
        $this->assertStringContainsString('["php", "artisan", "schedule:work"]', $compose);
    }

    public function test_docker_environment_example_exposes_request_worker_tuning_only(): void
    {
        $environment = file_get_contents(base_path('.env.docker.example'));

        $this->assertIsString($environment);
        $this->assertStringContainsString('REQUEST_QUEUE_SLEEP=3', $environment);
        $this->assertStringContainsString('REQUEST_QUEUE_TRIES=5', $environment);
        $this->assertStringContainsString('REQUEST_QUEUE_TIMEOUT=120', $environment);
        $this->assertStringContainsString('REQUEST_QUEUE_MAX_TIME=3600', $environment);
        $this->assertStringContainsString('REQUEST_QUEUE_MEMORY_LIMIT=768m', $environment);
        $this->assertStringContainsString('REQUEST_QUEUE_CPU_LIMIT=0.75', $environment);
    }

    public function test_release_runbook_keeps_local_pm2_and_production_compose_roles_separate(): void
    {
        $runbook = file_get_contents(base_path('docs/modules/Request/RELEASE_RUNBOOK.md'));

        $this->assertIsString($runbook);
        $this->assertStringContainsString('Request-Queue-laravel-shop', $runbook);
        $this->assertStringContainsString('Scheduler-laravel-shop', $runbook);
        $this->assertStringContainsString('queue-request', $runbook);
        $this->assertStringContainsString('module:migration-recover Request --apply', $runbook);
        $this->assertStringContainsString('Không tự insert bảng `migrations` bằng SQL/Tinker.', $runbook);
        $this->assertStringContainsString('Không dùng `migrate:rollback` một cách mù quáng', $runbook);
    }
}
