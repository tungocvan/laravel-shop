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

    public function test_multiple_awards_match_by_medicine_code_even_when_smart_rows_are_shuffled(): void
    {
        $manual = [
            ['PP2500561840', 'GEN01202', 'Famotidin', 245000, 2700],
            ['PP2500562063', 'GEN01425', 'Pregabalin', 80000, 7950],
            ['PP2500562747', 'GEN03092', 'Glibenclamid + metformin', 270000, 3297],
        ];

        foreach ($manual as [$lotNo, $medicineCode, $ingredient, $quantity, $price]) {
            ContractorManualLot::create([
                'contractor_code' => 'vn0314492345',
                'notify_no' => 'IB2500539527',
                'lot_key' => 'lot:'.$lotNo,
                'lot_no' => $lotNo,
                'lot_name' => $ingredient,
                'active_ingredient' => $ingredient,
                'quantity' => $quantity,
                'price_plan' => $price,
                'lot_price' => $quantity * $price,
                'plan_amount' => $quantity * $price,
                'source' => 'manual',
                'raw_payload' => ['medicine_code' => $medicineCode],
            ]);
        }

        $smart = [
            ['GEN03092', 'Thuốc C', 'Glibenclamid + metformin', 270000, 3200],
            ['GEN01202', 'Thuốc A', 'Famotidin', 245000, 2600],
            ['GEN01425', 'Thuốc B', 'Pregabalin', 80000, 7800],
        ];

        foreach ($smart as $index => [$medicineCode, $name, $ingredient, $quantity, $winningPrice]) {
            ContractorManualLot::create([
                'contractor_code' => 'vn0314492345',
                'notify_no' => 'IB2500539527',
                'lot_key' => 'smart:'.$index,
                'lot_name' => $name,
                'medicine_name' => $name,
                'active_ingredient' => $ingredient,
                'quantity' => $quantity,
                'lot_price' => $winningPrice,
                'plan_amount' => $quantity * $winningPrice,
                'source' => 'smart_pricing_verified',
                'raw_payload' => [
                    'medicine_code' => $medicineCode,
                    'medicine_name' => $name,
                    'active_ingredient' => $ingredient,
                    'winning_unit_price' => $winningPrice,
                    'manufacturer' => 'NSX '.$medicineCode,
                    'country' => 'Việt Nam',
                    'decision_no' => 'QD-'.$medicineCode,
                ],
            ]);
        }

        $rows = app(ContractorAwardCatalogService::class)
            ->rows('vn0314492345', ['IB2500539527'])
            ->keyBy('medicine_code');

        $this->assertCount(3, $rows);
        $this->assertSame('PP2500561840', $rows['GEN01202']['lot_no']);
        $this->assertSame('Thuốc A', $rows['GEN01202']['medicine_name']);
        $this->assertSame(2600.0, $rows['GEN01202']['winning_price']);
        $this->assertSame('NSX GEN01202', $rows['GEN01202']['manufacturer']);
        $this->assertSame('PP2500562063', $rows['GEN01425']['lot_no']);
        $this->assertSame('Thuốc B', $rows['GEN01425']['medicine_name']);
        $this->assertSame('PP2500562747', $rows['GEN03092']['lot_no']);
        $this->assertSame('Thuốc C', $rows['GEN03092']['medicine_name']);
        $this->assertTrue($rows->every(fn (array $row): bool => $row['source'] === 'MANUAL+SMART_PRICING'));
    }

    public function test_ambiguous_quantity_and_price_candidates_are_not_merged_arbitrarily(): void
    {
        ContractorManualLot::create([
            'contractor_code' => 'vn0314492345',
            'notify_no' => 'IB-AMBIGUOUS',
            'lot_key' => 'lot:1',
            'lot_no' => 'LOT-1',
            'quantity' => 100,
            'price_plan' => 5000,
            'source' => 'manual',
        ]);

        foreach ([1, 2] as $index) {
            ContractorManualLot::create([
                'contractor_code' => 'vn0314492345',
                'notify_no' => 'IB-AMBIGUOUS',
                'lot_key' => 'smart:'.$index,
                'medicine_name' => 'Candidate '.$index,
                'quantity' => 100,
                'lot_price' => 5000,
                'source' => 'smart_pricing_verified',
                'raw_payload' => ['winning_unit_price' => 5000],
            ]);
        }

        $rows = app(ContractorAwardCatalogService::class)
            ->rows('vn0314492345', ['IB-AMBIGUOUS']);

        $this->assertCount(3, $rows);
        $manual = $rows->firstWhere('lot_no', 'LOT-1');
        $this->assertSame('MANUAL', $manual['source']);
        $this->assertNull($manual['medicine_name']);
    }
}
