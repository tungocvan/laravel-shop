<?php

namespace Modules\Invoices\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Invoices\Models\Invoices;

class MeInvoiceService
{
    public function downloadSelected(array $ids): int
    {
        $lookupCodes = Invoices::query()
            ->whereIn('id', $ids)
            ->pluck('lookup_code')
            ->filter()
            ->unique()
            ->values();

        $count = 0;
        foreach ($lookupCodes as $lookupCode) {
            $this->downloadOne((string) $lookupCode);
            $count++;
        }

        return $count;
    }

    public function downloadOne(string $lookupCode, bool $force = false, ?string $targetFile = null): string
    {
        if ($lookupCode === '' || basename($lookupCode) !== $lookupCode) {
            throw new \RuntimeException('Mã tra cứu không hợp lệ.');
        }

        if ($targetFile === null) {
            $directory = trim((string) config('invoices.storage.pdf_directory', 'hoadon_temp'), '/');
            $targetFile = storage_path("app/{$directory}/{$lookupCode}.pdf");
        }

        $targetDirectory = dirname($targetFile);
        if (! is_dir($targetDirectory) && ! mkdir($targetDirectory, 0775, true) && ! is_dir($targetDirectory)) {
            throw new \RuntimeException('Không thể tạo thư mục lưu PDF.');
        }

        if (! $force && is_file($targetFile) && is_readable($targetFile)) {
            return $targetFile;
        }

        $token = config('invoices.meinvoice.token');
        if (! $token) {
            throw new \RuntimeException('Chưa cấu hình MEINVOICE_API_TOKEN.');
        }

        try {
            $response = Http::withToken($token)->acceptJson()->asJson()->post(
                rtrim((string) config('invoices.meinvoice.base_url'), '/').'/invoice/publishview',
                [$lookupCode]
            );
        } catch (ConnectionException $exception) {
            Log::warning('Không thể kết nối API MeInvoice.', [
                'lookup_code' => $lookupCode,
                'error' => $exception->getMessage(),
            ]);
            throw new \RuntimeException('Không thể kết nối MeInvoice.', previous: $exception);
        }

        $pdfUrl = $response->json('data');
        if (! $response->successful() || ! is_string($pdfUrl) || $pdfUrl === '') {
            throw new \RuntimeException("MeInvoice không trả về PDF cho hóa đơn {$lookupCode} (HTTP {$response->status()}).");
        }

        try {
            $pdfResponse = Http::timeout((int) config('invoices.gdt.timeout', 15))->get($pdfUrl);
        } catch (ConnectionException $exception) {
            throw new \RuntimeException('Không thể tải PDF từ MeInvoice.', previous: $exception);
        }

        if (! $pdfResponse->successful()) {
            throw new \RuntimeException("Không thể tải PDF hóa đơn {$lookupCode} (HTTP {$pdfResponse->status()}).");
        }

        $body = $pdfResponse->body();
        if (! str_starts_with($body, '%PDF-')) {
            throw new \RuntimeException("Dữ liệu trả về cho hóa đơn {$lookupCode} không phải PDF hợp lệ.");
        }

        if (file_put_contents($targetFile, $body, LOCK_EX) === false) {
            throw new \RuntimeException("Không thể lưu PDF hóa đơn {$lookupCode}.");
        }

        return $targetFile;
    }
}
