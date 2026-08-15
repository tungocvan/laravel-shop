<?php

namespace Modules\Invoices\Services;

class InvoiceImportService
{
    public function __construct(
        private readonly InvoiceImportExportService $importExportService
    ) {}

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
     * Backward-compatible adapter for existing GDT workflows and CLI commands.
     */
    public function import(string $filePath, string $type = 'sold', ?callable $callback = null): int
    {
        if (! in_array($type, ['sold', 'purchase'], true)) {
            throw new \InvalidArgumentException('Loại hóa đơn chỉ được là sold hoặc purchase.');
        }

        $callback && $callback("📂 Đang đọc file Excel: {$filePath}");

        $report = $this->importExportService->importForType($filePath, $type);
        $success = (int) ($report['success_rows'] ?? 0);
        $skipped = (int) ($report['skipped_rows'] ?? 0);
        $errors = (int) ($report['error_rows'] ?? 0);
        $total = (int) ($report['total_rows'] ?? ($success + $skipped + $errors));
        $ok = (bool) ($report['success'] ?? false);

        if ($errors > 0) {
            $first = $report['errors'][0]['reason'] ?? 'Có dòng dữ liệu không hợp lệ.';

            if ($success > 0 || $skipped > 0) {
                $callback && $callback(
                    "⚠️ Import một phần: {$success} thành công, {$skipped} bỏ qua, {$errors} lỗi. Lỗi đầu tiên: {$first}"
                );
            } else {
                $callback && $callback("❌ Import thất bại: {$errors}/{$total} dòng lỗi. Lỗi đầu tiên: {$first}");
            }
        } elseif ($ok) {
            $callback && $callback('✅ Dữ liệu hợp lệ, không phát hiện lỗi import.');
        }

        $callback && $callback(
            "🎉 Hoàn tất! Tổng: {$total} – Import: {$success} – Bỏ qua: {$skipped} – Lỗi: {$errors}"
        );

        return $success;
    }
}
