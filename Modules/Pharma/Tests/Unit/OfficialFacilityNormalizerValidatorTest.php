<?php

namespace Modules\Pharma\Tests\Unit;

use Modules\Pharma\Services\OfficialFacilityImport\OfficialFacilityNormalizer;
use Modules\Pharma\Services\OfficialFacilityImport\OfficialFacilityValidator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OfficialFacilityNormalizerValidatorTest extends TestCase
{
    #[Test]
    public function it_normalizes_identity_without_inferring_province(): void
    {
        $normalizer = new OfficialFacilityNormalizer;

        $this->assertSame('benh vien da khoa can tho', $normalizer->identity("  Bệnh viện   Đa khoa Cần Thơ  "));
        $this->assertSame('1800123456-001', $normalizer->taxCode(' MST: 1800123456-001 '));
    }

    #[Test]
    public function canonical_province_is_required(): void
    {
        $errors = (new OfficialFacilityValidator)->validate([
            'facility_name' => 'Bệnh viện A',
            'province_code' => null,
            'email' => null,
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Mã tỉnh canonical', implode(' ', $errors));
    }

    #[Test]
    public function invalid_email_is_rejected(): void
    {
        $errors = (new OfficialFacilityValidator)->validate([
            'facility_name' => 'Bệnh viện A',
            'province_code' => '92',
            'email' => 'not-an-email',
        ]);

        $this->assertStringContainsString('Email không hợp lệ', implode(' ', $errors));
    }
}
