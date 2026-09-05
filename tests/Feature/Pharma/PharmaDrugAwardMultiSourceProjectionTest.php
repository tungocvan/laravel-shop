<?php

namespace Tests\Feature\Pharma;

use Illuminate\Support\Facades\Schema;
use Modules\Pharma\Data\DrugAwardProjectionData;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Models\DrugBidAwardSource;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Models\MedicineSource;
use Modules\Pharma\Services\DrugAwardProjectionService;
use Tests\TestCase;

class PharmaDrugAwardMultiSourceProjectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('PDO SQLite is required for Pharma projection database tests.');
        }

        Schema::dropIfExists('pharma_drug_bid_award_sources');
        Schema::dropIfExists('pharma_medicine_sources');
        Schema::dropIfExists('pharma_drug_bid_awards');
        Schema::dropIfExists('pharma_medicines');

        (require base_path('Modules/Pharma/database/migrations/2026_05_21_145242_create_medicines_table.php'))->up();
        (require base_path('Modules/Pharma/database/migrations/2026_05_22_135028_create_drug_bid_awards_table.php'))->up();
        (require base_path('Modules/Pharma/database/migrations/2026_08_30_010000_add_source_identity_to_drug_bid_awards_table.php'))->up();
        (require base_path('Modules/Pharma/database/migrations/2026_09_05_010000_add_intelligence_fields_to_medicines_table.php'))->up();
        (require base_path('Modules/Pharma/database/migrations/2026_09_05_011000_create_medicine_sources_table.php'))->up();
        (require base_path('Modules/Pharma/database/migrations/2026_09_05_012000_add_intelligence_fields_to_drug_bid_awards_table.php'))->up();
        (require base_path('Modules/Pharma/database/migrations/2026_09_05_013000_create_drug_bid_award_sources_table.php'))->up();
        (require base_path('Modules/Pharma/database/migrations/2026_09_05_014000_relax_legacy_drug_award_constraints.php'))->up();
    }

    public function test_projection_is_idempotent_and_preserves_hssp_fallback_provenance(): void
    {
        $medicine = Medicine::query()->create($this->medicineData());
        $source = $this->sourceData(route: null);

        $first = app(DrugAwardProjectionService::class)->project($source);
        $second = app(DrugAwardProjectionService::class)->project($source);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, DrugBidAward::query()->count());
        $this->assertSame(1, DrugBidAwardSource::query()->count());
        $this->assertSame(1, MedicineSource::query()->count());
        $this->assertSame($medicine->id, $second->medicine_id);
        $this->assertNull($second->route);
        $this->assertSame(
            ['value' => 'Uống', 'origin' => 'hssp'],
            $second->effectiveMedicineAttribute('route'),
        );
    }

    public function test_two_source_records_can_resolve_to_one_canonical_award_with_two_lineages(): void
    {
        Medicine::query()->create($this->medicineData());
        $service = app(DrugAwardProjectionService::class);

        $service->project($this->sourceData(sourceRecordKey: 'source-a'));
        $service->project($this->sourceData(sourceRecordKey: 'source-b'));

        $this->assertSame(1, DrugBidAward::query()->count());
        $this->assertSame(2, DrugBidAwardSource::query()->count());
    }

    public function test_same_notice_and_contractor_with_different_lots_remain_distinct_awards(): void
    {
        Medicine::query()->create($this->medicineData());
        $service = app(DrugAwardProjectionService::class);

        $service->project($this->sourceData(sourceRecordKey: 'lot-1', lotNo: '01'));
        $service->project($this->sourceData(sourceRecordKey: 'lot-2', lotNo: '02'));

        $this->assertSame(2, DrugBidAward::query()->count());
        $this->assertSame(2, DrugBidAwardSource::query()->count());
    }

    public function test_strong_unmatched_source_creates_provisional_hssp_without_copying_unknown_license_semantics(): void
    {
        $award = app(DrugAwardProjectionService::class)->project($this->sourceData(
            sourceRecordKey: 'new-drug',
            registration: 'GPNK-UNKNOWN-TYPE',
        ));

        $medicine = $award->medicine;

        $this->assertNotNull($medicine);
        $this->assertSame(Medicine::IDENTITY_PROVISIONAL, $medicine->identity_status);
        $this->assertSame(Medicine::PROFILE_NEEDS_REVIEW, $medicine->profile_status);
        $this->assertNull($medicine->registration_number);
        $this->assertSame('GPNK-UNKNOWN-TYPE', $award->registration_or_import_license);
    }

    private function medicineData(array $overrides = []): array
    {
        return array_merge([
            'canonical_identity_key' => hash('sha256', 'medicine-1'),
            'identity_status' => Medicine::IDENTITY_VERIFIED_REGISTRATION,
            'profile_status' => Medicine::PROFILE_VERIFIED,
            'active_ingredients' => 'Meloxicam',
            'concentration' => '15mg',
            'name' => 'Trosicam 15mg',
            'dosage_form' => 'Viên hòa tan nhanh',
            'route_of_administration' => 'Uống',
            'unit' => 'Viên',
            'packaging_specification' => 'Hộp 3 vỉ x 10 viên',
            'registration_number' => 'VN-20104-16',
            'shelf_life' => '36 tháng',
            'shelf_life_months' => 36,
            'registered_company' => 'Alpex Pharma SA',
            'manufacturing_company' => 'Alpex Pharma SA',
            'manufacturing_country' => 'Thụy Sĩ',
            'is_special_control' => false,
        ], $overrides);
    }

    private function sourceData(
        string $sourceRecordKey = 'source-1',
        ?string $route = 'Uống',
        string $lotNo = '01',
        ?string $registration = 'VN-20104-16',
    ): DrugAwardProjectionData {
        return new DrugAwardProjectionData(
            sourceSystem: 'muasamcong',
            sourceRecordType: 'kqlcnt_award_item',
            sourceRecordKey: $sourceRecordKey,
            medicineName: 'Trosicam 15mg',
            notifyNo: 'IB260000001',
            lotNo: $lotNo,
            lotName: 'Gói thuốc generic',
            medicineCode: null,
            activeIngredient: 'Meloxicam',
            concentration: '15mg',
            route: $route,
            dosageForm: 'Viên hòa tan nhanh',
            unit: 'Viên',
            packagingSpec: 'Hộp 3 vỉ x 10 viên',
            shelfLifeMonths: 36,
            registrationOrImportLicense: $registration,
            manufacturer: 'Alpex Pharma SA',
            country: 'Thụy Sĩ',
            quantity: '1200.0000',
            pricePlan: '9000.0000',
            winningPrice: '8500.0000',
            amount: null,
            contractorCode: 'CONTRACTOR-01',
            contractorName: 'Công ty Dược A',
            investorCode: 'INVESTOR-01',
            investorName: 'Bệnh viện A',
            decisionNo: '123/QD-BV',
            decisionDate: '2026-08-20',
            publishedAt: '2026-08-21 08:00:00',
            contractNo: 'HD-01',
            contractPeriod: 12,
            contractPeriodUnit: 'M',
            contractPeriodText: '12 tháng',
            effectFramePeriod: 'Từ ngày ký hợp đồng',
            sourceReference: 'muasamcong_kqlcnt_award_items:1',
            sourceChannel: 'catalog',
            syncSource: 'API_SNAPSHOT|CATALOG',
            payloadHash: hash('sha256', $sourceRecordKey),
            lastVerifiedAt: '2026-08-21 09:00:00',
            isActive: true,
        );
    }
}
