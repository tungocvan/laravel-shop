<?php

namespace Modules\Pharma\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Partner\Models\Partner;
use Modules\Partner\Models\PartnerSourceReference;
use Modules\Pharma\Services\OfficialFacilityImport\OfficialFacilityMatcher;
use Modules\Pharma\Services\OfficialFacilityImport\OfficialFacilityNormalizer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OfficialFacilityMatcherTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function source_identity_has_highest_priority(): void
    {
        $partner = $this->hospital(['name' => 'Bệnh viện Canonical']);
        PartnerSourceReference::query()->create([
            'partner_id' => $partner->id,
            'source' => 'bhxh',
            'external_id' => '92-001',
        ]);

        $match = app(OfficialFacilityMatcher::class)->match($this->row(['external_id' => '92-001', 'tax_code' => '9999999999']), 'bhxh');

        $this->assertSame('EXACT', $match['classification']);
        $this->assertSame('source_external_id', $match['method']);
        $this->assertSame($partner->id, $match['partner_id']);
    }

    #[Test]
    public function normalized_name_and_province_is_only_likely_match(): void
    {
        $partner = $this->hospital(['name' => 'Bệnh viện Đa khoa Cần Thơ', 'province_code' => '92']);
        $normalizer = app(OfficialFacilityNormalizer::class);

        $match = app(OfficialFacilityMatcher::class)->match($this->row([
            'facility_name' => 'BENH VIEN DA KHOA CAN THO',
            'normalized_name' => $normalizer->identity('BENH VIEN DA KHOA CAN THO'),
            'external_id' => null,
            'tax_code' => null,
            'province_code' => '92',
        ]), 'bhxh');

        $this->assertSame('LIKELY_MATCH', $match['classification']);
        $this->assertSame($partner->id, $match['partner_id']);
    }

    #[Test]
    public function tax_code_collision_with_non_hospital_is_conflict(): void
    {
        Partner::query()->create([
            'name' => 'Công ty ABC',
            'tax_code' => '1800123456',
            'legal_type' => 'company',
            'partner_types' => ['supplier'],
            'source' => 'manual',
            'status' => 'active',
        ]);

        $match = app(OfficialFacilityMatcher::class)->match($this->row(['tax_code' => '1800123456', 'external_id' => null]), 'bhxh');

        $this->assertSame('CONFLICT', $match['classification']);
        $this->assertSame('tax_code_non_hospital', $match['method']);
    }

    private function hospital(array $overrides = []): Partner
    {
        return Partner::query()->create(array_merge([
            'name' => 'Bệnh viện Test',
            'legal_type' => 'hospital',
            'partner_types' => ['customer'],
            'source' => 'manual',
            'status' => 'active',
        ], $overrides));
    }

    private function row(array $overrides = []): array
    {
        return array_merge([
            'external_id' => null,
            'facility_name' => 'Bệnh viện Test',
            'normalized_name' => app(OfficialFacilityNormalizer::class)->identity('Bệnh viện Test'),
            'tax_code' => null,
            'address' => null,
            'normalized_address' => null,
            'province_code' => '92',
            'validation_errors' => [],
        ], $overrides);
    }
}
