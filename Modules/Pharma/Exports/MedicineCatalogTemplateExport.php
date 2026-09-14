<?php

namespace Modules\Pharma\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MedicineCatalogTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles
{
    public function headings(): array
    {
        return [
            'STT',
            'STT TT20/2022',
            'Nhóm thuốc',
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
            'Giá KK/KL',
        ];
    }

    public function array(): array
    {
        return [[
            1,
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

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
