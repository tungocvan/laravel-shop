<?php

namespace Tests\Feature\Muasamcong;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Muasamcong\Services\PricingSearchSnapshotService;
use Tests\TestCase;

class PricingSearchSnapshotServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reuses_normalized_keyword_and_preserves_source_search_time(): void
    {
        $service = app(PricingSearchSnapshotService::class);
        $result = [
            'success' => true,
            'status' => 200,
            'data' => [
                'total' => 2,
                'items' => [
                    ['id' => 'a', 'tenThuoc' => 'A'],
                    ['id' => 'b', 'tenThuoc' => 'B'],
                ],
            ],
            'message' => null,
        ];

        $stored = $service->store(' IB2500539527 ', $result, 10);
        $searchedAt = $stored->searched_at?->toIso8601String();

        $found = $service->find('ib2500539527');

        $this->assertNotNull($found);
        $this->assertSame($result, $found->result_payload);
        $this->assertSame(2, $found->loaded_total);
        $this->assertSame(2, $found->source_total);
        $this->assertSame($searchedAt, $found->searched_at?->toIso8601String());
        $this->assertSame(2, $found->access_count);
        $this->assertNotNull($found->last_accessed_at);
    }

    public function test_store_refreshes_existing_snapshot_instead_of_creating_duplicate(): void
    {
        $service = app(PricingSearchSnapshotService::class);

        $service->store('INAFO', [
            'success' => true,
            'data' => ['total' => 1, 'items' => [['id' => 'old']]],
        ], 10);

        $service->store(' inafo ', [
            'success' => true,
            'data' => ['total' => 2, 'items' => [['id' => 'new-1'], ['id' => 'new-2']]],
        ], 11);

        $this->assertDatabaseCount('muasamcong_pricing_search_snapshots', 1);

        $snapshot = $service->find('INAFO');

        $this->assertNotNull($snapshot);
        $this->assertSame(2, $snapshot->loaded_total);
        $this->assertSame(11, $snapshot->searched_by);
        $this->assertSame('new-1', $snapshot->result_payload['data']['items'][0]['id']);
    }

    public function test_it_can_delete_one_snapshot_and_clear_all_snapshots(): void
    {
        $service = app(PricingSearchSnapshotService::class);
        $result = [
            'success' => true,
            'data' => ['total' => 1, 'items' => [['id' => 'row-1']]],
        ];

        $service->store('IB2600117160', $result, 10);
        $service->store('IB2500539527', $result, 10);

        $this->assertTrue($service->delete(' ib2600117160 '));
        $this->assertNull($service->find('IB2600117160'));
        $this->assertDatabaseCount('muasamcong_pricing_search_snapshots', 1);

        $this->assertSame(1, $service->clear());
        $this->assertDatabaseCount('muasamcong_pricing_search_snapshots', 0);
    }
}
