<?php

namespace Tests\Feature\Invoices;

use Illuminate\Support\Facades\Cache;
use Modules\Invoices\Services\GdtPosSecureContinuation;
use Tests\TestCase;

class GdtPosSecureContinuationTest extends TestCase
{
    public function test_encrypted_checkpoint_is_session_bound_and_single_use(): void
    {
        $service = app(GdtPosSecureContinuation::class);
        $rows = array_fill(0, 15, ['id' => 'synthetic-invoice']);
        $id = $service->store(7, 'browser-session-a', 'filters-hash', $rows, 'synthetic-cursor', 17);

        $this->assertNull($service->consume(8, 'browser-session-a', 'filters-hash', $id));
        $this->assertNull($service->consume(7, 'browser-session-b', 'filters-hash', $id));
        $this->assertNull($service->consume(7, 'browser-session-a', 'different-filters', $id));

        $key = 'invoices:gdt:pos-secure-continuation:7:'.hash('sha256', 'browser-session-a').':'.$id;
        $encrypted = Cache::get($key);
        $this->assertIsString($encrypted);
        $this->assertStringNotContainsString('synthetic-cursor', $encrypted);
        $this->assertStringNotContainsString('synthetic-invoice', $encrypted);

        $payload = $service->consume(7, 'browser-session-a', 'filters-hash', $id);
        $this->assertSame('synthetic-cursor', $payload['cursor']);
        $this->assertCount(15, $payload['first_page']);
        $this->assertSame(17, $payload['total']);
        $this->assertNull($service->consume(7, 'browser-session-a', 'filters-hash', $id));
    }

    public function test_invalid_checkpoint_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        app(GdtPosSecureContinuation::class)->store(7, 'session', 'filters', [], 'cursor', 17);
    }

    public function test_discard_removes_checkpoint_without_consuming_it(): void
    {
        $service = app(GdtPosSecureContinuation::class);
        $id = $service->store(7, 'session', 'filters', array_fill(0, 15, ['id' => 'fixture']), 'cursor', 17);
        $service->discard(7, 'session', $id);

        $this->assertNull($service->consume(7, 'session', 'filters', $id));
    }
    public function test_claim_lock_prevents_competing_consumer(): void
    {
        $service = app(GdtPosSecureContinuation::class);
        $id = $service->store(7, 'session', 'filters', array_fill(0, 15, ['id' => 'fixture']), 'cursor', 17);
        $key = 'invoices:gdt:pos-secure-continuation:7:'.hash('sha256', 'session').':'.$id;
        $lock = Cache::lock($key.':claim', 10);
        $this->assertTrue($lock->get());

        try {
            $this->expectException(\Illuminate\Contracts\Cache\LockTimeoutException::class);
            $service->consume(7, 'session', 'filters', $id);
        } finally {
            $lock->release();
        }
    }

}
