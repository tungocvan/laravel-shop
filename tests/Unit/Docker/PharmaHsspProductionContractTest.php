<?php

namespace Tests\Unit\Docker;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PharmaHsspProductionContractTest extends TestCase
{
    #[Test]
    public function docker_runtime_supports_hssp_uploads_and_pharma_queue(): void
    {
        $root = base_path();
        $php = file_get_contents($root.'/docker/php/php.ini');
        $nginx = file_get_contents($root.'/docker/nginx/default.conf');
        $compose = file_get_contents($root.'/compose.yaml');
        $env = file_get_contents($root.'/.env.docker.example');

        $this->assertStringContainsString('upload_max_filesize=50M', $php);
        $this->assertStringContainsString('post_max_size=64M', $php);
        $this->assertStringContainsString('client_max_body_size 100m;', $nginx);
        $this->assertStringContainsString('QUEUE_NAMES:-facebook,pharma,default', $compose);
        $this->assertStringContainsString('QUEUE_NAMES=facebook,pharma,default', $env);
        $this->assertStringContainsString('app_storage:/var/www/html/storage', $compose);
    }
}
