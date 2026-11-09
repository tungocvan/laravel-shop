<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

class PharmaBidAwardProvinceRefreshContractTest extends TestCase
{
    public function test_hospital_refresh_reads_same_province_field_as_existing_form(): void
    {
        $blade = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/bid-award-allocation.blade.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));

        $this->assertStringContainsString('name="province_names[]"', $blade);
        $this->assertStringContainsString("formmethod=\"GET\"", $blade);
        $this->assertStringContainsString("query('province_names', \$request->query('provinces', \$setup['provinces']))", $controller);
        $this->assertStringContainsString('facilitiesForProvinces($draftProvinces)', $controller);
        $this->assertStringContainsString("query('facility_ids',", $controller);
        $this->assertStringContainsString("old('facility_ids',", $controller);
        $this->assertStringContainsString("old('province_names',", $controller);
        $this->assertStringContainsString("@if(\$errors->any()) open @endif", $blade);
    }
}
