<?php

namespace Tests\Feature\Muasamcong;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Modules\Muasamcong\Models\KqlcntAwardItem;
use Modules\Muasamcong\Services\KqlcntAwardExcelImportService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class KqlcntAwardPackageMetadataImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_sheet_one_contract_period_is_previewed_and_updates_all_scoped_medicines(): void
    {
        KqlcntAwardItem::query()->create([
            'notify_no' => 'IB-TEST-001', 'contractor_code' => 'vn-test-001',
            'contractor_name' => 'Nhà thầu thử', 'medicine_code' => 'MED-TEST-001',
            'medicine_name' => 'Thuốc thử', 'contract_period' => 24,
            'contract_period_unit' => 'tháng', 'synced_from_catalog_at' => now(),
        ]);
        $spreadsheet = new Spreadsheet();
        $medicines = $spreadsheet->getActiveSheet();
        $medicines->setTitle('Danh_muc_trung_thau');
        $medicines->fromArray([
            ['Mã lô', 'Tên lô', 'Mã thuốc', 'Tên thuốc', 'Nhóm thuốc', 'Hoạt chất', 'Hàm lượng',
             'Đường dùng', 'Dạng bào chế', 'Quy cách', 'Hạn dùng (tháng)', 'GĐKLH hoặc GPNK',
             'ĐVT', 'Số lượng', 'Giá kế hoạch', 'Giá trúng thầu', 'Thành tiền', 'Nhà sản xuất', 'Nước SX'],
            [null, null, 'MED-TEST-001', 'Thuốc thử'],
        ]);
        $package = $spreadsheet->createSheet();
        $package->setTitle('Thong_tin_trung_thau');
        $package->fromArray([
            ['Trường', 'Giá trị', 'Mã nhà thầu', 'Tên nhà thầu', 'Số hợp đồng', 'Thời gian thực hiện', 'Đơn vị thời gian'],
            ['Mã TBMT', 'IB-TEST-001'],
            ['DANH SÁCH NHÀ THẦU'],
            [null, null, 'vn-test-001', 'Nhà thầu thử', null, 12, 'tháng'],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'kqlcnt-package-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        try {
            $preview = app(KqlcntAwardExcelImportService::class)->preview(
                new UploadedFile($path, 'test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true)
            );
            $this->assertSame('12', $preview['package']['changes']['contract_period']);
            $this->assertSame('24', $preview['package']['comparison'][0]['current']);
            $this->assertSame('12', $preview['package']['comparison'][0]['incoming']);
            $this->assertEmpty($preview['package']['errors']);
        } finally {
            @unlink($path);
        }
    }

    public function test_package_metadata_preview_contract_has_guarded_transactional_update(): void
    {
        $source = file_get_contents(base_path('Modules/Muasamcong/Services/KqlcntAwardExcelImportService.php'));
        $this->assertStringContainsString('previewPackageChanges($scope)', $source);
        $this->assertStringContainsString('lockForUpdate()->get()', $source);
        $this->assertStringContainsString("'package_updated' => $packageUpdated", $source);
        $this->assertStringContainsString("! empty(\$batch['package']['errors'])", $source);
    }
}
