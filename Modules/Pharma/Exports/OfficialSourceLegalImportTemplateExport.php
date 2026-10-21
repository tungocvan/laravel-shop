<?php

namespace Modules\Pharma\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class OfficialSourceLegalImportTemplateExport implements FromArray, WithHeadings, WithColumnFormatting
{
    public function headings(): array
    {
        return [
            'Mã CSKCB',
            'Tên cơ sở khám chữa bệnh',
            'Mã số thuế đã xác nhận',
            'Địa chỉ pháp lý đã xác nhận',
        ];
    }

    public function array(): array
    {
        return [];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
        ];
    }
}
