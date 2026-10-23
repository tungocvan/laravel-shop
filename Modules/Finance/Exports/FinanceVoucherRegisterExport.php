<?php

namespace Modules\Finance\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Modules\Finance\Services\FinanceReportService;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

final class FinanceVoucherRegisterExport implements FromQuery, WithHeadings, WithMapping, WithColumnFormatting
{
    public function __construct(private readonly array $filters = [])
    {
    }

    public function query()
    {
        return app(FinanceReportService::class)->query($this->filters)
            ->orderBy('v.voucher_date')->orderBy('v.id');
    }

    public function headings(): array
    {
        return ['Ngày chứng từ', 'Số chứng từ', 'Nguồn chứng từ', 'Đối tượng',
            'Diễn giải', 'Nghiệp vụ', 'Tài khoản', 'Thu (VND)', 'Chi (VND)', 'Trạng thái'];
    }

    public function map($row): array
    {
        $amount = (float) $row->signed_amount;

        return [
            \Illuminate\Support\Carbon::parse($row->voucher_date)->format('d/m/Y'),
            $row->number,
            $row->source_document_code ?: ($row->reference_number ?: 'Thủ công'),
            $row->counterparty,
            $row->description,
            $row->category_name ?: $row->manual_category,
            $row->account_code.' — '.$row->account_name,
            $amount > 0 ? $amount : 0,
            $amount < 0 ? abs($amount) : 0,
            $row->status === 'reversed' ? 'Đã hoàn tác (thuần 0)' : 'Đã ghi sổ',
        ];
    }

    public function columnFormats(): array
    {
        return ['H' => '#,##0.00', 'I' => '#,##0.00'];
    }
}
