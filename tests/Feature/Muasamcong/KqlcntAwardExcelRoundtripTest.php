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
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Thiếu cột bắt buộc');
        app(KqlcntAwardExcelImportService::class)->preview($file);
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
