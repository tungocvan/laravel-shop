<?php

namespace Modules\Muasamcong\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Muasamcong\Models\PricingResult;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SyncedPricingBbgExportController extends Controller
{
    public function __invoke(Request $request): BinaryFileResponse
    {
        $validated = $request->validate([
            'selected_ids' => ['required', 'array', 'min:1', 'max:5000'],
            'selected_ids.*' => ['required', 'integer', 'min:1'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $validated['selected_ids'])));
        $items = PricingResult::query()->whereIn('id', $ids)->orderBy('id')->get();
        abort_if($items->isEmpty(), 422, 'Không tìm thấy dữ liệu đã chọn để xuất BBG.');

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('TỔNG HỢP');
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(11);

        foreach (range(1, 5) as $row) {
            $sheet->mergeCells("C{$row}:S{$row}");
        }
        $sheet->setCellValue('C1', 'CÔNG TY TNHH INAFO VIỆT NAM');
        $sheet->setCellValue('C2', 'Địa chỉ: 240/127/26 Nguyễn Văn Luông, Phường 11, Quận 6, TP. Hồ Chí Minh');
        $sheet->setCellValue('C3', 'VPĐD: 36 Nguyễn Minh Hoàng, Phường Bảy Hiền, TP. Hồ Chí Minh');
        $sheet->setCellValue('C4', 'Mã số thuế: 0314492345');
        $sheet->setCellValue('C5', 'Số điện thoại: 036 579 2786          Email: inafotender@gmail.com');
        $sheet->getStyle('C1')->getFont()->setBold(true)->setSize(12);

        $sheet->mergeCells('A6:S6');
        $sheet->setCellValue('A6', 'BẢNG CHÀO GIÁ');
        $sheet->getStyle('A6')->getFont()->setBold(true)->setSize(18);
        $sheet->getStyle('A6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A7:S7');
        $sheet->setCellValue('A7', 'Kính gửi: QUÝ KHÁCH HÀNG');
        $sheet->getStyle('A7')->getFont()->setBold(true);

        $sheet->mergeCells('A8:S8');
        $sheet->setCellValue('A8', 'Công ty INAFO Việt Nam xin trân trọng gửi đến Quý Khách hàng báo giá một số sản phẩm chúng tôi đang phân phối trên thị trường hiện nay như sau:');

        $headers = [
            'STT', 'Nhóm thuốc', 'Hoạt chất', 'Nồng độ / Hàm lượng', 'Tên thuốc', 'Dạng bào chế',
            'Đường dùng', 'Đơn vị tính', 'Quy cách đóng gói', 'GĐKLH / GPNK', 'Hạn dùng',
            'Cơ sở sản xuất', 'Nước sản xuất', 'Giá trúng thầu', 'Số lượng',
            'Chủ đầu tư / Bên mời thầu', 'Đơn vị trúng thầu', 'Số quyết định', 'Ngày quyết định',
        ];
        foreach ($headers as $index => $header) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1).'9', $header);
        }

        $yellow = 'FFC000';
        $green = '92D050';
        $sheet->getStyle('A9:M9')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($yellow);
        $sheet->getStyle('N9:S9')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($green);
        $sheet->getStyle('A9:S9')->getFont()->setBold(true);
        $sheet->getStyle('A9:S9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getRowDimension(9)->setRowHeight(64);

        $row = 10;
        foreach ($items as $index => $item) {
            $winnerNames = implode("\n", array_values(array_filter(array_map('strval', (array) $item->winning_name))));
            $values = [
                $index + 1,
                $this->medicineGroupNumber($item->nhom_thuoc),
                $item->ten_hoat_chat,
                $item->nong_do,
                $item->ten_thuoc,
                $item->dang_bao_che,
                $item->duong_dung,
                $item->don_vi_tinh,
                $item->quy_cach_dong_goi,
                $item->gdklh_gpnk,
                $item->han_dung,
                $item->ten_co_so_san_xuat,
                $item->nuoc_san_xuat,
                is_numeric($item->don_gia) ? (float) $item->don_gia : null,
                is_numeric($item->so_luong) ? (float) $item->so_luong : null,
                $item->ten_cdt_bmt,
                $winnerNames !== '' ? $winnerNames : null,
                $item->so_quyet_dinh,
                $item->ngay_ban_hanh_quyet_dinh?->format('d/m/Y'),
            ];

            foreach ($values as $column => $value) {
                $coordinate = Coordinate::stringFromColumnIndex($column + 1).$row;
                if ($column === 9) {
                    $sheet->setCellValueExplicit($coordinate, $value === null ? '' : (string) $value, DataType::TYPE_STRING);
                    $sheet->getStyle($coordinate)->getNumberFormat()->setFormatCode('@');
                } else {
                    $sheet->setCellValue($coordinate, $value);
                }
            }
            $sheet->getStyle("A{$row}:S{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
            $sheet->getStyle("A{$row}:B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$row}:H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("M{$row}:O{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("S{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$row}")->getFont()->setBold(true);
            $sheet->getStyle("N{$row}:O{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getRowDimension($row)->setRowHeight(44);
            $row++;
        }

        $lastDataRow = $row - 1;
        $allBorders = [
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
        ];
        $sheet->getStyle("A9:S{$lastDataRow}")->applyFromArray($allBorders);

        $footerRow = $lastDataRow + 3;
        $sheet->mergeCells("J{$footerRow}:S{$footerRow}");
        $sheet->setCellValue("J{$footerRow}", 'Tp.HCM, ngày…..tháng…...năm '.now()->format('Y'));
        $sheet->getStyle("J{$footerRow}")->getFont()->setBold(true)->setItalic(true);
        $sheet->getStyle("J{$footerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $directorRow = $footerRow + 1;
        $sheet->mergeCells("J{$directorRow}:S{$directorRow}");
        $sheet->setCellValue("J{$directorRow}", 'GIÁM ĐỐC CÔNG TY');
        $sheet->getStyle("J{$directorRow}")->getFont()->setBold(true);
        $sheet->getStyle("J{$directorRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $widths = [7, 12, 18, 18, 24, 16, 12, 12, 22, 20, 12, 24, 18, 14, 12, 28, 28, 20, 16];
        foreach ($widths as $index => $width) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index + 1))->setWidth($width);
        }
        $sheet->freezePane('A10');
        $sheet->getPageSetup()->setOrientation('landscape')->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageMargins()->setLeft(0.25)->setRight(0.25)->setTop(0.4)->setBottom(0.4);
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 9);

        $temporaryPath = tempnam(sys_get_temp_dir(), 'msc-bbg-');
        abort_if($temporaryPath === false, 500, 'Không thể tạo file BBG tạm.');
        $excelPath = $temporaryPath.'.xlsx';
        @unlink($temporaryPath);

        (new Xlsx($spreadsheet))->save($excelPath);
        $spreadsheet->disconnectWorksheets();

        return response()->download(
            $excelPath,
            'BBG-Muasamcong-'.now()->format('Ymd-His').'.xlsx',
            ['Cache-Control' => 'no-store, private', 'X-Content-Type-Options' => 'nosniff']
        )->deleteFileAfterSend(true);
    }

    private function medicineGroupNumber(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        preg_match('/\d+/', (string) $value, $matches);

        return $matches[0] ?? null;
    }
}
