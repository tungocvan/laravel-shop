<?php

namespace Modules\Pharma\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Services\MedicineCatalogImportCommitter;
use Modules\Pharma\Services\MedicineCatalogImportStager;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MedicineCatalogMetadataCommitTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function staged_therapeutic_group_and_special_control_are_persisted_to_medicine_master(): void
    {
        $batch = app(MedicineCatalogImportStager::class)->stage([
            [
                '_source_row' => 2,
                'Tên biệt dược' => 'Kháng sinh mẫu',
                'Tên hoạt chất' => 'Amoxicillin',
                'Nồng độ - Hàm lượng' => '500mg',
                'Dạng bào chế' => 'Viên nang',
                'Đơn vị tính' => 'Viên',
                'Quy cách đóng gói' => 'Hộp 10 vỉ x 10 viên',
                'Giấy phép lưu hành sản phẩm' => 'VD-TEST-001',
                'Nhóm thuốc điều trị' => 'Kháng sinh',
                'Thuốc KSĐB' => 'X',
                'Giá KK/ KKL' => 1250.5,
            ],
        ], 'template.xlsx');

        app(MedicineCatalogImportCommitter::class)->commit($batch);

        $medicine = Medicine::query()->where('registration_number', 'VD-TEST-001')->firstOrFail();

        $this->assertSame('Kháng sinh', $medicine->therapeutic_group);
        $this->assertTrue($medicine->is_special_control);
        $this->assertSame('1250.50', $medicine->declared_price);
    }
    #[Test]
    public function existing_medicine_receives_all_owner_excel_business_columns_on_update(): void
    {
        $initial = app(MedicineCatalogImportStager::class)->stage([[
            '_source_row' => 2,
            'STT TT20/2022' => '548',
            'Nhóm thuốc' => '1',
            'Nhóm thuốc điều trị' => 'Tim mạch',
            'Thuốc KSĐB' => '',
            'Tên hoạt chất' => 'Telmisartan + Hydrochlorothiazid',
            'Nồng độ - Hàm lượng' => '40mg + 12.5mg',
            'Tên biệt dược' => 'Anvo Test',
            'Dạng bào chế' => 'Viên',
            'Đường dùng' => 'uống',
            'Đơn vị tính' => 'Viên',
            'Quy cách đóng gói' => 'Hộp 1 vỉ x 7 viên',
            'Giấy phép lưu hành sản phẩm' => '840110178923',
            'Hạn dùng' => '24 tháng',
            'Cơ sở sản xuất' => 'Laboratorios Liconsa, S.A',
            'Nước sản xuất' => 'Spain',
            'Giá KK/ KKL' => 15000,
        ]], 'initial.xlsx');

        app(MedicineCatalogImportCommitter::class)->commit($initial);

        $updated = app(MedicineCatalogImportStager::class)->stage([[
            '_source_row' => 2,
            'STT TT20/2022' => '549',
            'Nhóm thuốc' => '2',
            'Nhóm thuốc điều trị' => 'Tim mạch – Chống tăng huyết áp',
            'Thuốc KSĐB' => 'X',
            'Tên hoạt chất' => 'Telmisartan + Hydrochlorothiazid',
            'Nồng độ - Hàm lượng' => '80mg + 12.5mg',
            'Tên biệt dược' => 'Anvo Test',
            'Dạng bào chế' => 'Viên nén',
            'Đường dùng' => 'uống',
            'Đơn vị tính' => 'viên',
            'Quy cách đóng gói' => 'Hộp 2 vỉ x 7 viên',
            'Giấy phép lưu hành sản phẩm' => '840110178923',
            'Hạn dùng' => '36 tháng',
            'Cơ sở sản xuất' => 'Laboratorios Liconsa, S.A',
            'Nước sản xuất' => 'Spain',
            'Giá KK/ KKL' => 15500,
        ]], 'updated.xlsx');

        app(MedicineCatalogImportCommitter::class)->commit($updated);

        $medicine = Medicine::query()->where('registration_number', '840110178923')->firstOrFail();

        $this->assertSame('549', $medicine->circular_order_number);
        $this->assertSame('2', $medicine->circular_group);
        $this->assertSame('Tim mạch – Chống tăng huyết áp', $medicine->therapeutic_group);
        $this->assertTrue($medicine->is_special_control);
        $this->assertSame('Telmisartan + Hydrochlorothiazid', $medicine->active_ingredients);
        $this->assertSame('80mg + 12.5mg', $medicine->concentration);
        $this->assertSame('Anvo Test', $medicine->name);
        $this->assertSame('Viên nén', $medicine->dosage_form);
        $this->assertSame('uống', $medicine->route_of_administration);
        $this->assertSame('viên', $medicine->unit);
        $this->assertSame('Hộp 2 vỉ x 7 viên', $medicine->packaging_specification);
        $this->assertSame('840110178923', $medicine->registration_number);
        $this->assertSame('840110178923', $medicine->registration_number_primary);
        $this->assertSame('840110178923', $medicine->registration_number_raw);
        $this->assertSame('36 tháng', $medicine->shelf_life);
        $this->assertSame('Laboratorios Liconsa, S.A', $medicine->manufacturing_company);
        $this->assertSame('Spain', $medicine->manufacturing_country);
        $this->assertSame('15500.00', $medicine->declared_price);
    }

}
