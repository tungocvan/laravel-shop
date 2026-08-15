<?php

namespace Modules\Invoices\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InvoicePartnerReportExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows->map(fn ($row) => [
            'partner_name' => $row->partner_name,
            'partner_tax_code' => $row->partner_tax_code,
            'invoice_count' => (int) $row->invoice_count,
            'sold_count' => (int) $row->sold_count,
            'purchase_count' => (int) $row->purchase_count,
            'sold_total' => $row->sold_total,
            'purchase_total' => $row->purchase_total,
            'vat_total' => $row->vat_total,
            'net_total' => $row->net_total,
        ]);
    }

    public function headings(): array
    {
        return ['Đối tác', 'MST', 'Số hóa đơn', 'HĐ bán ra', 'HĐ mua vào', 'Bán ra', 'Mua vào', 'VAT', 'Chênh lệch'];
    }
}
