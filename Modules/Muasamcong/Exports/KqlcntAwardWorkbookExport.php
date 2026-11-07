<?php

namespace Modules\Muasamcong\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * One TBMT per workbook. Sheet 1 contains package metadata and contractor rows;
 * sheet 2 contains the canonical medicine rows, including optimistic-lock columns.
 */
class KqlcntAwardWorkbookExport implements WithMultipleSheets
{
    public function __construct(
        private readonly array $packageRows,
        private readonly array $medicineHeadings,
        private readonly array $medicineRows,
    ) {}

    public function sheets(): array
    {
        return [
            new KqlcntArraySheetExport('Thong_tin_trung_thau', ['Trường', 'Giá trị', 'Mã nhà thầu', 'Tên nhà thầu', 'Số hợp đồng', 'Thời gian thực hiện', 'Đơn vị thời gian'], $this->packageRows),
            new KqlcntArraySheetExport('Danh_muc_trung_thau', $this->medicineHeadings, $this->medicineRows),
        ];
    }
}
