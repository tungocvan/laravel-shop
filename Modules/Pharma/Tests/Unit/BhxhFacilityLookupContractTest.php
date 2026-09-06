<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BhxhFacilityLookupContractTest extends TestCase
{
    #[Test]
    public function routes_are_admin_only_and_use_existing_official_facility_view_permission(): void
    {
        $routes = file_get_contents(base_path('Modules/Pharma/routes/web.php'));

        $this->assertStringContainsString("'/official-facilities/bhxh'", $routes);
        $this->assertStringContainsString("'/official-facilities/bhxh/captcha'", $routes);
        $this->assertStringContainsString("'/official-facilities/bhxh/lookup'", $routes);
        $this->assertStringContainsString('can:view_pharma_official_facilities', $routes);
    }

    #[Test]
    public function live_lookup_does_not_write_partner_or_staging_records_and_uses_one_explicit_source_partition(): void
    {
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/BhxhOfficialFacilityLookupController.php'));

        $this->assertStringNotContainsString('Partner::', $controller);
        $this->assertStringNotContainsString('OfficialFacilityImportBatch::', $controller);
        $this->assertStringNotContainsString('OfficialFacilityImportRow::', $controller);
        $this->assertStringContainsString("'source_partition'", $controller);
        $this->assertStringContainsString('resolveSourcePartition', $controller);
        $this->assertStringNotContainsString('retry_province_code', $controller);
        $this->assertStringNotContainsString('SESSION_RESOLVED_PROVINCES', $controller);
    }

    #[Test]
    public function workspace_keeps_human_captcha_boundary_and_exposes_source_partition(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/pages/official-facilities/bhxh.blade.php'));

        $this->assertStringContainsString('Không OCR / không bypass CAPTCHA', $view);
        $this->assertStringContainsString('Vùng dữ liệu BHXH', $view);
        $this->assertStringContainsString('Địa bàn BHXH', $view);
        $this->assertStringContainsString('source_partition', $view);
        $this->assertStringContainsString('CAPTCHA BHXH', $view);
    }
}
