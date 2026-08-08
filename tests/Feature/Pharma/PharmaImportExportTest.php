<?php

namespace Tests\Feature\Pharma;

use Illuminate\Support\Facades\Schema;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Models\SupplierTracking;
use Modules\Pharma\Services\DrugBidAwardImportExport;
use Modules\Pharma\Services\ImportExport as SupplierTrackingImportExport;
use Modules\Pharma\Services\MedicineImportExport;
use Rap2hpoutre\FastExcel\FastExcel;
use Tests\TestCase;

class PharmaImportExportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('PDO SQLite is required for Pharma import/export database tests.');
        }

        Schema::dropIfExists('pharma_drug_bid_awards');
        Schema::dropIfExists('pharma_supplier_trackings');
        Schema::dropIfExists('pharma_medicines');

        (require base_path('Modules/Pharma/database/migrations/2026_05_21_145242_create_medicines_table.php'))->up();
        (require base_path('Modules/Pharma/database/migrations/2026_05_22_135028_create_drug_bid_awards_table.php'))->up();
        (require base_path('Modules/Pharma/database/migrations/2026_05_23_141810_create_supplier_trackings_table.php'))->up();
    }

    public function test_medicine_excel_fixture_passes_dry_run(): void
    {
        $report = app(MedicineImportExport::class)->import(
            storage_path('app/import/hssp.xlsx'),
            ['mode' => 'update_or_create', 'dry_run' => true]
        );

        $this->assertTrue($report['success']);
        $this->assertSame(42, $report['total_rows']);
        $this->assertSame(42, $report['success_rows']);
        $this->assertSame(0, $report['error_rows']);
    }

    public function test_bid_award_semicolon_fixture_passes_dry_run(): void
    {
        $report = app(DrugBidAwardImportExport::class)->import(
            storage_path('app/import/thong-tin-trung-thau.csv'),
            ['mode' => 'update_or_create', 'dry_run' => true]
        );

        $this->assertTrue($report['success']);
        $this->assertSame(32, $report['total_rows']);
        $this->assertSame(32, $report['success_rows']);
        $this->assertSame(0, $report['error_rows']);
    }

    public function test_medicine_update_keeps_existing_values_for_empty_cells(): void
    {
        Medicine::query()->create($this->medicineData());

        $path = sys_get_temp_dir().'/medicine-import-'.uniqid('', true).'.xlsx';
        (new FastExcel(collect([[
            'STT' => 1,
            'Số thứ tự theo thông tư' => null,
            'Phân nhóm theo thông tư' => null,
            'Tên hoạt chất' => null,
            'Nồng độ - Hàm lượng' => null,
            'Tên thuốc' => null,
            'Dạng bào chế' => null,
            'Đường dùng' => null,
            'Đơn vị tính' => null,
            'Quy cách đóng gói' => 'Hộp 3 vỉ x 10 viên',
            'Giấy phép lưu hành sản phẩm' => 'VN-20104-16',
            'Hạn dùng' => null,
            'Cơ sở đăng ký' => null,
            'Cơ sở sản xuất' => null,
            'Nước sản xuất' => null,
            'Hiệu lực Visa' => null,
            'GMP Cơ sở sản xuất' => null,
            'Giá kê khai' => 9000,
            'Link Hồ sơ sản phẩm' => null,
            'Hoạt chất kiểm soát đặc biệt' => null,
            'Ghi chú' => null,
        ]])))->export($path);

        try {
            $report = app(MedicineImportExport::class)->import($path, ['mode' => 'update_or_create']);
        } finally {
            @unlink($path);
        }

        $medicine = Medicine::query()->firstOrFail();
        $this->assertTrue($report['success']);
        $this->assertSame('Trosicam 15mg', $medicine->name);
        $this->assertSame('9000.00', $medicine->declared_price);
    }

    public function test_import_continues_after_an_invalid_bid_award_row(): void
    {
        $path = sys_get_temp_dir().'/bid-import-'.uniqid('', true).'.csv';
        file_put_contents($path, implode("\n", [
            'STT;Tên thuốc;Quy cách đóng gói;Số lượng;Đơn giá trúng thầu;Mã thông báo mời thầu;Tên Chủ đầu tư;Số quyết định;Ngày ban hành quyết định;Thời hạn hiệu lực;Công ty trúng thầu;Link quyết định trúng thầu',
            '1;Trosicam 15mg;Hộp 3 vỉ x 10 viên;600.000;7.791;IB01;Bệnh viện A;QĐ-01;13/10/2025;24 tháng;Công ty A;',
            '2;;Hộp 1;100;2000;IB02;Bệnh viện B;QĐ-02;13/10/2025;12 tháng;Công ty B;',
        ]));

        try {
            $report = app(DrugBidAwardImportExport::class)->import(
                $path,
                ['mode' => 'update_or_create', 'dry_run' => true]
            );
        } finally {
            @unlink($path);
        }

        $this->assertFalse($report['success']);
        $this->assertSame(2, $report['total_rows']);
        $this->assertSame(1, $report['success_rows']);
        $this->assertGreaterThanOrEqual(1, $report['error_rows']);
    }

    public function test_supplier_tracking_import_uses_a_to_v_and_recalculates_derived_fields(): void
    {
        Medicine::query()->create($this->medicineData());

        $path = sys_get_temp_dir().'/supplier-import-'.uniqid('', true).'.xlsx';
        (new FastExcel(collect([[
            'Ngày làm việc' => '01/05/2026',
            'Tên thuốc' => 'Trosicam 15mg',
            'Số đăng ký' => 'VN-20104-16',
            'Nhà cung cấp' => 'Công ty ABC',
            'Người đại diện' => 'Nguyễn Văn A',
            'Khu vực' => 'Miền Nam',
            'Giá nhập' => 3750,
            'Giá bán' => 7791,
            'Giá hóa đơn' => 7000,
            'Chênh lệch hóa đơn' => 999999,
            '% phí chênh lệch' => 10,
            'Phí chênh lệch' => 999999,
            'Giá vốn' => 999999,
            '% lợi nhuận thực tế' => 999999,
            'Số lượng cam kết' => 500000,
            'Đơn vị' => 'Viên',
            'Tiền cọc' => 50000000,
            'Ngày bắt đầu' => '01/06/2026',
            'Ngày kết thúc' => '01/06/2027',
            'URL hợp đồng' => null,
            'Trạng thái' => 'active',
            'Ghi chú' => null,
        ]])))->export($path);

        try {
            $report = app(SupplierTrackingImportExport::class)->import($path, ['mode' => 'update_or_create']);
        } finally {
            @unlink($path);
        }

        $tracking = SupplierTracking::query()->firstOrFail();
        $this->assertTrue($report['success']);
        $this->assertSame('3250.00', $tracking->invoice_difference_amount);
        $this->assertSame('325.00', $tracking->invoice_difference_fee);
        $this->assertSame('4075.00', $tracking->cost_price);
        $this->assertSame('47.70', $tracking->gross_profit_percent);
    }

    private function medicineData(): array
    {
        return [
            'active_ingredients' => 'Meloxicam',
            'concentration' => '15mg',
            'name' => 'Trosicam 15mg',
            'dosage_form' => 'Viên hòa tan nhanh',
            'route_of_administration' => 'Uống',
            'unit' => 'Viên',
            'packaging_specification' => 'Hộp 3 vỉ x 10 viên',
            'registration_number' => 'VN-20104-16',
            'shelf_life' => '36 tháng',
            'registered_company' => 'Alpex Pharma SA',
            'manufacturing_company' => 'Alpex Pharma SA',
            'manufacturing_country' => 'Thụy Sĩ',
            'declared_price' => 8500,
            'is_special_control' => false,
        ];
    }
}
