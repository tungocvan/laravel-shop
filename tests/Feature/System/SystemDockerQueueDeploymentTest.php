<?php

namespace Tests\Feature\System;

use Tests\TestCase;

class SystemDockerQueueDeploymentTest extends TestCase
{
    public function test_compose_has_dedicated_admission_documents_worker(): void
    {
        $compose = file_get_contents(base_path('compose.yaml'));

        $this->assertStringContainsString('queue-admission-documents:', $compose);
        $this->assertStringContainsString('--queue=admission-documents', $compose);
        $this->assertStringContainsString('${ADMISSION_QUEUE_SLEEP:-2}', $compose);
        $this->assertStringContainsString('${ADMISSION_QUEUE_TRIES:-3}', $compose);
        $this->assertStringContainsString('${ADMISSION_QUEUE_TIMEOUT:-180}', $compose);
        $this->assertStringContainsString('${ADMISSION_QUEUE_MAX_JOBS:-100}', $compose);
        $this->assertStringContainsString('${ADMISSION_QUEUE_MAX_TIME:-3600}', $compose);
        $this->assertStringContainsString('${ADMISSION_QUEUE_MEMORY_LIMIT:-768m}', $compose);
        $this->assertStringContainsString('${ADMISSION_QUEUE_CPU_LIMIT:-1.0}', $compose);
        $this->assertStringContainsString('app_storage:/var/www/html/storage', $compose);
        $this->assertStringContainsString('restart: unless-stopped', $compose);
    }

    public function test_docker_example_exposes_admission_queue_tuning(): void
    {
        $env = file_get_contents(base_path('.env.docker.example'));

        foreach ([
            'ADMISSION_QUEUE_SLEEP=2',
            'ADMISSION_QUEUE_TRIES=3',
            'ADMISSION_QUEUE_TIMEOUT=180',
            'ADMISSION_QUEUE_MAX_JOBS=100',
            'ADMISSION_QUEUE_MAX_TIME=3600',
            'ADMISSION_QUEUE_MEMORY_LIMIT=768m',
            'ADMISSION_QUEUE_CPU_LIMIT=1.0',
        ] as $setting) {
            $this->assertStringContainsString($setting, $env);
        }
    }

    public function test_app_image_contains_libreoffice_for_pdf_generation(): void
    {
        $dockerfile = file_get_contents(base_path('Dockerfile'));

        $this->assertStringContainsString('libreoffice-core', $dockerfile);
        $this->assertStringContainsString('libreoffice-writer', $dockerfile);
    }
}
