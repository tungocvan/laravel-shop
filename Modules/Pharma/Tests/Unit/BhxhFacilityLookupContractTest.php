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
    public function live_lookup_does_not_write_partner_or_staging_records(): void
    {
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/BhxhOfficialFacilityLookupController.php'));

        $this->assertStringNotContainsString('Partner::', $controller);
        $this->assertStringNotContainsString('OfficialFacilityImportBatch::', $controller);
        $this->assertStringNotContainsString('OfficialFacilityImportRow::', $controller);
    }

    #[Test]
    public function workspace_states_that_captcha_is_human_entered(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/pages/official-facilities/bhxh.blade.php'));

        $this->assertStringContainsString('phải nhập thủ công', $view);
        $this->assertStringContainsString('Không OCR / không bypass CAPTCHA', $view);
    }
}
