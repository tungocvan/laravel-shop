<?php

namespace Modules\Invoices\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Invoices\Models\InvoiceInventorySnapshot;
use Modules\Invoices\Models\InvoiceSourceRecord;
use Modules\Invoices\Models\Invoices;
use RuntimeException;
use Throwable;

class GdtPdfService
{
    public function __construct(private readonly InvoiceFileService $fileService) {}

    public function downloadInvoice(Invoices $invoice, bool $force = false): string
    {
        if (! $force && $this->fileService->existsForInvoice($invoice)) {
            return $this->fileService->pdfPathForInvoice($invoice);
        }

        $detail = $this->fetchDetail($invoice);
        $path = $this->fileService->targetPdfPathForInvoice($invoice);
        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('Không thể tạo thư mục lưu PDF hóa đơn.');
        }

        Pdf::loadView('Invoices::pdf.gdt-invoice', [
            'detail' => $detail,
            'invoice' => $invoice,
        ])->setPaper('a4')->save($path);

        if (! is_file($path) || filesize($path) === 0) {
            throw new RuntimeException('Không tạo được PDF từ dữ liệu chi tiết GDT.');
        }

        return $path;
    }

    /**
     * Read the canonical persisted GDT detail without a network request.
     * Legacy Inventory snapshots are imported once for backward compatibility.
     */
    public function storedDetail(Invoices $invoice): ?array
    {
        $source = InvoiceSourceRecord::query()
            ->where('invoice_id', $invoice->id)
            ->where('provider', 'gdt')
            ->first();

        if ($source?->hasUsableDetail()) {
            return $source->detail_payload;
        }

        $legacy = InvoiceInventorySnapshot::query()
            ->where('invoice_id', $invoice->id)
            ->where('source', 'gdt_detail')
            ->first();
        $payload = $legacy?->raw_payload;

        if (! is_array($payload) || ! is_array($payload['hdhhdvu'] ?? null) || $payload['hdhhdvu'] === []) {
            return null;
        }

        $hash = $this->payloadHash($payload);
        $source = InvoiceSourceRecord::query()->firstOrCreate(
            ['invoice_id' => $invoice->id, 'provider' => 'gdt'],
            ['source_version' => 'gdt-v1'],
        );
        $source->forceFill([
            'detail_payload' => $payload,
            'detail_hash' => $hash,
            'detail_status' => 'READY',
            'detail_fetched_at' => $legacy->fetched_at ?? now(),
            'last_error' => null,
        ])->save();

        return $payload;
    }

    /**
     * Local-only detail lookup. Missing RAW must be acquired from /admin/invoices/hoadon.
     */
    public function fetchDetail(Invoices $invoice, bool $force = false): array
    {
        if (($stored = $this->storedDetail($invoice)) !== null) {
            return $stored;
        }

        throw new RuntimeException(
            'Chưa có RAW GDT detail trên server. Hãy đồng bộ nguồn tại /admin/invoices/hoadon trước khi sử dụng nghiệp vụ này.'
        );
    }

    /**
     * Explicit GDT acquisition entry point. The /admin/invoices/hoadon workflow owns calls here.
     */
    public function fetchAndStoreDetail(Invoices $invoice): array
    {
        $token = Cache::get((string) config('invoices.gdt.cache_key', 'gdt_token'));
        if (! $token) {
            throw new RuntimeException('Phiên đăng nhập GDT đã hết hạn hoặc chưa được tạo.');
        }

        [$khmshdon, $khhdon] = $this->parseSymbol((string) $invoice->symbol);
        $nbmst = $this->sellerTaxCode($invoice);
        $shdon = trim((string) $invoice->invoice_number);

        if ($nbmst === '' || $khhdon === '' || $shdon === '' || $khmshdon === '') {
            throw new RuntimeException('Thiếu thông tin định danh để lấy chi tiết hóa đơn từ GDT.');
        }

        $source = InvoiceSourceRecord::query()->firstOrCreate(
            ['invoice_id' => $invoice->id, 'provider' => 'gdt'],
            ['source_version' => 'gdt-v1'],
        );
        $source->forceFill([
            'detail_status' => 'FETCHING',
            'last_error' => null,
        ])->save();

        try {
            $response = Http::withOptions([
                'verify' => (bool) config('invoices.gdt.verify_ssl', true),
            ])->timeout((int) config('invoices.gdt.timeout', 15))
                ->withToken($token)
                ->acceptJson()
                ->get(rtrim((string) config('invoices.gdt.base_url'), '/').'/query/invoices/detail', [
                    'nbmst' => $nbmst,
                    'khhdon' => $khhdon,
                    'shdon' => $shdon,
                    'khmshdon' => $khmshdon,
                ]);

            if ($response->status() === 401 || $response->status() === 403) {
                Cache::forget((string) config('invoices.gdt.cache_key', 'gdt_token'));
                throw new RuntimeException('Phiên đăng nhập GDT đã hết hạn.');
            }

            if (! $response->successful()) {
                throw new RuntimeException("GDT trả lỗi HTTP {$response->status()} khi lấy chi tiết hóa đơn.");
            }

            $data = $response->json();
            if (! is_array($data) || $data === []) {
                throw new RuntimeException('GDT không trả dữ liệu chi tiết hóa đơn.');
            }

            $source->forceFill([
                'detail_payload' => $data,
                'detail_hash' => $this->payloadHash($data),
                'detail_status' => 'READY',
                'detail_fetched_at' => now(),
                'last_error' => null,
            ])->save();

            return $data;
        } catch (ConnectionException $exception) {
            $this->markAcquisitionError($source, 'Không thể kết nối GDT để lấy chi tiết hóa đơn.');
            throw new RuntimeException('Không thể kết nối GDT để lấy chi tiết hóa đơn.', previous: $exception);
        } catch (Throwable $exception) {
            $this->markAcquisitionError($source, $exception->getMessage());
            throw $exception;
        }
    }

    private function markAcquisitionError(InvoiceSourceRecord $source, string $message): void
    {
        $source->forceFill([
            'detail_status' => 'ERROR',
            'last_error' => mb_substr($message, 0, 4000),
        ])->save();
    }

    private function payloadHash(array $payload): string
    {
        return hash('sha256', json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ));
    }

    private function parseSymbol(string $symbol): array
    {
        $symbol = trim($symbol);
        if ($symbol === '') {
            return ['', ''];
        }

        if (str_contains($symbol, '/')) {
            [$template, $series] = array_pad(explode('/', $symbol, 2), 2, '');

            return [trim($template), trim($series)];
        }

        return ['1', $symbol];
    }

    private function sellerTaxCode(Invoices $invoice): string
    {
        if ($invoice->invoice_type === 'purchase') {
            return trim((string) $invoice->tax_code);
        }

        return trim((string) config('invoices.gdt.username'));
    }
}
