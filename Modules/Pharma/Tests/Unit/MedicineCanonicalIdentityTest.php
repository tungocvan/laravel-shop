<?php

namespace Modules\Pharma\Tests\Unit;

use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Services\MedicineCatalogNormalizer;
use Modules\Pharma\Services\MedicineIdentityResolver;
use LogicException;
use Modules\Pharma\Services\MedicineService;
use Modules\Pharma\Services\MedicineSkuGenerator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MedicineCanonicalIdentityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function same_registration_and_name_with_distinct_packaging_have_distinct_medicine_identities(): void
    {
        $resolver = new MedicineIdentityResolver;
        $first = ['registration_number' => '893110138900', 'name' => 'Pitamsol', 'strength_text' => '2.400mg; 7,2ml', 'dosage_form' => 'Dung dịch uống', 'presentation_text' => '7,2ml'];
        $second = ['registration_number' => '893110138900', 'name' => 'Pitamsol', 'strength_text' => '2.400mg; 24ml', 'dosage_form' => 'Dung dịch uống', 'presentation_text' => '24ml'];
        $this->assertNotSame($resolver->canonicalMedicineIdentity($first), $resolver->canonicalMedicineIdentity($second));
        $this->assertNotSame($resolver->canonicalVariantIdentity($first), $resolver->canonicalVariantIdentity($second));
    }

    #[Test]
    public function identical_ozdectin_rows_resolve_to_same_variant_identity(): void
    {
        $resolver = new MedicineIdentityResolver;
        $row = ['registration_number' => '893100952824', 'name' => 'Ozdectin', 'strength_text' => '275,5mg', 'dosage_form' => 'Viên nén', 'presentation_text' => 'Hộp 10 vỉ x 10 viên'];
        $this->assertSame($resolver->canonicalVariantIdentity($row), $resolver->canonicalVariantIdentity($row));
    }

    #[Test]
    public function registration_parser_keeps_raw_source_but_extracts_primary_value(): void
    {
        $normalizer = new MedicineCatalogNormalizer;
        $this->assertSame('893110660724', $normalizer->registrationPrimary("893110660724\n(VD-28911-18)"));
        $this->assertSame('893110660724', $normalizer->registration(" 893110660724\n(VD-28911-18) "));
    }

    #[Test]
    public function sku_is_deterministic_and_changes_for_distinct_presentations(): void
    {
        $generator = new MedicineSkuGenerator;
        $base = ['registration_number' => '893110138900', 'name' => 'Pitamsol', 'strength_text' => '2.400mg', 'dosage_form' => 'Dung dịch uống'];
        $first = $generator->generate($base + ['presentation_text' => '7,2ml']);
        $repeat = $generator->generate($base + ['presentation_text' => '7,2ml']);
        $second = $generator->generate($base + ['presentation_text' => '24ml']);
        $this->assertSame($first, $repeat);
        $this->assertNotSame($first['sku'], $second['sku']);
        $this->assertNotSame($first['basis_hash'], $second['basis_hash']);
    }

    #[Test]
    public function product_name_drug_name_and_brand_name_are_one_canonical_name_concept(): void
    {
        $resolver = new MedicineIdentityResolver;
        $byName = $resolver->canonicalMedicineIdentity(['registration_number' => 'VD-34495-20', 'name' => 'Sallet']);
        $byBrandName = $resolver->canonicalMedicineIdentity(['registration_number' => 'VD-34495-20', 'brand_name' => 'Sallet']);
        $this->assertSame($byName, $byBrandName);
    }

    #[Test]
    public function medicine_code_is_deterministic_from_canonical_medicine_id(): void
    {
        $this->assertSame('MED-000001', Medicine::codeForId(1));
        $this->assertSame('MED-000177', Medicine::codeForId(177));
        $this->assertSame('MED-001234', Medicine::codeForId(1234));
    }

    #[Test]
    public function missing_medicine_codes_have_a_safe_idempotent_backfill_migration(): void
    {
        $migration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_15_183000_backfill_missing_medicine_codes.php'));
        $model = file_get_contents(base_path('Modules/Pharma/Models/Medicine.php'));
        $this->assertStringContainsString("sprintf('MED-%06d', \$medicine->id)", $migration);
        $this->assertStringContainsString("whereNull('medicine_code')", $migration);
        $this->assertStringContainsString("orWhere('medicine_code', '')", $migration);
        $this->assertStringContainsString('codeAlreadyBelongsToAnotherMedicine', $migration);
        $this->assertStringContainsString('static::created(', $model);
        $this->assertStringContainsString('saveQuietly()', $model);
    }
    #[Test]
    public function manual_edit_keeps_registration_fields_canonical_and_rejects_duplicate_identity(): void
    {
        $first = Medicine::query()->create([
            'name' => 'Medicine A',
            'registration_number' => 'REG-A',
            'packaging_specification' => 'Hộp 10 viên',
            'registration_number_raw' => 'REG-A',
            'registration_number_primary' => 'REG-A',
            'canonical_identity_key' => app(MedicineIdentityResolver::class)->canonicalMedicineIdentity([
                'name' => 'Medicine A',
                'registration_number' => 'REG-A',
                'packaging_specification' => 'Hộp 10 viên',
            ]),
        ]);
        $second = Medicine::query()->create([
            'name' => 'Medicine B',
            'registration_number' => 'REG-B',
            'packaging_specification' => 'Hộp 20 viên',
            'registration_number_raw' => 'REG-B',
            'registration_number_primary' => 'REG-B',
            'canonical_identity_key' => app(MedicineIdentityResolver::class)->canonicalMedicineIdentity([
                'name' => 'Medicine B',
                'registration_number' => 'REG-B',
                'packaging_specification' => 'Hộp 20 viên',
            ]),
        ]);

        $updated = app(MedicineService::class)->update($first->id, [
            'name' => 'Medicine A',
            'registration_number' => " REG-C\n",
            'packaging_specification' => 'Hộp 10 viên',
        ]);

        $this->assertSame('REG-C', $updated->registration_number);
        $this->assertSame('REG-C', $updated->registration_number_primary);
        $this->assertSame('REG-C', $updated->registration_number_raw);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('trùng Medicine Master');

        app(MedicineService::class)->update($updated->id, [
            'name' => $second->name,
            'registration_number' => $second->registration_number,
            'packaging_specification' => $second->packaging_specification,
        ]);
    }

    #[Test]
    public function medicine_master_verification_requires_complete_registration_identity(): void
    {
        $medicine = Medicine::query()->create([
            'name' => 'Verification candidate',
            'registration_number' => null,
            'profile_status' => Medicine::PROFILE_INCOMPLETE,
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Hãy lưu lại đầy đủ thông tin Nhà sản xuất & thông tin quản lý rồi xác nhận lại.');

        app(MedicineService::class)->verifyMaster($medicine->id);
    }

    #[Test]
    public function complete_medicine_master_can_be_verified_for_bid_linking(): void
    {
        $medicine = Medicine::query()->create([
            'name' => 'Verified candidate',
            'registration_number' => 'VD-TEST-01',
            'active_ingredients' => 'Paracetamol',
            'concentration' => '500mg',
            'dosage_form' => 'Viên nén',
            'route_of_administration' => 'Uống',
            'unit' => 'Viên',
            'packaging_specification' => 'Hộp 10 vỉ x 10 viên',
            'shelf_life' => '36 tháng',
            'registered_company' => 'Registrant',
            'manufacturing_company' => 'Manufacturer',
            'manufacturing_country' => 'Việt Nam',
            'profile_status' => Medicine::PROFILE_COMPLETE,
        ]);

        $verified = app(MedicineService::class)->verifyMaster($medicine->id);

        $this->assertSame(Medicine::PROFILE_VERIFIED, $verified->profile_status);
        $this->assertSame(Medicine::IDENTITY_VERIFIED_REGISTRATION, $verified->identity_status);
        $this->assertNotNull($verified->last_verified_at);
    }

}
