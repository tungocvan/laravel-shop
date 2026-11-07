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

class KqlcntAwardExcelRoundtripTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_rejects_workbook_without_identity_columns(): void
    {
        $file = $this->workbook([['Mã TBMT'], ['TBMT-1']]);
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(KqlcntAwardExcelImportService::class)->preview($file);
    }

    public function test_legacy_36_column_export_can_be_previewed_without_500(): void
    {
        $headers = ['Mã TBMT', 'Mã nhà thầu', 'Tên nhà thầu', 'Mã lô', 'Tên lô', 'Mã thuốc', 'Tên thuốc',
            'Nhóm thuốc', 'Hoạt chất', 'Hàm lượng', 'Đường dùng', 'Dạng bào chế', 'Quy cách',
            'Hạn dùng (tháng)', 'GĐKLH hoặc GPNK', 'ĐVT', 'Số lượng', 'Giá kế hoạch',
            'Giá trúng thầu', 'Thành tiền', 'Nhà sản xuất', 'Nước SX', 'Số quyết định',
            'Ngày quyết định', 'Ngày đăng KQLCNT', 'Mã chủ đầu tư', 'Chủ đầu tư',
            'Số hợp đồng', 'Thời gian thực hiện', 'Đơn vị thời gian',
            'Thời gian thực hiện gói thầu', 'Thời hạn hiệu lực',
            'Nguồn dữ liệu', 'Nguồn đồng bộ', 'Trạng thái', 'Đồng bộ lúc'];
        $row = array_fill(0, count($headers), null);
        $row[0] = 'TBMT-LEGACY';
        $row[1] = 'CONTRACTOR-LEGACY';
        $row[6] = 'Thuốc kiểm thử';
        $preview = app(KqlcntAwardExcelImportService::class)->preview($this->workbook([$headers, $row]));
        $this->assertSame(0, $preview['valid']);
        $this->assertSame(1, $preview['errors']);
        $this->assertSame(1, $preview['total']);
        $this->assertSame('error', $preview['rows'][0]['status']);
        $this->assertCount(32, $preview['rows'][0]['comparison']);
        $this->assertStringContainsString('Không tìm thấy', implode(' ', $preview['rows'][0]['errors']));
    }

    public function test_import_preview_defaults_to_actionable_rows_and_retains_optional_unchanged_inspection(): void
    {
        $blade = file_get_contents(base_path('Modules/Muasamcong/resources/views/kqlcnt-awards/import-preview.blade.php'));
        $this->assertIsString($blade);
        $this->assertStringContainsString("filter: 'actionable'", $blade);
        $this->assertStringContainsString("filter === 'actionable'", $blade);
        $this->assertStringContainsString('value="unchanged"', $blade);
        $this->assertStringContainsString('value="all"', $blade);
        $this->assertStringContainsString('Dữ liệu đã được đồng bộ', $blade);
        $this->assertStringContainsString("showAllFields ||", $blade);
        $this->assertStringContainsString("@disabled(\$preview['valid'] === 0)", $blade);
    }

    public function test_confirm_rejects_expired_preview_token(): void
    {
        Cache::forget('muasamcong:kqlcnt:excel:'.hash('sha256', 'expired'));
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(KqlcntAwardExcelImportService::class)->confirm('expired');
    }

    private function workbook(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Danh_muc_trung_thau');
        $sheet->fromArray($rows);
        $path = tempnam(sys_get_temp_dir(), 'kqlcnt-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        return new UploadedFile($path, 'kqlcnt.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
