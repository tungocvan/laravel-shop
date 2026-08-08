<?php

namespace Modules\Invoices\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InvoicesSelectedExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly Collection $invoices) {}

    public function collection()
    {
        return $this->invoices->map->only([
            'id',
            'lookup_code',
            'symbol',
            'invoice_number',
            'issued_date',
            'name',
            'tax_code',
            'tax_rate',
            'total_amount',
            'vat_amount',
        ]);
    }

    public function headings(): array
    {
        return [
            'ID',
            'Mã tra cứu',
            'Ký hiệu',
            'Số HĐ',
            'Ngày lập',
            'Tên',
            'MST',
            'Thuế suất',
            'Thành tiền',
            'VAT',
        ];
    }
}
