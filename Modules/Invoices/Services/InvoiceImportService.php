<?php

namespace Modules\Invoices\Services;

use Carbon\Carbon;
use Modules\Invoices\Models\Invoices;
use Rap2hpoutre\FastExcel\FastExcel;

class InvoiceImportService
{
    public function importExportedRange(
        string $startDate,
        string $endDate,
        bool $purchase,
        ?callable $callback = null
    ): int {
        $directory = trim((string) config('invoices.storage.export_directory', 'gdt'), '/');
        $direction = $purchase ? 'vat_in' : 'vat_out';
        $filePath = storage_path(
            "app/{$directory}/{$direction}/{$direction}_{$startDate}_{$endDate}.xlsx"
        );

        return $this->import($filePath, $purchase ? 'purchase' : 'sold', $callback);
    }

    /**
     * Import Excel vào bảng invoices
     */
    public function import(string $filePath, string $type = 'sold', ?callable $callback = null)
    {
        if (! file_exists($filePath)) {
            throw new \Exception("File không tồn tại: $filePath");
        }

        $callback && $callback("📂 Đang đọc file Excel: $filePath");

        $rows = (new FastExcel)->import($filePath);
        $count = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            try {
                $lookup = trim($row['Mã tra cứu'] ?? '');
                $number = trim($row['Số hóa đơn'] ?? '');
                $tax = trim($row['Mã số thuế'] ?? '');

                $issued = ! empty($row['Ngày lập'])
                    ? Carbon::createFromFormat('d/m/Y', trim($row['Ngày lập']))->format('Y-m-d')
                    : null;

                // 🔍 kiểm tra hóa đơn đã tồn tại?
                $exists = Invoices::where('lookup_code', $lookup)
                    ->where('invoice_number', $number)
                    ->where('issued_date', $issued)
                    ->where('tax_code', $tax)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    $callback && $callback("⚠️ Bỏ qua (đã tồn tại): HĐ số $number – MST $tax");

                    continue;
                }

                // 🧩 Create mới
                Invoices::create([
                    'lookup_code' => $lookup,
                    'symbol' => trim($row['Ký hiệu'] ?? ''),
                    'invoice_number' => $number,
                    'type' => trim($row['Loại hóa đơn'] ?? ''),
                    'issued_date' => $issued,

                    'tax_code' => $tax,
                    'name' => trim($row['Đơn vị'] ?? ''),
                    'address' => trim($row['Địa chỉ'] ?? ''),
                    'email' => trim($row['Email'] ?? ''),
                    'phone' => trim($row['Phone'] ?? ''),

                    'tax_rate' => $this->toDecimal($row['Thuế suất'] ?? 0),
                    'vat_amount' => $this->toDecimal($row['Tiền VAT'] ?? 0),
                    'amount_before_vat' => $this->toDecimal($row['Trước VAT'] ?? 0),
                    'total_amount' => $this->toDecimal($row['Thành tiền'] ?? 0),
                    'invoice_type' => $type === 'sold' ? 'sold' : 'purchase',
                ]);

                $count++;
                $callback && $callback('✔ Đã import hóa đơn số: '.($number ?: 'N/A'));

            } catch (\Throwable $e) {
                $callback && $callback('❌ Lỗi import HĐ số: '.($row['Số hóa đơn'] ?? 'N/A').' – '.$e->getMessage());
            }
        }

        $callback && $callback("🎉 Hoàn tất! Import: $count – Bỏ qua: $skipped");

        return $count;
    }

    private function toDecimal($value)
    {
        if ($value === null || $value === '' || $value === false) {
            return 0;
        }

        // chuyển 1.234.567,89 → 1234567.89
        $value = str_replace(['.', ','], ['', '.'], $value);

        return floatval($value);
    }
}
