<?php

namespace Tests\Feature\Chat;

use Illuminate\Support\Facades\Http;
use Modules\Chat\Services\ChatService;
use Modules\Chat\Services\InternalChatService;
use Modules\System\Services\Env\AdvancedConfigService;
use Tests\TestCase;

class ChatBridgeDatabaseSettingsTest extends TestCase
{
    public function test_chat_broadcast_uses_database_bridge_credentials(): void
    {
        $this->assertBroadcastUsesDatabaseCredentials(
            new class extends ChatService {
                public function dispatchForTest(array $payload): void
                {
                    $this->broadcastToNodeJS($payload);
                }
            }
        );
    }

    public function test_internal_chat_broadcast_uses_database_bridge_credentials(): void
    {
        $this->assertBroadcastUsesDatabaseCredentials(
            new class extends InternalChatService {
                public function dispatchForTest(array $payload): void
                {
                    $this->broadcast($payload);
                }
            }
        );
    }

    public function test_chat_skips_http_when_database_bridge_secret_is_missing(): void
    {
        config()->set('realtime.allowed', true);
        config()->set('realtime.default_enabled', true);
        $this->mock(AdvancedConfigService::class)->shouldReceive('serverCredentials')
            ->once()->andReturn(['url' => 'http://127.0.0.1:6001', 'secret' => '']);
        Http::fake();

        $service = new class extends ChatService {
            public function dispatchForTest(): void
            {
                $this->broadcastToNodeJS(['event' => 'Test']);
            }
        };
        $service->dispatchForTest();
        Http::assertNothingSent();
    }

    private function assertBroadcastUsesDatabaseCredentials(object $service): void
    {
        $this->mock(RealtimeManager::class)->shouldReceive('enabled')->once()->andReturn(true);
        $this->mock(AdvancedConfigService::class)->shouldReceive('serverCredentials')
            ->once()->andReturn(['url' => 'http://127.0.0.1:6001', 'secret' => 'database-secret']);
        Http::fake(['http://127.0.0.1:6001/broadcast' => Http::response(['success' => true])]);

        $service->dispatchForTest(['event' => 'Test']);
        Http::assertSent(fn ($request) =>
            $request->url() === 'http://127.0.0.1:6001/broadcast'
            && $request->hasHeader('X-Bridge-Secret', 'database-secret')
        );
    }
}
