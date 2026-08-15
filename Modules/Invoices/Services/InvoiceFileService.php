<?php

namespace Modules\Invoices\Services;

class InvoiceFileService
{
    public function exists(string $lookupCode): bool
    {
        if ($lookupCode === '' || basename($lookupCode) !== $lookupCode) {
            return false;
        }

        return is_file($this->path($lookupCode)) && is_readable($this->path($lookupCode));
    }

    public function pdfPath(string $lookupCode): string
    {
        if (basename($lookupCode) !== $lookupCode) {
            throw new \RuntimeException('Mã tra cứu không hợp lệ.');
        }

        $path = $this->path($lookupCode);

        if (! is_file($path) || ! is_readable($path)) {
            throw new \RuntimeException('Không tìm thấy PDF hóa đơn.');
        }

        return $path;
    }

    private function path(string $lookupCode): string
    {
        $directory = trim((string) config('invoices.storage.pdf_directory', 'hoadon_temp'), '/');

        return storage_path("app/{$directory}/{$lookupCode}.pdf");
    }
}
