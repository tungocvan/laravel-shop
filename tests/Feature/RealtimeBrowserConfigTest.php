<?php

namespace Tests\Feature;

use App\Services\RealtimeManager;
use Modules\System\Services\Env\AdvancedConfigService;
use Tests\TestCase;

class RealtimeBrowserConfigTest extends TestCase
{
    public function test_local_browser_url_falls_back_to_loopback_nodejs_server(): void
    {
        $this->assertBrowserUrl('local', null, 'http://127.0.0.1:6001', 'http://127.0.0.1:6001');
    }

    public function test_explicit_public_url_takes_priority_in_local(): void
    {
        $this->assertBrowserUrl('local', 'https://socket.example.test', 'http://127.0.0.1:6001', 'https://socket.example.test');
    }

    public function test_production_does_not_expose_internal_nodejs_server(): void
    {
        $this->assertBrowserUrl('production', null, 'http://127.0.0.1:6001', null);
    }

    public function test_local_does_not_expose_non_loopback_internal_server(): void
    {
        $this->assertBrowserUrl('local', null, 'http://10.0.0.5:6001', null);
    }

    private function assertBrowserUrl(string $environment, ?string $publicUrl, string $serverUrl, ?string $expected): void
    {
        $originalEnvironment = app()->environment();
        $originalPublicUrl = config('services.nodejs.public_url');

        try {
            app()->instance('env', $environment);
            config()->set('services.nodejs.public_url', $publicUrl);
            $advanced = $this->mock(AdvancedConfigService::class);
            $advanced->shouldReceive('serverUrl')->once()->andReturn($serverUrl);

            $this->assertSame($expected, app(RealtimeManager::class)->browserConfig()['url']);
        } finally {
            app()->instance('env', $originalEnvironment);
            config()->set('services.nodejs.public_url', $originalPublicUrl);
            app()->forgetInstance(AdvancedConfigService::class);
        }
    }
}
