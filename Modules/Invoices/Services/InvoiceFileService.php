<?php

namespace Modules\Invoices\Services;

use Illuminate\Support\Str;
use Modules\Invoices\Models\Invoices;

class InvoiceFileService
{
    public function exists(string $lookupCode): bool
    {
        if ($lookupCode === '' || basename($lookupCode) !== $lookupCode) {
            return false;
        }

        return $this->isReadable($this->legacyPath($lookupCode));
    }

    public function existsForInvoice(Invoices $invoice): bool
    {
        return $this->resolveExistingPath($invoice) !== null;
    }

    public function pdfPath(string $lookupCode): string
    {
        if ($lookupCode === '' || basename($lookupCode) !== $lookupCode) {
            throw new \RuntimeException('Mã tra cứu không hợp lệ.');
        }

        return $this->assertReadable($this->legacyPath($lookupCode));
    }

    public function pdfPathForInvoice(Invoices $invoice): string
    {
        $path = $this->resolveExistingPath($invoice);

        if ($path === null) {
            throw new \RuntimeException('Không tìm thấy PDF hóa đơn.');
        }

        return $path;
    }

    public function targetPdfPath(string $lookupCode): string
    {
        if ($lookupCode === '' || basename($lookupCode) !== $lookupCode) {
            throw new \RuntimeException('Mã tra cứu không hợp lệ.');
        }

        return $this->legacyPath($lookupCode);
    }

    public function targetPdfPathForInvoice(Invoices $invoice): string
    {
        return storage_path('app/'.$this->relativePathForInvoice($invoice));
    }

    public function relativePathForInvoice(Invoices $invoice): string
    {
        $date = $invoice->issued_date ?: $invoice->created_at ?: now();
        $year = $date->format('Y');
        $month = $date->format('m');
        $type = $invoice->invoice_type === 'purchase' ? 'purchase' : 'sold';

        $base = trim((string) config('invoices.storage.pdf_archive_directory', 'invoices/pdf'), '/');

        return $base.'/'.$year.'/'.$month.'/'.$type.'/'.$this->filenameForInvoice($invoice);
    }

    public function filenameForInvoice(Invoices $invoice): string
    {
        $date = $invoice->issued_date ?: $invoice->created_at ?: now();
        $datePart = $date->format('Y-m-d');
        $number = $this->safePart((string) $invoice->invoice_number, 'HD-'.$invoice->getKey());
        $taxCode = $this->safePart((string) $invoice->tax_code, 'NO-MST');
        $partner = Str::slug((string) $invoice->name, '-');
        $partner = $partner !== '' ? Str::limit($partner, 50, '') : 'doi-tac';

        return "{$datePart}_HD-{$number}_{$taxCode}_{$partner}.pdf";
    }

    public function storageKey(Invoices $invoice): string
    {
        $lookupCode = trim((string) $invoice->lookup_code);
        if ($lookupCode !== '' && basename($lookupCode) === $lookupCode) {
            return $lookupCode;
        }

        return 'invoice-'.$invoice->getKey();
    }

    private function resolveExistingPath(Invoices $invoice): ?string
    {
        $structured = storage_path('app/'.$this->relativePathForInvoice($invoice));
        if ($this->isReadable($structured)) {
            return $structured;
        }

        $legacy = $this->legacyPath($this->storageKey($invoice));
        if ($this->isReadable($legacy)) {
            return $legacy;
        }

        return null;
    }

    private function safePart(string $value, string $fallback): string
    {
        $value = trim($value);
        $value = preg_replace('/[^A-Za-z0-9._-]+/', '-', $value) ?: '';
        $value = trim($value, '-._');

        return $value !== '' ? Str::limit($value, 60, '') : $fallback;
    }

    private function assertReadable(string $path): string
    {
        if (! $this->isReadable($path)) {
            throw new \RuntimeException('Không tìm thấy PDF hóa đơn.');
        }

        return $path;
    }

    private function isReadable(string $path): bool
    {
        return is_file($path) && is_readable($path);
    }

    private function legacyPath(string $key): string
    {
        $directory = trim((string) config('invoices.storage.pdf_directory', 'hoadon_temp'), '/');

        return storage_path("app/{$directory}/{$key}.pdf");
    }
}
