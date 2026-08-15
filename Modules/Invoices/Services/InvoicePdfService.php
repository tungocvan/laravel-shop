<?php

namespace Modules\Invoices\Services;

use Modules\Invoices\Models\Invoices;

class InvoicePdfService
{
    public function __construct(
        private readonly MeInvoiceService $meInvoiceService,
        private readonly InvoiceFileService $fileService,
    ) {}

    public function statusFor(string $lookupCode): string
    {
        if ($lookupCode === '') {
            return 'unsupported';
        }

        return $this->fileService->exists($lookupCode) ? 'available' : 'missing';
    }

    public function downloadInvoice(int $invoiceId, bool $force = false): string
    {
        $invoice = Invoices::query()->findOrFail($invoiceId);
        $lookupCode = trim((string) $invoice->lookup_code);

        if ($lookupCode === '') {
            throw new \RuntimeException('Hóa đơn chưa có mã tra cứu nên không thể tải PDF.');
        }

        if (! $force && $this->fileService->exists($lookupCode)) {
            return $this->fileService->pdfPath($lookupCode);
        }

        return $this->meInvoiceService->downloadOne($lookupCode, $force);
    }

    public function downloadSelected(array $ids, bool $force = false): array
    {
        $ids = collect($ids)
            ->filter(fn ($id) => filter_var($id, FILTER_VALIDATE_INT) !== false && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $result = ['downloaded' => 0, 'existing' => 0, 'failed' => 0, 'errors' => []];

        foreach ($ids as $id) {
            try {
                $invoice = Invoices::query()->find($id);
                if (! $invoice || ! $invoice->lookup_code) {
                    $result['failed']++;
                    $result['errors'][] = "ID {$id}: thiếu mã tra cứu.";
                    continue;
                }

                $alreadyExists = $this->fileService->exists((string) $invoice->lookup_code);
                $this->downloadInvoice($id, $force);

                if ($alreadyExists && ! $force) {
                    $result['existing']++;
                } else {
                    $result['downloaded']++;
                }
            } catch (\Throwable $exception) {
                $result['failed']++;
                $result['errors'][] = "ID {$id}: {$exception->getMessage()}";
            }
        }

        return $result;
    }
}
