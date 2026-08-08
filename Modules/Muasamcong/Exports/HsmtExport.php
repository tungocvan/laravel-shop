<?php

namespace Modules\Muasamcong\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class HsmtExport implements FromArray, WithHeadings
{
    public function __construct(private readonly array $data) {}

    public function array(): array
    {
        return array_map('array_values', $this->data);
    }

    public function headings(): array
    {
        return [
            'Tên gói thầu',
            'Mã TBMT',
            'Ngày đăng tải',
            'Đóng thầu',
            'Bên mời thầu',
            'Tỉnh',
        ];
    }
}
