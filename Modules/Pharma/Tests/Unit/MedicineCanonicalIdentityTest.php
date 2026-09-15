<?php

namespace Modules\Pharma\Tests\Unit;

use Modules\Pharma\Services\MedicineCatalogNormalizer;
use Modules\Pharma\Services\MedicineIdentityResolver;
use Modules\Pharma\Services\MedicineSkuGenerator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MedicineCanonicalIdentityTest extends TestCase
{
    #[Test]
    public function same_registration_and_name_can_have_distinct_strength_variants(): void
    {
        $resolver = new MedicineIdentityResolver;

        $first = [
            'registration_number' => '893110138900',
            'name' => 'Pitamsol',
            'strength_text' => '2.400mg; 7,2ml',
            'dosage_form' => 'Dung dịch uống',
            'presentation_text' => '7,2ml',
        ];

        $second = [
            'registration_number' => '893110138900',
            'name' => 'Pitamsol',
            'strength_text' => '2.400mg; 24ml',
            'dosage_form' => 'Dung dịch uống',
            'presentation_text' => '24ml',
        ];

        $this->assertSame(
            $resolver->canonicalMedicineIdentity($first),
            $resolver->canonicalMedicineIdentity($second),
        );
        $this->assertNotSame(
            $resolver->canonicalVariantIdentity($first),
            $resolver->canonicalVariantIdentity($second),
        );
    }

    #[Test]
    public function identical_ozdectin_rows_resolve_to_same_variant_identity(): void
    {
        $resolver = new MedicineIdentityResolver;

        $row = [
            'registration_number' => '893100952824',
            'name' => 'Ozdectin',
            'strength_text' => '275,5mg',
            'dosage_form' => 'Viên nén',
            'presentation_text' => 'Hộp 10 vỉ x 10 viên',
        ];

        $this->assertSame(
            $resolver->canonicalVariantIdentity($row),
            $resolver->canonicalVariantIdentity($row),
        );
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

        $base = [
            'registration_number' => '893110138900',
            'name' => 'Pitamsol',
            'strength_text' => '2.400mg',
            'dosage_form' => 'Dung dịch uống',
        ];

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

        $byName = $resolver->canonicalMedicineIdentity([
            'registration_number' => 'VD-34495-20',
            'name' => 'Sallet',
        ]);

        $byBrandName = $resolver->canonicalMedicineIdentity([
            'registration_number' => 'VD-34495-20',
            'brand_name' => 'Sallet',
        ]);

        $this->assertSame($byName, $byBrandName);
    }
}
