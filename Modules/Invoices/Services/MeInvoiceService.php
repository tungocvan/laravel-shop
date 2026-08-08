<?php

namespace Modules\Invoices\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Invoices\Models\Invoices;
use setasign\Fpdi\Fpdi;

class MeInvoiceService
{
    public function downloadSelected(array $ids): int
    {
        $lookupCodes = Invoices::query()->whereIn('id', $ids)->pluck('lookup_code')->filter()->values()->all();
        $directory = trim((string) config('invoices.storage.pdf_directory', 'hoadon_temp'), '/');
        $targetDirectory = storage_path("app/{$directory}");

        if (! is_dir($targetDirectory) && ! mkdir($targetDirectory, 0755, true) && ! is_dir($targetDirectory)) {
            throw new \RuntimeException('Không thể tạo thư mục lưu PDF.');
        }

        $lookupCodes = array_values(array_filter(
            $lookupCodes,
            fn (string $code) => ! file_exists($targetDirectory.'/'.$code.'.pdf')
        ));

        if ($lookupCodes === []) {
            return 0;
        }

        $token = config('invoices.meinvoice.token');
        if (! $token) {
            throw new \RuntimeException('Chưa cấu hình MEINVOICE_API_TOKEN.');
        }

        try {
            $response = Http::withToken($token)->acceptJson()->asJson()->post(
                rtrim((string) config('invoices.meinvoice.base_url'), '/').'/invoice/publishview',
                $lookupCodes
            );
        } catch (ConnectionException $exception) {
            Log::warning('Không thể kết nối API MeInvoice.', ['error' => $exception->getMessage()]);
            throw new \RuntimeException('Không thể kết nối MeInvoice.', previous: $exception);
        }

        if (! $response->successful() || ! $response->json('data')) {
            throw new \RuntimeException("MeInvoice trả lỗi HTTP {$response->status()}.");
        }

        try {
            $pdfResponse = Http::timeout((int) config('invoices.gdt.timeout', 15))->get($response->json('data'));
        } catch (ConnectionException $exception) {
            throw new \RuntimeException('Không thể tải PDF từ MeInvoice.', previous: $exception);
        }

        if (! $pdfResponse->successful()) {
            throw new \RuntimeException("Không thể tải PDF (HTTP {$pdfResponse->status()}).");
        }

        $sourceFile = $targetDirectory.'/hoadon_full_'.now()->format('Ymd_His').'.pdf';
        file_put_contents($sourceFile, $pdfResponse->body());

        try {
            $source = new Fpdi;
            $pageCount = $source->setSourceFile($sourceFile);

            if ($pageCount < count($lookupCodes)) {
                throw new \RuntimeException('Số trang PDF không khớp số hóa đơn.');
            }

            foreach ($lookupCodes as $index => $code) {
                $pdf = new Fpdi;
                $pdf->setSourceFile($sourceFile);
                $template = $pdf->importPage($index + 1);
                $pdf->AddPage();
                $pdf->useTemplate($template);
                $pdf->Output($targetDirectory.'/'.$code.'.pdf', 'F');
            }
        } finally {
            if (file_exists($sourceFile)) {
                unlink($sourceFile);
            }
        }

        return count($lookupCodes);
    }
}
