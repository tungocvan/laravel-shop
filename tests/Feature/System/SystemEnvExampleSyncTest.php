<?php

namespace Tests\Feature\System;

use Modules\System\Services\Env\EnvExampleSyncService;
use Tests\TestCase;

class SystemEnvExampleSyncTest extends TestCase
{
    public function test_production_env_render_sanitizes_secrets_and_keeps_safe_values(): void
    {
        $source = <<<'ENV'
APP_NAME="INAFO Pharma"
APP_ENV=local
APP_KEY=base64:real-secret
APP_DEBUG=true
APP_URL=https://real.example.test
DB_HOST=10.0.0.5
DB_DATABASE=real_database
DB_USERNAME=real_user
DB_PASSWORD=real_password
REDIS_HOST=10.0.0.6
GOOGLE_DRIVE_CLIENT_ID=client-id.apps.googleusercontent.com
GOOGLE_DRIVE_CLIENT_SECRET=GOCSPX-real-secret
MUASAMCONG_SMART_TOKEN=real-token
SESSION_COOKIE=real-cookie
AWS_ACCESS_KEY_ID=AKIAREALKEY
CUSTOM_SAFE_FLAG=true
ENV;

        $template = <<<'ENV'
APP_NAME=Laravel
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://example.com
DB_HOST=127.0.0.1
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=
REDIS_HOST=127.0.0.1
GOOGLE_DRIVE_CLIENT_ID=
GOOGLE_DRIVE_CLIENT_SECRET=
ENV;

        $service = app(EnvExampleSyncService::class);
        $result = $service->render($source, $template, false);
        $content = $result['content'];

        $this->assertStringContainsString('APP_NAME="INAFO Pharma"', $content);
        $this->assertStringContainsString('APP_ENV=production', $content);
        $this->assertStringContainsString('APP_DEBUG=false', $content);
        $this->assertStringContainsString('APP_URL=https://example.com', $content);
        $this->assertStringContainsString('DB_DATABASE=laravel', $content);
        $this->assertStringContainsString('GOOGLE_DRIVE_CLIENT_ID=client-id.apps.googleusercontent.com', $content);
        $this->assertStringContainsString('CUSTOM_SAFE_FLAG=true', $content);

        $this->assertStringContainsString("APP_KEY=\n", $content);
        $this->assertStringContainsString("DB_PASSWORD=\n", $content);
        $this->assertStringContainsString("GOOGLE_DRIVE_CLIENT_SECRET=\n", $content);
        $this->assertStringContainsString("MUASAMCONG_SMART_TOKEN=\n", $content);
        $this->assertStringContainsString("SESSION_COOKIE=\n", $content);
        $this->assertStringContainsString("AWS_ACCESS_KEY_ID=\n", $content);

        $this->assertStringNotContainsString('real-secret', $content);
        $this->assertStringNotContainsString('real_password', $content);
        $this->assertStringNotContainsString('real-token', $content);
        $this->assertStringNotContainsString('real-cookie', $content);
        $this->assertStringNotContainsString('AKIAREALKEY', $content);
    }

    public function test_docker_render_preserves_docker_network_defaults_and_template_only_keys(): void
    {
        $source = <<<'ENV'
APP_ENV=local
APP_DEBUG=true
APP_URL=https://real.example.test
DB_HOST=127.0.0.1
DB_DATABASE=real_database
DB_USERNAME=real_user
DB_PASSWORD=real_password
REDIS_HOST=127.0.0.1
FACEBOOK_SCHEDULER_ENABLED=false
NEW_RUNTIME_FLAG=enabled
ENV;

        $template = <<<'ENV'
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com
DB_HOST=db
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=
REDIS_HOST=redis
QUEUE_NAMES=facebook,default
ENV;

        $service = app(EnvExampleSyncService::class);
        $result = $service->render($source, $template, true);
        $content = $result['content'];

        $this->assertStringContainsString('DB_HOST=db', $content);
        $this->assertStringContainsString('REDIS_HOST=redis', $content);
        $this->assertStringContainsString('DB_DATABASE=laravel', $content);
        $this->assertStringContainsString('DB_USERNAME=laravel', $content);
        $this->assertStringContainsString("DB_PASSWORD=\n", $content);
        $this->assertStringContainsString('QUEUE_NAMES=facebook,default', $content);
        $this->assertStringContainsString('FACEBOOK_SCHEDULER_ENABLED=false', $content);
        $this->assertStringContainsString('NEW_RUNTIME_FLAG=enabled', $content);
        $this->assertStringNotContainsString('real_password', $content);
    }
}
