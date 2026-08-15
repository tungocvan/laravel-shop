<?php

namespace Modules\Invoices\Services;

use Modules\Invoices\Models\Invoices;

class InvoicePdfService
{
    public function __construct(
        private readonly GdtPdfService $gdtPdfService,
        private readonly MeInvoiceService $meInvoiceService,
        private readonly InvoiceFileService $fileService,
        private readonly InvoiceFileManagerService $fileManager,
    ) {}

    public function statusForInvoice(Invoices $invoice): string
    {
        if (! $this->canResolveGdtIdentity($invoice)) {
            return 'unsupported';
        }

        return $this->fileService->existsForInvoice($invoice) ? 'available' : 'missing';
    }

    public function downloadInvoice(int $invoiceId, bool $force = false): string
    {
        $invoice = Invoices::query()->findOrFail($invoiceId);

        if (! $this->canResolveGdtIdentity($invoice)) {
            throw new \RuntimeException('Hóa đơn thiếu thông tin định danh để lấy PDF từ GDT.');
        }

        if (! $force && $this->fileService->existsForInvoice($invoice)) {
            $path = $this->fileService->pdfPathForInvoice($invoice);
            $provider = str_contains(str_replace('\\', '/', $path), '/hoadon_temp/') ? 'legacy' : 'local';
            $this->fileManager->recordAvailable($invoice, $path, $provider);

            return $path;
        }

        try {
            $path = $this->gdtPdfService->downloadInvoice($invoice, $force);
            $this->fileManager->recordAvailable($invoice, $path, 'gdt');

            return $path;
        } catch (\Throwable $gdtException) {
            $lookupCode = trim((string) $invoice->lookup_code);

            if ($lookupCode !== '' && config('invoices.meinvoice.token')) {
                try {
                    $path = $this->meInvoiceService->downloadOne(
                        $lookupCode,
                        $force,
                        $this->fileService->targetPdfPathForInvoice($invoice)
                    );
                    $this->fileManager->recordAvailable($invoice, $path, 'meinvoice');

                    return $path;
                } catch (\Throwable $meInvoiceException) {
                    $this->fileManager->recordFailure($invoice, 'meinvoice', $meInvoiceException->getMessage());
                    throw $meInvoiceException;
                }
            }

            $this->fileManager->recordFailure($invoice, 'gdt', $gdtException->getMessage());
            throw $gdtException;
        }
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
                if (! $invoice || ! $this->canResolveGdtIdentity($invoice)) {
                    $result['failed']++;
                    $result['errors'][] = "ID {$id}: thiếu thông tin định danh GDT.";
                    continue;
                }

                $alreadyExists = $this->fileService->existsForInvoice($invoice);
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

    private function canResolveGdtIdentity(Invoices $invoice): bool
    {
        return trim((string) $invoice->tax_code) !== ''
            && trim((string) $invoice->symbol) !== ''
            && trim((string) $invoice->invoice_number) !== '';
    }
}
