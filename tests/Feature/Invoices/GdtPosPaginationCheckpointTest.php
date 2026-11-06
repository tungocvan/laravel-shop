<?php

namespace Tests\Feature\Invoices;

use Illuminate\Support\Facades\Cache;
use Modules\Invoices\Services\GdtPosPaginationCheckpoint;
use Tests\TestCase;

class GdtPosPaginationCheckpointTest extends TestCase
{
    public function test_checkpoint_is_session_scoped_and_contains_only_progress_metadata(): void
    {
        Cache::flush();
        $service = app(GdtPosPaginationCheckpoint::class);
        $checkpoint = $service->create('session-one', 'pos', '2026-09-01', '2026-09-30', 15, 17);

        $this->assertSame(2, $checkpoint['next_page']);
        $this->assertSame(15, $checkpoint['received']);
        $this->assertSame(17, $checkpoint['total']);
        $this->assertNull($service->find('session-two', $checkpoint['id']));
        $this->assertSame($checkpoint, $service->find('session-one', $checkpoint['id']));
        foreach (['ckey', 'cvalue', 'token', 'cookies', 'state', 'datas', 'invoices'] as $sensitiveKey) {
            $this->assertArrayNotHasKey($sensitiveKey, $checkpoint);
        }

        $service->discard('session-one', $checkpoint['id']);
        $this->assertNull($service->find('session-one', $checkpoint['id']));
    }

    public function test_completed_search_cannot_create_incomplete_checkpoint(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        app(GdtPosPaginationCheckpoint::class)->create('session-one', 'pos', '2026-09-01', '2026-09-30', 17, 17);
    }

    public function test_invalid_checkpoint_id_is_rejected(): void
    {
        $this->assertNull(app(GdtPosPaginationCheckpoint::class)->find('session-one', '../other'));
    }
}
