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
        $this->assertStringContainsString("official-facilities.bhxh.cached", file_get_contents(base_path('Modules/Pharma/routes/web.php')));
        $this->assertStringContainsString('loadCachedFacilities', $view);
        $this->assertStringContainsString('Tra cứu lại BHXH', $view);
        $this->assertStringContainsString('Dữ liệu đã lưu', $view);
        $this->assertStringNotContainsString('name="captcha" required', $view);
    }
    public function test_source_repository_exposes_region_filter_search_selection_export_and_white_pagination(): void
    {
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/OfficialSourceSyncController.php'));
        $routes = file_get_contents(base_path('Modules/Pharma/routes/web.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/pages/official-facilities/source.blade.php'));

        $this->assertStringContainsString("string('business_region')", $controller);
        $this->assertStringContainsString("whereIn('province_name', \$regionProvinceNames)", $controller);
        $this->assertStringContainsString('public function export(', $controller);
        $this->assertStringContainsString('Excel::download(', $controller);
        $this->assertStringContainsString(".'.xlsx'", $controller);
        $this->assertStringContainsString("'selected_ids' => ['nullable', 'array', 'max:500']", $controller);
        $this->assertStringContainsString('$this->applyFilters($query, $request, $provinceCatalog)', $controller);
        $this->assertStringContainsString('public function import(', $controller);
        $this->assertStringContainsString('OfficialSourceFacilitiesImport', $controller);
        $this->assertStringContainsString("official-facilities/source/export", $routes);
        $this->assertStringContainsString('<x-search', $view);
        $this->assertStringContainsString('name="business_region"', $view);
        $this->assertStringContainsString('data-select-page', $view);
        $this->assertStringContainsString('name="selected_ids[]"', $view);
        $this->assertStringContainsString('Xuất Excel', $view);
        $this->assertStringContainsString('Import Excel', $view);
        $this->assertStringContainsString('name="business_region" value="{{ request(\'business_region\') }}"', $view);
        $this->assertStringContainsString("official-facilities/source/import", $routes);
        $this->assertStringContainsString('aria-label="Phân trang cơ sở KCB nguồn"', $view);
        $this->assertStringContainsString('$facilities->url($page)', $view);
        $this->assertStringContainsString('Tìm mã, tên cơ sở...', $view);
        $this->assertStringContainsString('border-slate-200 bg-white', $view);
        $this->assertStringContainsString('{{ $provinceRegions[$facility->province_name] ?? \'—\' }}', $view);
    }
}
