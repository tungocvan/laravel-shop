<?php

namespace Modules\Pharma\Tests\Unit;

use Modules\Pharma\Services\MedicineCatalogImportMapper;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MedicineCatalogImportMapperTest extends TestCase
{
    #[Test]
    public function it_maps_owner_excel_headers_and_synonymous_product_name_labels(): void
    {
        $mapper = app(MedicineCatalogImportMapper::class);

        $mapped = $mapper->map([
            'Tên hoạt chất' => 'Salbutamol',
            'Nồng độ - Hàm lượng' => '2mg/5ml; 150ml',
            'Tên sản phẩm' => 'Sallet',
            'Dạng bào chế' => 'Siro',
            'Đường dùng' => 'Uống',
            'Đơn vị tính' => 'Lọ',
            'Quy cách đóng gói' => 'Hộp 1 lọ 150ml',
            'Giấy phép lưu hành sản phẩm' => " VD-34495-20 \n",
            'Hạn dùng' => '36 tháng',
            'Cơ sở sản xuất' => 'Công ty A',
            'Nước sản xuất' => 'Việt Nam',
            'Giá KK/KL' => '23.809,524',
        ]);

        $this->assertSame('Sallet', $mapped['name']);
        $this->assertSame('VD-34495-20', $mapped['registration_number_primary']);
        $this->assertSame('2mg/5ml; 150ml', $mapped['concentration']);
        $this->assertSame('Hộp 1 lọ 150ml', $mapped['packaging_specification']);
        $this->assertFalse($mapped['is_special_control']);
        $this->assertNull($mapped['therapeutic_group']);
    }

    #[Test]
    public function canonical_name_accepts_ten_biet_duoc_ten_thuoc_and_ten_san_pham(): void
    {
        $mapper = app(MedicineCatalogImportMapper::class);

        foreach (['Tên biệt dược', 'Tên thuốc', 'Tên sản phẩm'] as $label) {
            $mapped = $mapper->map([$label => 'Pitamsol']);
            $this->assertSame('Pitamsol', $mapped['name']);
        }
    }

    #[Test]
    public function it_maps_therapeutic_group_and_special_control_marker(): void
    {
        $mapper = app(MedicineCatalogImportMapper::class);

        $mapped = $mapper->map([
            'Tên biệt dược' => 'Thuốc mẫu',
            'Nồng độ - Hàm lượng' => '500mg',
            'Nhóm thuốc điều trị' => 'Kháng sinh',
            'Thuốc KSĐB' => 'X',
        ]);

        $this->assertSame('Kháng sinh', $mapped['therapeutic_group']);
        $this->assertTrue($mapped['is_special_control']);

        $blank = $mapper->map([
            'Tên biệt dược' => 'Thuốc mẫu 2',
            'Nồng độ - Hàm lượng' => '500mg',
            'Thuốc KSĐB' => '',
        ]);

        $this->assertFalse($blank['is_special_control']);
    }

    #[Test]
    public function it_normalizes_real_excel_header_whitespace_and_line_breaks(): void
    {
        $mapper = app(MedicineCatalogImportMapper::class);

        $mapped = $mapper->map([
            ' Tên hoạt chất' => "Telmisartan +\nHydrochlorothiazid",
            "Nồng độ - \r\nHàm lượng" => '80mg + 12.5mg',
            'Tên biệt dược' => 'Anvo-Telmisartan HCTZ 80/12,5mg',
            'Quy cách đóng gói' => 'Hộp 2 vỉ x 7 viên',
            'Giấy phép lưu hành sản phẩm' => '840110178923',
            'Hạn dùng' => '36 tháng',
            'Cơ sở sản xuất' => 'Laboratorios Liconsa, S.A',
            'Nước sản xuất' => 'Spain',
            'Giá KK/ KKL' => '15.500',
        ]);

        $this->assertSame('Telmisartan + Hydrochlorothiazid', $mapped['active_ingredients']);
        $this->assertSame('80mg + 12.5mg', $mapped['concentration']);
        $this->assertSame('Anvo-Telmisartan HCTZ 80/12,5mg', $mapped['name']);
        $this->assertSame('840110178923', $mapped['registration_number']);
        $this->assertSame('Hộp 2 vỉ x 7 viên', $mapped['packaging_specification']);
        $this->assertNotNull($mapped['declared_price']);
    }

    #[Test]
    public function unicode_equivalent_vietnamese_values_produce_the_same_payload_hash(): void
    {
        if (! class_exists(\Normalizer::class)) {
            $this->markTestSkipped('PHP intl Normalizer is required for NFC regression coverage.');
        }

        $mapper = app(MedicineCatalogImportMapper::class);
        $common = [
            'Tên hoạt chất' => 'Simethicon',
            'Nồng độ - Hàm lượng' => '275,5mg',
            'Tên biệt dược' => 'Ozdectin',
            'Dạng bào chế' => 'Viên nang mềm',
            'Đường dùng' => 'uống',
            'Đơn vị tính' => 'viên',
            'Quy cách đóng gói' => 'Hộp 10 vỉ x 10 viên',
            'Giấy phép lưu hành sản phẩm' => '893100952824',
            'Hạn dùng' => '36 tháng',
            'Nước sản xuất' => 'Việt Nam',
            'Giá KK/ KKL' => 3200,
        ];

        $composed = $mapper->map($common + [
            'Cơ sở sản xuất' => 'Công ty TNHH Dược phẩm Hoa Linh Hà Nam',
        ]);
        $decomposed = $mapper->map($common + [
            'Cơ sở sản xuất' => "Công ty TNHH Dược phẩm Hoa Linh Hà Nam",
        ]);

        $this->assertSame($composed['manufacturing_company'], $decomposed['manufacturing_company']);
        $this->assertSame($mapper->payloadHash($composed), $mapper->payloadHash($decomposed));
    }
}
