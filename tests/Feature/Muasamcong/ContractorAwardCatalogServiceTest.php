<?php

namespace Tests\Feature\Muasamcong;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Muasamcong\Models\ContractorManualLot;
use Modules\Muasamcong\Services\ContractorAwardCatalogService;
use Tests\TestCase;

class ContractorAwardCatalogServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_and_smart_pricing_rows_for_same_award_are_merged_once(): void
    {
        ContractorManualLot::create([
            'contractor_code' => 'vn0314492345',
            'notify_no' => 'IB2600119906',
            'lot_key' => 'lot:PP2600129158',
            'lot_no' => 'PP2600129158',
            'lot_name' => 'Gourcuff-2,5',
            'quantity' => 24000,
            'price_plan' => 3800,
            'lot_price' => 91200000,
            'plan_amount' => 91200000,
            'source' => 'manual',
            'raw_payload' => [
                'medicine_group' => 'Nhóm 2',
                'concentration' => '2,5mg',
                'route' => 'Uống',
            ],
        ]);

        ContractorManualLot::create([
            'contractor_code' => 'vn0314492345',
            'notify_no' => 'IB2600119906',
            'lot_key' => 'smart:sample',
            'lot_name' => 'Gourcuff-2,5',
            'medicine_name' => 'Gourcuff-2,5',
            'active_ingredient' => 'Alfuzosin HCl',
            'quantity' => 24000,
            'lot_price' => 3800,
            'plan_amount' => 91200000,
            'source' => 'smart_pricing_verified',
            'raw_payload' => [
                'contractor_name' => 'CÔNG TY TNHH INAFO VIỆT NAM',
                'winning_unit_price' => 3800,
                'medicine_name' => 'Gourcuff-2,5',
                'active_ingredient' => 'Alfuzosin HCl',
                'concentration' => '2,5mg',
                'route' => 'Uống',
                'medicine_group' => 'Nhóm 2',
            ],
        ]);

        $rows = app(ContractorAwardCatalogService::class)
            ->rows('vn0314492345', ['IB2600119906']);

        $this->assertCount(1, $rows);
        $row = $rows->first();
        $this->assertSame('PP2600129158', $row['lot_no']);
        $this->assertSame('Gourcuff-2,5', $row['medicine_name']);
        $this->assertSame('Alfuzosin HCl', $row['active_ingredient']);
        $this->assertSame(24000.0, $row['quantity']);
        $this->assertSame(3800.0, $row['winning_price']);
        $this->assertSame(91200000.0, $row['amount']);
        $this->assertSame('MANUAL+SMART_PRICING', $row['source']);
    }
}
