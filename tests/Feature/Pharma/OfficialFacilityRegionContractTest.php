<?php

namespace Tests\Feature\Pharma;

use Modules\Pharma\Services\OfficialFacilityImport\BhxhProvinceCatalog;
use Tests\TestCase;

class OfficialFacilityRegionContractTest extends TestCase
{
    public function test_bhxh_catalog_exposes_erp_business_regions_without_replacing_source_partitions(): void
    {
        $catalog = app(BhxhProvinceCatalog::class);

        $this->assertSame('Miền Tây / Tây Nam Bộ', $catalog->regionFor('92TTT'));
        $this->assertSame('Miền Đông / Đông Nam Bộ', $catalog->regionFor('79TTT'));
        $this->assertSame('Miền Bắc', $catalog->regionFor('01TTT'));
        $this->assertSame('Nam Trung Bộ', $catalog->regionFor('48TTT'));

        $canTho = $catalog->provincesByRegion()['Miền Tây / Tây Nam Bộ'];
        $this->assertSame('Thành phố Cần Thơ', $canTho['92TTT']);
        $this->assertSame('92TTT', $catalog->partitionsFor('92TTT')[0]['source_code']);
    }

    public function test_bhxh_admin_view_explains_source_storage_boundary(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/pages/official-facilities/bhxh.blade.php'));

        $this->assertStringContainsString('Vùng miền', $view);
        $this->assertStringContainsString('Kho dữ liệu nguồn Pharma', $view);
        $this->assertStringContainsString('Dữ liệu chưa ghi trực tiếp vào Partner', $view);
        $this->assertStringContainsString('business_region', $view);
    }
}
