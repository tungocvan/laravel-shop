<?php

namespace Tests\Feature\Muasamcong;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Muasamcong\Services\ContractorSearchArchiveService;
use Tests\TestCase;

class ContractorSearchArchiveServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_normalizes_tax_code_and_reuses_stored_contractor(): void
    {
        $service = app(ContractorSearchArchiveService::class);

        $this->assertSame('vn0315681994', $service->normalizeContractorCode('0315681994'));
        $this->assertSame('vn0315681994', $service->normalizeContractorCode('VN0315681994'));

        $stored = $service->store(
            '0315681994',
            'CÔNG TY TNHH TM DƯỢC PHẨM KHANG TÍN',
            '2021-01-01',
            null,
            [
                'reported_total' => 2,
                'total_pages' => 1,
                'items' => [
                    ['id' => 1, 'notifyNo' => 'IB001', 'bidName' => 'Gói 1', 'createdDate' => '2026-08-01T00:00:00'],
                    ['id' => 2, 'notifyNo' => 'IB002', 'bidName' => 'Gói 2', 'createdDate' => '2026-07-01T00:00:00'],
                ],
            ]
        );

        $this->assertSame('vn0315681994', $stored->contractor_code);
        $this->assertSame('0315681994', $stored->tax_code);
        $this->assertNotNull($service->findByCode('0315681994'));
        $this->assertCount(1, $service->findByName('KHANG TÍN'));
        $this->assertDatabaseCount('muasamcong_contractor_searches', 1);
        $this->assertDatabaseCount('muasamcong_contractor_search_items', 2);
    }

    public function test_it_refreshes_snapshot_and_pages_from_database(): void
    {
        $service = app(ContractorSearchArchiveService::class);
        $items = [];

        for ($i = 1; $i <= 25; $i++) {
            $items[] = [
                'id' => $i,
                'notifyNo' => 'IB'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'bidName' => 'Gói '.$i,
                'createdDate' => sprintf('2026-07-%02dT00:00:00', (($i - 1) % 28) + 1),
            ];
        }

        $stored = $service->store('vn0315681994', 'KHANG TÍN', '2021-01-01', null, [
            'reported_total' => 25,
            'total_pages' => 3,
            'items' => $items,
        ]);

        $page = $service->page($stored, 2, 10);

        $this->assertSame(25, $page['total']);
        $this->assertSame(2, $page['page']);
        $this->assertSame(3, $page['total_pages']);
        $this->assertCount(10, $page['items']);
    }
}
