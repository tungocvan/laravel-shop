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
        $path = sys_get_temp_dir().'/pharma-medicine-invalid-'.uniqid('', true).'.xlsx';
        (new FastExcel(collect([[
            'STT' => 1,
            'Số thứ tự theo thông tư' => '1',
            'Phân nhóm theo thông tư' => '1',
            'Tên hoạt chất' => 'Meloxicam',
            'Nồng độ - Hàm lượng' => '15mg',
            'Tên thuốc' => 'Trosicam 15mg',
            'Dạng bào chế' => 'Viên nén',
            'Đường dùng' => 'Uống',
            'Đơn vị tính' => 'Viên',
            'Quy cách đóng gói' => 'Hộp 3 vỉ x 10 viên',
            'Số đăng ký' => '',
            'Cơ sở sản xuất' => 'Demo Manufacturer',
            'Nước sản xuất' => 'Việt Nam',
        ]])))->export($path);

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

    public function test_drug_bid_award_import_matches_medicine_by_registration_number(): void
    {
        Medicine::query()->create($this->medicineData());

        $path = sys_get_temp_dir().'/drug-bid-import-'.uniqid('', true).'.xlsx';
        (new FastExcel(collect([[
            'Mã TBMT' => 'IB260000001',
            'Tên thuốc' => 'Trosicam 15mg',
            'Số đăng ký' => 'VN-20104-16',
            'Quy cách đóng gói' => 'Hộp 3 vỉ x 10 viên',
            'Số lượng' => 1000,
            'Đơn giá' => 5000,
            'Chủ đầu tư' => 'Bệnh viện A',
            'Số quyết định' => 'QD-001',
            'Ngày quyết định' => '01/05/2026',
            'Thời gian thực hiện hợp đồng' => 12,
            'Nhà thầu trúng thầu' => 'Công ty Dược A',
            'URL quyết định' => null,
        ]])))->export($path);

        try {
            $report = app(DrugBidAwardImportExport::class)->import($path, ['mode' => 'update_or_create']);
        } finally {
            @unlink($path);
        }

        $this->assertTrue($report['success']);
        $this->assertDatabaseHas('pharma_drug_bid_awards', [
            'bidding_notice_code' => 'IB260000001',
            'medicine_name' => 'Trosicam 15mg',
        ]);
    }

    public function test_drug_bid_award_import_reports_missing_medicine_without_crashing(): void
    {
        $path = sys_get_temp_dir().'/drug-bid-import-missing-'.uniqid('', true).'.xlsx';
        (new FastExcel(collect([[
            'Mã TBMT' => 'IB260000002',
            'Tên thuốc' => 'Không tồn tại',
            'Số đăng ký' => 'NOT-FOUND',
            'Quy cách đóng gói' => 'Hộp 1',
            'Số lượng' => 100,
            'Đơn giá' => 1000,
            'Chủ đầu tư' => 'Bệnh viện B',
            'Số quyết định' => 'QD-002',
            'Ngày quyết định' => '02/05/2026',
            'Thời gian thực hiện hợp đồng' => 6,
            'Nhà thầu trúng thầu' => 'Công ty Dược B',
            'URL quyết định' => null,
        ]])))->export($path);

        try {
            $report = app(DrugBidAwardImportExport::class)->import($path, ['mode' => 'update_or_create']);
        } finally {
            @unlink($path);
        }

        $this->assertFalse($report['success']);
        $this->assertSame(1, $report['total_rows']);
        $this->assertSame(0, $report['success_rows']);
        $this->assertGreaterThanOrEqual(1, $report['error_rows']);
    }

    public function test_drug_bid_award_import_keeps_valid_rows_when_another_row_is_invalid(): void
    {
        Medicine::query()->create($this->medicineData());

        $path = sys_get_temp_dir().'/drug-bid-import-partial-'.uniqid('', true).'.xlsx';
        (new FastExcel(collect([
            [
                'Mã TBMT' => 'IB260000003',
                'Tên thuốc' => 'Trosicam 15mg',
                'Số đăng ký' => 'VN-20104-16',
                'Quy cách đóng gói' => 'Hộp 3 vỉ x 10 viên',
                'Số lượng' => 1000,
                'Đơn giá' => 5000,
                'Chủ đầu tư' => 'Bệnh viện C',
                'Số quyết định' => 'QD-003',
                'Ngày quyết định' => '03/05/2026',
                'Thời gian thực hiện hợp đồng' => 12,
                'Nhà thầu trúng thầu' => 'Công ty Dược C',
                'URL quyết định' => null,
            ],
            [
                'Mã TBMT' => 'IB260000004',
                'Tên thuốc' => 'Không tồn tại',
                'Số đăng ký' => 'NOT-FOUND-2',
                'Quy cách đóng gói' => 'Hộp 1',
                'Số lượng' => 100,
                'Đơn giá' => 1000,
                'Chủ đầu tư' => 'Bệnh viện D',
                'Số quyết định' => 'QD-004',
                'Ngày quyết định' => '04/05/2026',
                'Thời gian thực hiện hợp đồng' => 6,
                'Nhà thầu trúng thầu' => 'Công ty Dược D',
                'URL quyết định' => null,
            ],
        ])))->export($path);

        try {
            $report = app(DrugBidAwardImportExport::class)->import($path, ['mode' => 'update_or_create']);
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

    private function createMedicineFixture(): string
    {
        $path = sys_get_temp_dir().'/pharma-medicine-fixture-'.uniqid('', true).'.xlsx';
        $rows = collect(range(1, 42))->map(fn (int $index): array => [
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
            'Số đăng ký' => 'VN-'.str_pad((string) $index, 5, '0', STR_PAD_LEFT).'-26',
            'Cơ sở sản xuất' => 'Demo Manufacturer',
            'Nước sản xuất' => 'Việt Nam',
        ]);

        (new FastExcel($rows))->export($path);

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
