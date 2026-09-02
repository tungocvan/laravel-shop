<?php

namespace Modules\Invoices\Services;

use Illuminate\Support\Facades\Log;
use Modules\Invoices\Models\Invoices;
use RuntimeException;

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

        if ($this->fileService->existsForInvoice($invoice)) {
            return 'available';
        }

        return $invoice->file()->value('status') === 'error' ? 'error' : 'missing';
    }

    public function downloadInvoice(int $invoiceId, bool $force = false): string
    {
        $invoice = Invoices::query()->findOrFail($invoiceId);

        if (! $this->canResolveGdtIdentity($invoice)) {
            throw new RuntimeException('Hóa đơn thiếu thông tin định danh để lấy PDF.');
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
            $lookup = trim((string) $invoice->lookup_code);

            if ($lookup !== '' && config('invoices.meinvoice.token')) {
                try {
                    $path = $this->meInvoiceService->downloadOne(
                        $lookup,
                        $force,
                        $this->fileService->targetPdfPathForInvoice($invoice),
                    );
                    $this->fileManager->recordAvailable($invoice, $path, 'meinvoice');

                    return $path;
                } catch (\Throwable $meInvoiceException) {
                    $this->recordProviderFailure($invoice, 'meinvoice', $meInvoiceException);
                    throw new RuntimeException('Không thể tải PDF hóa đơn từ nhà cung cấp. Vui lòng thử lại sau.');
                }
            }

            $this->recordProviderFailure($invoice, 'gdt', $gdtException);
            throw new RuntimeException('Không thể tải PDF hóa đơn từ GDT. Vui lòng thử lại sau.');
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
                    $result['errors'][] = "ID {$id}: thiếu thông tin định danh PDF.";
                    continue;
                }

                $exists = $this->fileService->existsForInvoice($invoice);
                $this->downloadInvoice($id, $force);
                if ($exists && ! $force) {
                    $result['existing']++;
                } else {
                    $result['downloaded']++;
                }
            } catch (\Throwable) {
                $result['failed']++;
                $result['errors'][] = "ID {$id}: không thể tải PDF.";
            }
        }

        return $result;
    }

    private function recordProviderFailure(Invoices $invoice, string $provider, \Throwable $exception): void
    {
        $this->fileManager->recordFailure($invoice, $provider, $exception->getMessage());
        Log::warning('Invoice PDF provider failure.', [
            'invoice_id' => $invoice->getKey(),
            'provider' => $provider,
            'exception' => $exception::class,
            'message' => $exception->getMessage(),
        ]);
    }

    private function canResolveGdtIdentity(Invoices $invoice): bool
    {
        return trim((string) $invoice->tax_code) !== ''
            && trim((string) $invoice->symbol) !== ''
            && trim((string) $invoice->invoice_number) !== '';
    }
}
