<?php

namespace Modules\Muasamcong\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ContractorKqlcntWorkbookExport implements WithMultipleSheets
{
    public function __construct(private readonly array $sheets) {}

    public function sheets(): array
    {
        return array_map(
            fn (array $sheet): KqlcntArraySheetExport => new KqlcntArraySheetExport(
                $sheet['title'],
                $sheet['headings'],
                $sheet['rows'],
            ),
            $this->sheets
        );
    }
}
