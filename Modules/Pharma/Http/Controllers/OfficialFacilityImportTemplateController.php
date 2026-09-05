<?php

namespace Modules\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class OfficialFacilityImportTemplateController extends Controller
{
    public function __invoke(): Response
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Official Facilities');

        $headers = [
            'external_id',
            'facility_name',
            'tax_code',
            'address',
            'phone',
            'email',
        ];

        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray([
            ['TEST-KCB-001', 'Bệnh viện Test Cần Thơ', '1800000001', '123 Đường Test, Cần Thơ', '02923888888', 'test1@example.com'],
            ['TEST-KCB-002', 'Trung tâm Y tế Test Ninh Kiều', null, '456 Đường Test, Cần Thơ', '02923999999', 'test2@example.com'],
            ['TEST-KCB-003', 'Phòng khám Test Cần Thơ', null, '789 Đường Test, Cần Thơ', null, null],
        ], null, 'A2');

        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $sheet->freezePane('A2');

        $notes = $spreadsheet->createSheet();
        $notes->setTitle('README');
        $notes->fromArray([
            ['Trường', 'Yêu cầu / ghi chú'],
            ['external_id', 'Mã định danh của cơ sở theo nguồn chính thức. Khuyến nghị có để hỗ trợ idempotent re-import.'],
            ['facility_name', 'Bắt buộc. Tên cơ sở y tế.'],
            ['tax_code', 'Không bắt buộc. Nếu xung đột với Partner hiện có, hệ thống sẽ chặn và yêu cầu review.'],
            ['address', 'Không bắt buộc. Chỉ safe-fill vào Partner hiện có khi Partner chưa có địa chỉ.'],
            ['phone', 'Không bắt buộc. Không ghi đè số điện thoại Partner hiện có.'],
            ['email', 'Không bắt buộc. Phải đúng định dạng email; không ghi đè email Partner hiện có.'],
            ['Lưu ý', 'Mã tỉnh canonical, mã tỉnh của nguồn và ngày nguồn được nhập ở form upload trên Admin, không nằm trong từng dòng Excel.'],
            ['Dữ liệu mẫu', 'Các dòng TEST-* chỉ dùng để thử workflow, không phải cơ sở y tế chính thức.'],
        ], null, 'A1');
        $notes->getColumnDimension('A')->setAutoSize(true);
        $notes->getColumnDimension('B')->setWidth(90);

        $writer = new Xlsx($spreadsheet);
        $path = tempnam(sys_get_temp_dir(), 'pharma-official-facility-template-');
        $writer->save($path);
        $content = file_get_contents($path);
        @unlink($path);

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="official-facility-import-template.xlsx"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
