<?php

namespace Modules\Invoices\Services;

use Modules\Invoices\Models\Invoices;

class InvoiceFileService
{
    public function exists(string $lookupCode): bool
    {
        if ($lookupCode === '' || basename($lookupCode) !== $lookupCode) {
            return false;
        }

        return is_file($this->path($lookupCode)) && is_readable($this->path($lookupCode));
    }

    public function existsForInvoice(Invoices $invoice): bool
    {
        return is_file($this->path($this->storageKey($invoice)))
            && is_readable($this->path($this->storageKey($invoice)));
    }

    public function pdfPath(string $lookupCode): string
    {
        if (basename($lookupCode) !== $lookupCode) {
            throw new \RuntimeException('Mã tra cứu không hợp lệ.');
        }

        return $this->assertReadable($this->path($lookupCode));
    }

    public function pdfPathForInvoice(Invoices $invoice): string
    {
        return $this->assertReadable($this->path($this->storageKey($invoice)));
    }

    public function targetPdfPath(string $lookupCode): string
    {
        if ($lookupCode === '' || basename($lookupCode) !== $lookupCode) {
            throw new \RuntimeException('Mã tra cứu không hợp lệ.');
        }

        return $this->path($lookupCode);
    }

    public function targetPdfPathForInvoice(Invoices $invoice): string
    {
        return $this->path($this->storageKey($invoice));
    }

    public function storageKey(Invoices $invoice): string
    {
        $lookupCode = trim((string) $invoice->lookup_code);
        if ($lookupCode !== '' && basename($lookupCode) === $lookupCode) {
            return $lookupCode;
        }

        return 'invoice-'.$invoice->getKey();
    }

    private function assertReadable(string $path): string
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new \RuntimeException('Không tìm thấy PDF hóa đơn.');
        }

        return $path;
    }

    private function path(string $key): string
    {
        $directory = trim((string) config('invoices.storage.pdf_directory', 'hoadon_temp'), '/');

        return storage_path("app/{$directory}/{$key}.pdf");
    }
}
