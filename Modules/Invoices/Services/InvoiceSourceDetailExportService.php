<?php

namespace Modules\Invoices\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Modules\Invoices\Models\InvoiceSourceRecord;
use Rap2hpoutre\FastExcel\FastExcel;

final class InvoiceSourceDetailExportService
{
    public function download(Collection $sources, string $filename)
    {
        $detailKeys = $this->detailKeys($sources);
        $rows = $this->rows($sources, $detailKeys);

        return (new FastExcel($rows))->download($filename);
    }

    private function rows(Collection $sources, array $detailKeys): Collection
    {
        return $sources->flatMap(function (InvoiceSourceRecord $source) use ($detailKeys): array {
            $details = collect($source->detail_payload['hdhhdvu'] ?? [])
                ->filter(fn ($item) => is_array($item))
                ->values();

            if ($details->isEmpty()) {
                return [$this->row($source, null, 1, $detailKeys)];
            }

            return $details
                ->map(fn (array $detail, int $index) => $this->row($source, $detail, $index + 1, $detailKeys))
                ->all();
        })->values();
    }

    private function row(InvoiceSourceRecord $source, ?array $detail, int $lineNumber, array $detailKeys): array
    {
        $invoice = $source->invoice;
        $row = [
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
            'Thuế suất hóa đơn' => $invoice?->tax_rate,
            'Tiền VAT' => $invoice?->vat_amount,
            'Tiền trước VAT' => $invoice?->amount_before_vat,
            'Tổng thanh toán' => $invoice?->total_amount,
            'Nhà cung cấp dữ liệu' => $source->provider,
            'Trạng thái detail' => $source->detail_status,
            'Phân loại nghiệp vụ' => $this->classificationLabel((string) $source->business_classification),
            'Phân loại chi phí cấp 2' => $source->expenseCategory?->name,
            'Ghi chú phân loại' => $source->business_note,
            'Ghi chú chi phí' => $source->expense_note,
            'Thời điểm lấy header' => $source->header_fetched_at?->format('d/m/Y H:i:s'),
            'Thời điểm lấy detail' => $source->detail_fetched_at?->format('d/m/Y H:i:s'),
            'Lỗi detail gần nhất' => $source->last_error,
            'Dòng chi tiết' => $detail === null ? null : $lineNumber,
        ];

        foreach ($detailKeys as $key) {
            $row['Chi tiết - '.$key] = $detail === null ? null : $this->excelValue(Arr::get($detail, $key));
        }

        return $row;
    }

    private function detailKeys(Collection $sources): array
    {
        $keys = [];
        foreach ($sources as $source) {
            foreach (($source->detail_payload['hdhhdvu'] ?? []) as $detail) {
                if (! is_array($detail)) {
                    continue;
                }
                foreach (array_keys(Arr::dot($detail)) as $key) {
                    $keys[$key] = true;
                }
            }
        }

        return array_keys($keys);
    }

    private function excelValue(mixed $value): mixed
    {
        if ($value === null || is_scalar($value)) {
            return $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function classificationLabel(string $classification): string
    {
        return match ($classification) {
            'GOODS' => 'Hàng hóa',
            'SERVICE_EXPENSE' => 'Dịch vụ / Chi phí',
            'MIXED' => 'Hỗn hợp',
            default => 'Chưa phân loại',
        };
    }
}
