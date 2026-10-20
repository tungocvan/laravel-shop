<?php

namespace Tests\Feature\System;

use Tests\TestCase;

class AdvancedDatabaseSettingsContractTest extends TestCase
{
    public function test_advanced_settings_are_database_backed_and_default_urls_are_environment_specific(): void
    {
        $source = file_get_contents(base_path('Modules/System/Services/Env/AdvancedConfigService.php'));
        $this->assertStringContainsString('SettingsService $settings', $source);
        $this->assertStringContainsString('http://127.0.0.1:6001', $source);
        $this->assertStringContainsString('http://socket:6001', $source);
        $this->assertStringContainsString('Crypt::encryptString($secret)', $source);
        $this->assertStringContainsString('Crypt::decryptString(', $source);
        $this->assertStringContainsString('public function serverCredentials(): array', $source);
        $this->assertStringContainsString("'secret' => \$this->bridgeSecret()", $source);
        $this->assertStringContainsString("updateMany(\$values, 'runtime')", $source);
        $this->assertStringNotContainsString('EnvManagerService', $source);
    }

    public function test_queue_driver_is_not_presented_as_database_mutable(): void
    {
        $view = file_get_contents(base_path('Modules/System/resources/views/livewire/settings/advanced-config.blade.php'));
        $this->assertStringContainsString('wire:model="form.QUEUE_CONNECTION" disabled', $view);
        $this->assertStringContainsString('Lưu cấu hình NodeJS Bridge vào cơ sở dữ liệu?', $view);
        $this->assertStringNotContainsString('vào .env?', $view);
    }

    public function test_node_bridge_secret_remains_a_nodejs_runtime_requirement(): void
    {
        $compose = file_get_contents(base_path('compose.yaml'));
        $socket = file_get_contents(base_path('socket/server.js'));
        $localExample = file_get_contents(base_path('.env.example'));
        $dockerExample = file_get_contents(base_path('.env.docker.example'));

        $this->assertMatchesRegularExpression('/(?m)^  socket:\\s*$/', $compose);
        $this->assertMatchesRegularExpression('/(?m)^    env_file: \\.env\\s*$/', $compose);
        $this->assertStringContainsString('process.env.BRIDGE_SECRET_KEY', $socket);
        $this->assertStringContainsString('req.headers["x-bridge-secret"]', $socket);
        $this->assertMatchesRegularExpression('/(?m)^BRIDGE_SECRET_KEY=/', $localExample);
        $this->assertMatchesRegularExpression('/(?m)^BRIDGE_SECRET_KEY=/', $dockerExample);
    }

}
