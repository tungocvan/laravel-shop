<?php

namespace Modules\Invoices\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Modules\Invoices\Models\InvoiceSourceRecord;
use Rap2hpoutre\FastExcel\FastExcel;

final class InvoiceSourceDetailExportService
{
    public const HEADERS = [
        'Loại hóa đơn',
        'Mã tra cứu',
        'Ký hiệu',
        'Số hóa đơn',
        'Loại / tên hóa đơn',
        'Ngày lập',
        'Mã số thuế đối tác',
        'Đơn vị / đối tác',
        'Địa chỉ',
        'Email',
        'Số điện thoại',
        'Tiền VAT',
        'Tiền trước VAT',
        'Tổng thanh toán',
        'Chi tiết - ten',
        'Chi tiết - dgia',
        'Chi tiết - dvtinh',
        'Chi tiết - ltsuat',
        'Chi tiết - sluong',
        'Chi tiết - thtien',
    ];

    public function download(Collection $sources, string $filename)
    {
        return (new FastExcel($this->rows($sources)))->download($filename);
    }

    private function rows(Collection $sources): Collection
    {
        return $sources->flatMap(function (InvoiceSourceRecord $source): array {
            $details = collect($source->detail_payload['hdhhdvu'] ?? [])
                ->filter(fn ($item) => is_array($item))
                ->filter(fn (array $item) => $this->hasExportableAmount($item))
                ->values();

            if ($details->isEmpty()) {
                return [];
            }

            return $details
                ->map(fn (array $detail) => $this->row($source, $detail))
                ->all();
        })->values();
    }

    private function hasExportableAmount(array $detail): bool
    {
        $amount = Arr::get($detail, 'thtien');

        if ($amount === null || $amount === '') {
            return false;
        }

        if (is_numeric($amount)) {
            return (float) $amount != 0.0;
        }

        $normalized = str_replace([',', ' '], '', (string) $amount);

        return ! is_numeric($normalized) || (float) $normalized != 0.0;
    }

    private function row(InvoiceSourceRecord $source, array $detail): array
    {
        $invoice = $source->invoice;

        return [
            'Loại hóa đơn' => $invoice?->invoice_type === 'sold' ? 'Bán ra' : 'Mua vào',
            'Mã tra cứu' => $invoice?->lookup_code,
            'Ký hiệu' => $invoice?->symbol,
            'Số hóa đơn' => $invoice?->invoice_number,
            'Loại / tên hóa đơn' => $invoice?->type,
            'Ngày lập' => $invoice?->issued_date?->format('d/m/Y'),
            'Mã số thuế đối tác' => $invoice?->tax_code,
            'Đơn vị / đối tác' => $invoice?->name,
            'Địa chỉ' => $invoice?->address,
            'Email' => $invoice?->email,
            'Số điện thoại' => $invoice?->phone,
            'Tiền VAT' => $invoice?->vat_amount,
            'Tiền trước VAT' => $invoice?->amount_before_vat,
            'Tổng thanh toán' => $invoice?->total_amount,
            'Chi tiết - ten' => $this->excelValue(Arr::get($detail, 'ten')),
            'Chi tiết - dgia' => $this->excelValue(Arr::get($detail, 'dgia')),
            'Chi tiết - dvtinh' => $this->excelValue(Arr::get($detail, 'dvtinh')),
            'Chi tiết - ltsuat' => $this->excelValue(Arr::get($detail, 'ltsuat')),
            'Chi tiết - sluong' => $this->excelValue(Arr::get($detail, 'sluong')),
            'Chi tiết - thtien' => $this->excelValue(Arr::get($detail, 'thtien')),
        ];
    }

    private function excelValue(mixed $value): mixed
    {
        if ($value === null || is_scalar($value)) {
            return $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
