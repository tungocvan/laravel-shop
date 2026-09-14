<?php

namespace Modules\Pharma\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MedicineCatalogTemplateExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithStyles
{
    public function headings(): array
    {
        return [
            'STT',
            'STT TT20/2022',
            'Nhóm thuốc',
            'Nhóm thuốc điều trị',
            'Thuốc KSĐB',
            'Tên hoạt chất',
            'Nồng độ - Hàm lượng',
            'Tên biệt dược',
            'Dạng bào chế',
            'Đường dùng',
            'Đơn vị tính',
            'Quy cách đóng gói',
            'Giấy phép lưu hành sản phẩm',
            'Hạn dùng',
            'Cơ sở sản xuất',
            'Nước sản xuất',
            'Giá KK/ KKL',
        ];
    }

    public function array(): array
    {
        return [[
            '1',
            '',
            '',
            '',
            '',
            'Salbutamol',
            '2mg/5ml; 150ml',
            'Sallet',
            'Siro',
            'Uống',
            'Lọ',
            'Hộp 1 lọ 150ml',
            'VD-34495-20',
            '36 tháng',
            'Cơ sở sản xuất mẫu',
            'Việt Nam',
            23809.524,
        ]];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'B' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_TEXT,
            'E' => NumberFormat::FORMAT_TEXT,
            'F' => NumberFormat::FORMAT_TEXT,
            'G' => NumberFormat::FORMAT_TEXT,
            'H' => NumberFormat::FORMAT_TEXT,
            'I' => NumberFormat::FORMAT_TEXT,
            'J' => NumberFormat::FORMAT_TEXT,
            'K' => NumberFormat::FORMAT_TEXT,
            'L' => NumberFormat::FORMAT_TEXT,
            'M' => NumberFormat::FORMAT_TEXT,
            'N' => NumberFormat::FORMAT_TEXT,
            'O' => NumberFormat::FORMAT_TEXT,
            'P' => NumberFormat::FORMAT_TEXT,
            'Q' => '#,##0.########',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
