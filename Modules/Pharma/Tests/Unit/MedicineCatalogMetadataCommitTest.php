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
}
