<?php

namespace Modules\Invoices\Services;

use Illuminate\Support\Str;
use Modules\Invoices\Models\InvoiceFile;
use Modules\Invoices\Models\Invoices;
use RuntimeException;
use ZipArchive;

class InvoiceFileManagerService
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly InvoiceFileService $fileService,
    ) {}

    public function recordAvailable(Invoices $invoice, string $path, string $provider): InvoiceFile
    {
        return InvoiceFile::query()->updateOrCreate(
            ['invoice_id' => $invoice->getKey()],
            [
                'provider' => $provider,
                'status' => 'available',
                'path' => $this->relativeStoragePath($path),
                'size' => is_file($path) ? filesize($path) : null,
                'last_error' => null,
                'downloaded_at' => now(),
            ]
        );
    }

    public function recordFailure(Invoices $invoice, string $provider, string $error): InvoiceFile
    {
        return InvoiceFile::query()->updateOrCreate(
            ['invoice_id' => $invoice->getKey()],
            [
                'provider' => $provider,
                'status' => 'error',
                'path' => null,
                'size' => null,
                'last_error' => Str::limit($error, 2000, ''),
                'downloaded_at' => null,
            ]
        );
    }

    public function summary(array $filters): array
    {
        $invoiceIds = $this->invoiceService->filteredBuilder($filters)->select('id');
        $total = (clone $invoiceIds)->count();

        $available = InvoiceFile::query()
            ->whereIn('invoice_id', clone $invoiceIds)
            ->where('status', 'available')
            ->count();

        $errors = InvoiceFile::query()
            ->whereIn('invoice_id', clone $invoiceIds)
            ->where('status', 'error')
            ->count();

        $size = (int) InvoiceFile::query()
            ->whereIn('invoice_id', clone $invoiceIds)
            ->where('status', 'available')
            ->sum('size');

        return [
            'total' => $total,
            'available' => $available,
            'error' => $errors,
            'missing' => max(0, $total - $available - $errors),
            'size' => $size,
        ];
    }

    public function reconcile(array $filters, int $limit = 1000): array
    {
        $invoices = $this->invoiceService->filteredBuilder($filters)
            ->orderByDesc('issued_date')
            ->limit(max(1, min($limit, 5000)))
            ->get();

        $available = 0;
        $missing = 0;

        foreach ($invoices as $invoice) {
            $file = InvoiceFile::query()->where('invoice_id', $invoice->getKey())->first();

            if ($this->fileService->existsForInvoice($invoice)) {
                $path = $this->fileService->pdfPathForInvoice($invoice);
                $detectedProvider = str_contains(str_replace('\\', '/', $path), '/hoadon_temp/') ? 'legacy' : 'local';
                $provider = $file?->provider ?: $detectedProvider;
                $this->recordAvailable($invoice, $path, $provider);
                $available++;
                continue;
            }

            if ($file?->status === 'available') {
                $file->update([
                    'status' => 'missing',
                    'path' => null,
                    'size' => null,
                    'downloaded_at' => null,
                ]);
            }
            $missing++;
        }

        return [
            'scanned' => $invoices->count(),
            'available' => $available,
            'missing' => $missing,
        ];
    }

    public function missingInvoiceIds(array $filters, int $limit = 25): array
    {
        $limit = max(1, min($limit, 100));

        return $this->invoiceService->filteredBuilder($filters)
            ->whereDoesntHave('file', fn ($query) => $query->where('status', 'available'))
            ->orderByDesc('issued_date')
            ->limit($limit)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function createZip(array $filters): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP chưa cài extension zip (ZipArchive).');
        }

        $invoices = $this->invoiceService->filter($filters);
        $archiveDirectory = storage_path('app/invoices/archives');

        if (! is_dir($archiveDirectory) && ! mkdir($archiveDirectory, 0775, true) && ! is_dir($archiveDirectory)) {
            throw new RuntimeException('Không thể tạo thư mục lưu ZIP hóa đơn.');
        }

        $filename = $this->archiveFilename($filters);
        $path = $archiveDirectory.'/'.$filename;
        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Không thể tạo file ZIP hóa đơn.');
        }

        $added = 0;
        foreach ($invoices as $invoice) {
            if (! $this->fileService->existsForInvoice($invoice)) {
                continue;
            }

            $pdfPath = $this->fileService->pdfPathForInvoice($invoice);
            $zip->addFile($pdfPath, $this->fileService->filenameForInvoice($invoice));
            $added++;
        }

        $zip->close();

        if ($added === 0) {
            @unlink($path);
            throw new RuntimeException('Bộ lọc hiện tại chưa có PDF nào để đóng gói ZIP.');
        }

        return ['path' => $path, 'filename' => $filename, 'count' => $added];
    }

    private function archiveFilename(array $filters): string
    {
        $type = match ($filters['invoice_type'] ?? null) {
            'sold' => 'ban-ra',
            'purchase' => 'mua-vao',
            default => 'tat-ca',
        };

        $from = preg_replace('/[^0-9-]/', '', (string) ($filters['issued_date_from'] ?? '')) ?: 'all';
        $to = preg_replace('/[^0-9-]/', '', (string) ($filters['issued_date_to'] ?? '')) ?: 'all';

        return "hoa-don_{$type}_{$from}_{$to}.zip";
    }

    private function relativeStoragePath(string $path): string
    {
        $root = rtrim(str_replace('\\', '/', storage_path('app')), '/').'/';
        $normalized = str_replace('\\', '/', $path);

        return str_starts_with($normalized, $root)
            ? substr($normalized, strlen($root))
            : basename($normalized);
    }
}
