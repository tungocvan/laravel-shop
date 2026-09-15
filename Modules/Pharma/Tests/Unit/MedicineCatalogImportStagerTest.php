<?php

namespace Modules\Pharma\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Pharma\Models\MedicineImportRow;
use Modules\Pharma\Services\MedicineCatalogImportStager;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MedicineCatalogImportStagerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function pitamsol_presentations_are_separate_variants_and_real_ozdectin_rows_are_duplicates(): void
    {
        $stager = app(MedicineCatalogImportStager::class);

        $batch = $stager->stage([
            [
                '_source_row' => 2,
                'Tên biệt dược' => 'Pitamsol',
                'Nồng độ - Hàm lượng' => '2.400mg; 7,2ml',
                'Dạng bào chế' => 'Dung dịch uống',
                'Đơn vị tính' => 'Gói',
                'Quy cách đóng gói' => 'Hộp 30 gói',
                'Giấy phép lưu hành sản phẩm' => '893110138900',
            ],
            [
                '_source_row' => 3,
                'Tên biệt dược' => 'Pitamsol',
                'Nồng độ - Hàm lượng' => '2.400mg; 24ml',
                'Dạng bào chế' => 'Dung dịch uống',
                'Đơn vị tính' => 'Gói',
                'Quy cách đóng gói' => 'Hộp 30 gói',
                'Giấy phép lưu hành sản phẩm' => '893110138900',
            ],
            [
                '_source_row' => 41,
                'STT TT20/2022' => 739,
                'Nhóm thuốc' => 4,
                'Tên hoạt chất' => 'Simethicon',
                'Tên biệt dược' => 'Ozdectin',
                'Nồng độ - Hàm lượng' => '275,5mg',
                'Dạng bào chế' => 'Viên nang mềm',
                'Đường dùng' => 'uống',
                'Đơn vị tính' => 'viên',
                'Quy cách đóng gói' => 'Hộp 10 vỉ x 10 viên',
                'Giấy phép lưu hành sản phẩm' => '893100952824',
                'Hạn dùng' => '36 tháng',
                'Cơ sở sản xuất' => 'Công ty TNHH Dược phẩm Hoa Linh Hà Nam',
                'Nước sản xuất' => 'Việt Nam',
                'Giá KK/ KKL' => 3200,
            ],
            [
                '_source_row' => 44,
                'STT TT20/2022' => 739,
                'Nhóm thuốc' => 4,
                'Tên hoạt chất' => 'Simethicon',
                'Tên biệt dược' => 'Ozdectin',
                'Nồng độ - Hàm lượng' => '275,5mg',
                'Dạng bào chế' => 'Viên nang mềm',
                'Đường dùng' => 'uống',
                'Đơn vị tính' => 'viên',
                'Quy cách đóng gói' => 'Hộp 10 vỉ x 10 viên',
                'Giấy phép lưu hành sản phẩm' => '893100952824',
                'Hạn dùng' => '36 tháng',
                'Cơ sở sản xuất' => "Công ty TNHH Dược phẩm Hoa Linh Hà Nam",
                'Nước sản xuất' => 'Việt Nam',
                'Giá KK/ KKL' => 3200,
            ],
        ], 'Danh Muc Thuoc.xlsx');

        $rows = $batch->rows()->orderBy('source_row')->get();

        $this->assertSame(4, $batch->total_rows);
        $this->assertSame(3, $batch->new_rows);
        $this->assertSame(1, $batch->duplicate_rows);
        $this->assertSame(0, $batch->conflict_rows);
        $this->assertNotSame($rows[0]->variant_identity_key, $rows[1]->variant_identity_key);
        $this->assertSame($rows[2]->variant_identity_key, $rows[3]->variant_identity_key);
        $this->assertSame($rows[2]->payload_hash, $rows[3]->payload_hash);
        $this->assertSame(MedicineImportRow::CLASS_DUPLICATE, $rows[3]->classification);
        $this->assertFalse($rows[3]->selected);
    }
}
