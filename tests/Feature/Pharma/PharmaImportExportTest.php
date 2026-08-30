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
        (require base_path('Modules/Pharma/database/migrations/2026_08_30_010000_add_source_identity_to_drug_bid_awards_table.php'))->up();
        (require base_path('Modules/Pharma/database/migrations/2026_08_30_020000_add_business_key_to_supplier_trackings_table.php'))->up();
    }

    public function test_medicine_excel_fixture_passes_dry_run(): void
    {
        $path = $this->createMedicineFixture();

        try {
            $report = app(MedicineImportExport::class)->import(
                $path,
                ['mode' => 'update_or_create', 'dry_run' => true]
            );
        } finally {
            @unlink($path);
        }

        $this->assertTrue($report['success']);
        $this->assertSame(42, $report['total_rows']);
        $this->assertSame(42, $report['success_rows']);
        $this->assertSame(0, $report['error_rows']);
    }

    public function test_medicine_import_rejects_rows_without_required_registration_number(): void
    {
        $row = $this->medicineWorkbookRow(1);
        $row['Giấy phép lưu hành sản phẩm'] = '';
        $path = $this->createWorkbook('pharma-medicine-invalid', [$row]);

        try {
            $report = app(MedicineImportExport::class)->import($path, ['mode' => 'update_or_create']);
        } finally {
            @unlink($path);
        }

        $this->assertFalse($report['success']);
        $this->assertSame(1, $report['total_rows']);
        $this->assertSame(0, $report['success_rows']);
        $this->assertGreaterThanOrEqual(1, $report['error_rows']);
    }

    public function test_drug_bid_award_import_links_matching_medicine(): void
    {
        $medicine = Medicine::query()->create($this->medicineData());
        $path = $this->createWorkbook('drug-bid-import', [
            $this->drugBidAwardWorkbookRow('IB260000001', 'Trosicam 15mg', 'Hộp 3 vỉ x 10 viên', 'Công ty Dược A'),
        ]);

        try {
            $report = app(DrugBidAwardImportExport::class)->import($path, ['mode' => 'update_or_create']);
        } finally {
            @unlink($path);
        }

        $this->assertTrue($report['success']);
        $this->assertDatabaseHas('pharma_drug_bid_awards', [
            'bidding_notice_code' => 'IB260000001',
            'medicine_name' => 'Trosicam 15mg',
            'medicine_id' => $medicine->id,
        ]);
    }

    public function test_drug_bid_award_import_keeps_unmatched_medicine_as_nullable_link(): void
    {
        $path = $this->createWorkbook('drug-bid-import-unmatched', [
            $this->drugBidAwardWorkbookRow('IB260000002', 'Không tồn tại', 'Hộp 1', 'Công ty Dược B'),
        ]);

        try {
            $report = app(DrugBidAwardImportExport::class)->import($path, ['mode' => 'update_or_create']);
        } finally {
            @unlink($path);
        }

        $this->assertTrue($report['success']);
        $this->assertSame(1, $report['total_rows']);
        $this->assertSame(1, $report['success_rows']);
        $this->assertSame(0, $report['error_rows']);
        $this->assertDatabaseHas('pharma_drug_bid_awards', [
            'bidding_notice_code' => 'IB260000002',
            'medicine_name' => 'Không tồn tại',
            'medicine_id' => null,
        ]);
    }

    public function test_drug_bid_award_import_keeps_matched_and_unmatched_rows(): void
    {
        $medicine = Medicine::query()->create($this->medicineData());
        $path = $this->createWorkbook('drug-bid-import-mixed', [
            $this->drugBidAwardWorkbookRow('IB260000003', 'Trosicam 15mg', 'Hộp 3 vỉ x 10 viên', 'Công ty Dược C'),
            $this->drugBidAwardWorkbookRow('IB260000004', 'Không tồn tại', 'Hộp 1', 'Công ty Dược D'),
        ]);

        try {
            $report = app(DrugBidAwardImportExport::class)->import($path, ['mode' => 'update_or_create']);
        } finally {
            @unlink($path);
        }

        $this->assertTrue($report['success']);
        $this->assertSame(2, $report['total_rows']);
        $this->assertSame(2, $report['success_rows']);
        $this->assertSame(0, $report['error_rows']);
        $this->assertDatabaseHas('pharma_drug_bid_awards', [
            'bidding_notice_code' => 'IB260000003',
            'medicine_id' => $medicine->id,
        ]);
        $this->assertDatabaseHas('pharma_drug_bid_awards', [
            'bidding_notice_code' => 'IB260000004',
            'medicine_id' => null,
        ]);
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

    private function createMedicineFixture(): string
    {
        return $this->createWorkbook(
            'pharma-medicine-fixture',
            collect(range(1, 42))->map(fn (int $index): array => $this->medicineWorkbookRow($index))->all()
        );
    }

    private function medicineWorkbookRow(int $index): array
    {
        return [
            'STT' => $index,
            'Số thứ tự theo thông tư' => (string) $index,
            'Phân nhóm theo thông tư' => '1',
            'Tên hoạt chất' => 'Meloxicam',
            'Nồng độ - Hàm lượng' => '15mg',
            'Tên thuốc' => "Trosicam {$index} 15mg",
            'Dạng bào chế' => 'Viên nén',
            'Đường dùng' => 'Uống',
            'Đơn vị tính' => 'Viên',
            'Quy cách đóng gói' => 'Hộp 3 vỉ x 10 viên',
            'Giấy phép lưu hành sản phẩm' => 'VN-'.str_pad((string) $index, 5, '0', STR_PAD_LEFT).'-26',
            'Hạn dùng' => '36 tháng',
            'Cơ sở đăng ký' => 'Demo Registered Company',
            'Cơ sở sản xuất' => 'Demo Manufacturer',
            'Nước sản xuất' => 'Việt Nam',
            'Ngày hết hạn visa' => null,
            'Ngày chứng nhận GMP' => null,
            'Giá kê khai' => 5000,
            'Link hồ sơ' => null,
            'Kiểm soát đặc biệt' => 0,
            'Ghi chú' => null,
        ];
    }

    private function drugBidAwardWorkbookRow(
        string $noticeCode,
        string $medicineName,
        string $packaging,
        string $company
    ): array {
        return [
            'STT' => 1,
            'Tên thuốc' => $medicineName,
            'Quy cách đóng gói' => $packaging,
            'Số lượng' => 1000,
            'Đơn giá trúng thầu' => 5000,
            'Mã thông báo mời thầu' => $noticeCode,
            'Tên Chủ đầu tư' => 'Bệnh viện A',
            'Số quyết định' => 'QD-001',
            'Ngày ban hành quyết định' => '01/05/2026',
            'Thời hạn hiệu lực' => 12,
            'Công ty trúng thầu' => $company,
            'Link quyết định trúng thầu' => null,
        ];
    }

    private function createWorkbook(string $prefix, array $rows): string
    {
        $path = sys_get_temp_dir().'/'.$prefix.'-'.uniqid('', true).'.xlsx';
        (new FastExcel(collect($rows)))->export($path);

        return $path;
    }

    private function medicineData(): array
    {
        return [
            'name' => 'Trosicam 15mg',
            'registration_number' => 'VN-20104-16',
            'active_ingredients' => 'Meloxicam',
            'concentration' => '15mg',
            'dosage_form' => 'Viên nén',
            'route_of_administration' => 'Uống',
            'unit' => 'Viên',
            'packaging_specification' => 'Hộp 3 vỉ x 10 viên',
            'shelf_life' => '36 tháng',
            'registered_company' => 'Demo Registered Company',
            'manufacturing_company' => 'Demo Manufacturer',
            'manufacturing_country' => 'Việt Nam',
        ];
    }
}
