<?php

namespace Modules\Pharma\Tests\Unit;

use Modules\Pharma\Services\OfficialFacilityImport\BhxhProvinceCatalog;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BhxhProvinceCatalogTest extends TestCase
{
    #[Test]
    public function catalog_maps_public_bhxh_codes_to_display_names(): void
    {
        $catalog = app(BhxhProvinceCatalog::class)->all();
        $this->assertSame('Thành phố Cần Thơ', $catalog['92TTT']);
        $this->assertSame('Thành phố Hồ Chí Minh', $catalog['79TTT']);
        $this->assertSame('Thành phố Hà Nội', $catalog['01TTT']);
    }

    #[Test]
    public function duplicate_display_names_are_one_erp_facing_province_with_multiple_source_partitions(): void
    {
        $service = app(BhxhProvinceCatalog::class);
        $catalog = $service->all();
        $partitions = $service->partitionsFor('89TTT');

        $this->assertSame(1, count(array_filter($catalog, fn ($name) => $name === 'Tỉnh An Giang')));
        $this->assertSame(['89TTT', '91TTT'], $service->sourceCodesFor('89TTT'));
        $this->assertSame('Khu vực An Giang cũ', $partitions[0]['partition_name']);
        $this->assertSame('Khu vực Kiên Giang cũ', $partitions[1]['partition_name']);
        $this->assertTrue($service->isSourceCodeFor('89TTT', '91TTT'));
        $this->assertFalse($service->isSourceCodeFor('89TTT', '92TTT'));
        $this->assertContains('89TTT', $service->codes());
        $this->assertNotContains('91TTT', $service->codes());
    }

    #[Test]
    public function single_source_province_has_one_partition(): void
    {
        $service = app(BhxhProvinceCatalog::class);
        $this->assertSame(['92TTT'], $service->sourceCodesFor('92TTT'));
        $this->assertSame('Thành phố Cần Thơ', $service->partitionsFor('92TTT')[0]['partition_name']);
        $this->assertSame([], $service->sourceCodesFor('UNKNOWN'));
    }

    #[Test]
    public function bhxh_lookup_view_separates_province_source_partition_and_source_district(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/pages/official-facilities/bhxh.blade.php'));
        $this->assertStringContainsString('<select id="ma_tinh" name="ma_tinh"', $view);
        $this->assertStringContainsString('<select id="source_partition" name="source_partition"', $view);
        $this->assertStringContainsString('Vùng dữ liệu BHXH', $view);
        $this->assertStringContainsString('<select id="ma_quan_huyen" name="ma_quan_huyen"', $view);
        $this->assertStringContainsString('-- Toàn vùng --', $view);
        $this->assertStringContainsString('Địa bàn BHXH', $view);
        $this->assertStringNotContainsString('-- Toàn tỉnh --', $view);
    }
}
