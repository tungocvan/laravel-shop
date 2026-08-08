<?php

namespace Modules\Invoices\Services;

class InvoiceFileService
{
    public function pdfPath(string $lookupCode): string
    {
        if (basename($lookupCode) !== $lookupCode) {
            throw new \RuntimeException('Mã tra cứu không hợp lệ.');
        }

        $directory = trim((string) config('invoices.storage.pdf_directory', 'hoadon_temp'), '/');
        $path = storage_path("app/{$directory}/{$lookupCode}.pdf");

        if (! file_exists($path)) {
            throw new \RuntimeException('Không tìm thấy PDF hóa đơn.');
        }

        return $path;
    }
}
