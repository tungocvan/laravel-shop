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

        if (! ($report['ok'] ?? false) && ! empty($report['errors'])) {
            $first = $report['errors'][0]['message'] ?? 'Import hóa đơn không thành công.';
            $callback && $callback("❌ {$first}");
        }

        $callback && $callback("🎉 Hoàn tất! Import: {$success} – Bỏ qua: {$skipped}");

        return $success;
    }
}
