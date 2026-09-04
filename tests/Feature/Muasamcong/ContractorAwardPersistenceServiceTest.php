<?php

namespace Tests\Feature\Muasamcong;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Muasamcong\Models\ContractorManualLot;
use Modules\Muasamcong\Models\KqlcntAwardItem;
use Modules\Muasamcong\Models\KqlcntRecord;
use Modules\Muasamcong\Services\ContractorAwardPersistenceService;
use Modules\Muasamcong\Services\ContractorSearchArchiveService;
use Tests\TestCase;

class ContractorAwardPersistenceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_three_logical_awards_are_persisted_once_and_resync_is_idempotent(): void
    {
        $search = $this->storedSearch();
        $this->seedThreeLogicalAwards();
        $service = app(ContractorAwardPersistenceService::class);

        $first = $service->persist($search, 'IB2500539527');

        $this->assertSame(3, $first['count']);
        $this->assertSame(3, $first['created']);
        $this->assertSame(0, $first['updated']);
        $this->assertSame(0, $first['unchanged']);
        $this->assertDatabaseCount('muasamcong_kqlcnt_award_items', 3);
        $this->assertSame(3, KqlcntAwardItem::query()->where('is_active', true)->whereNotNull('synced_from_catalog_at')->count());

        $stored = KqlcntAwardItem::query()->orderBy('lot_no')->get();
        $this->assertTrue($stored->every(fn (KqlcntAwardItem $row): bool => $row->contractor_search_id === $search->id));
        $this->assertTrue($stored->every(fn (KqlcntAwardItem $row): bool => str_contains((string) $row->sync_source, 'MANUAL')));
        $this->assertTrue($stored->every(fn (KqlcntAwardItem $row): bool => str_contains((string) $row->sync_source, 'SMART_PRICING')));

        $second = $service->persist($search, 'IB2500539527');

        $this->assertSame(3, $second['count']);
        $this->assertSame(0, $second['created']);
        $this->assertSame(0, $second['updated']);
        $this->assertSame(3, $second['unchanged']);
        $this->assertDatabaseCount('muasamcong_kqlcnt_award_items', 3);
    }

    public function test_package_period_metadata_is_copied_to_awards_and_resync_is_idempotent(): void
    {
        $search = $this->storedSearch();
        KqlcntRecord::create([
            'notify_no' => 'IB2500539527',
            'contractor_code' => 'vn0314492345',
            'contract_period' => 730,
            'contract_period_unit' => 'D',
            'contract_period_text' => '730 ngày',
            'effect_frame_period' => 'Kể từ ngày ký đến hết 06/02/2028',
            'contracts' => [],
            'contracts_raw' => [],
            'tbmt_raw' => [],
        ]);
        $this->seedThreeLogicalAwards();
        $service = app(ContractorAwardPersistenceService::class);

        $first = $service->persist($search, 'IB2500539527');
        $stored = KqlcntAwardItem::query()->where('is_active', true)->get();

        $this->assertSame(3, $first['created']);
        $this->assertTrue($stored->every(fn (KqlcntAwardItem $row): bool => $row->contract_period === 730));
        $this->assertTrue($stored->every(fn (KqlcntAwardItem $row): bool => $row->contract_period_unit === 'D'));
        $this->assertTrue($stored->every(fn (KqlcntAwardItem $row): bool => $row->contract_period_text === '730 ngày'));
        $this->assertTrue($stored->every(fn (KqlcntAwardItem $row): bool => $row->effect_frame_period === 'Kể từ ngày ký đến hết 06/02/2028'));

        $second = $service->persist($search, 'IB2500539527');

        $this->assertSame(0, $second['created']);
        $this->assertSame(0, $second['updated']);
        $this->assertSame(3, $second['unchanged']);
    }

    public function test_resync_updates_changed_catalog_fingerprint_without_creating_duplicate(): void
    {
        $search = $this->storedSearch();
        $this->seedThreeLogicalAwards();
        $service = app(ContractorAwardPersistenceService::class);
        $service->persist($search, 'IB2500539527');

        $smart = ContractorManualLot::query()
            ->where('notify_no', 'IB2500539527')
            ->where('source', 'smart_pricing_verified')
            ->where('raw_payload->medicine_code', 'GEN01202')
            ->firstOrFail();
        $raw = $smart->raw_payload;
        $raw['winning_unit_price'] = 2800;
        $smart->update([
            'lot_price' => 2800,
            'plan_amount' => 245000 * 2800,
            'raw_payload' => $raw,
        ]);

        $result = $service->persist($search, 'IB2500539527');

        $this->assertSame(3, $result['count']);
        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['updated']);
        $this->assertSame(2, $result['unchanged']);
        $this->assertDatabaseCount('muasamcong_kqlcnt_award_items', 3);
        $this->assertSame(
            '2800.0000',
            KqlcntAwardItem::query()->where('medicine_code', 'GEN01202')->firstOrFail()->winning_price
        );
    }

    private function storedSearch()
    {
        return app(ContractorSearchArchiveService::class)->store(
            'vn0314492345',
            'CÔNG TY TNHH INAFO VIỆT NAM',
            '2021-01-01',
            null,
            [
                'reported_total' => 1,
                'total_pages' => 1,
                'items' => [[
                    'id' => '1',
                    'notifyNo' => 'IB2500539527',
                    'bidName' => 'Gói thầu thuốc Generic',
                    'createdDate' => '2026-08-01T00:00:00',
                ]],
            ]
        );
    }

    private function seedThreeLogicalAwards(): void
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
            ['GEN01202', 'Famodin 20mg', 'Famotidin', 245000, 2700],
            ['GEN01425', 'Pregabakern 25mg', 'Pregabalin', 80000, 7950],
            ['GEN03092', 'Glibenclamide 5mg; Metformin hydrochloride 1000mg', 'Glibenclamid + metformin', 270000, 3297],
        ];

        foreach ($smart as $index => [$medicineCode, $name, $ingredient, $quantity, $winningPrice]) {
            ContractorManualLot::create([
                'contractor_code' => 'vn0314492345',
                'notify_no' => 'IB2500539527',
                'lot_key' => 'smart:'.$index,
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
    }
}
