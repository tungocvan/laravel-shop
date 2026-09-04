<?php

namespace Tests\Feature\Muasamcong;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Modules\Muasamcong\Models\ContractorManualLot;
use Modules\Muasamcong\Services\ContractorAwardEnrichmentService;
use Modules\Muasamcong\Services\SmartPricingAwardService;
use Tests\TestCase;

class ContractorAwardEnrichmentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_every_smart_pricing_award_and_is_idempotent(): void
    {
        $items = [
            $this->item('a', 'GEN01202', 'Thuốc A', 245000, 2600),
            $this->item('b', 'GEN01425', 'Thuốc B', 80000, 7800),
            $this->item('c', 'GEN03092', 'Thuốc C', 270000, 3200),
        ];

        $smartPricing = Mockery::mock(SmartPricingAwardService::class);
        $smartPricing->shouldReceive('forContractor')
            ->twice()
            ->with('IB2500539527', 'vn0314492345')
            ->andReturn([
                'items' => $items,
                'total_source' => 3,
                'pages_fetched' => 1,
                'total_pages' => 1,
                'truncated' => false,
            ]);

        $service = new ContractorAwardEnrichmentService($smartPricing);
        $first = $service->sync('IB2500539527', 'vn0314492345');
        $second = $service->sync('IB2500539527', 'vn0314492345');

        $this->assertSame(3, $first['count']);
        $this->assertSame(3, $second['count']);
        $this->assertSame(3, ContractorManualLot::query()
            ->where('notify_no', 'IB2500539527')
            ->where('contractor_code', 'vn0314492345')
            ->where('source', 'smart_pricing_verified')
            ->count());

        $row = ContractorManualLot::query()->where('lot_key', 'smart:a')->firstOrFail();
        $this->assertSame('Thuốc A', $row->medicine_name);
        $this->assertSame('2600.0000', $row->lot_price);
        $this->assertSame('637000000.0000', $row->plan_amount);
        $this->assertSame('GEN01202', data_get($row->raw_payload, 'medicine_code'));
    }

    private function item(string $key, string $medicineCode, string $medicineName, int $quantity, int $price): array
    {
        return [
            'source_key' => $key,
            'notify_no' => 'IB2500539527',
            'contractor_code' => 'vn0314492345',
            'contractor_name' => 'CÔNG TY TNHH INAFO VIỆT NAM',
            'medicine_code' => $medicineCode,
            'medicine_name' => $medicineName,
            'active_ingredient' => $medicineName,
            'quantity' => $quantity,
            'winning_unit_price' => $price,
            'decision_no' => 'QD-'.$medicineCode,
            'manufacturer' => 'NSX '.$medicineCode,
            'country' => 'Việt Nam',
            'raw_payload' => ['medicineCode' => $medicineCode],
        ];
    }
}
