<?php

namespace Modules\Invoices\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Invoices\Models\InvoiceInventorySnapshot;
use Modules\Invoices\Models\Invoices;
use RuntimeException;

class GdtPdfService
{
    public function __construct(private readonly InvoiceFileService $fileService) {}

    public function downloadInvoice(Invoices $invoice, bool $force = false): string
    {
        if (! $force && $this->fileService->existsForInvoice($invoice)) {
            return $this->fileService->pdfPathForInvoice($invoice);
        }

        $detail = $this->fetchDetail($invoice, $force);
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
     * Return the persisted GDT detail without making a network request.
     */
    public function storedDetail(Invoices $invoice): ?array
    {
        $snapshot = InvoiceInventorySnapshot::query()
            ->where('invoice_id', $invoice->id)
            ->where('source', 'gdt_detail')
            ->first();

        $payload = $snapshot?->raw_payload;

        return is_array($payload) && is_array($payload['hdhhdvu'] ?? null) && $payload['hdhhdvu'] !== []
            ? $payload
            : null;
    }

    /**
     * Local-first detail lookup. GDT is called only when RAW has never been persisted
     * (or when an administrator explicitly forces a refresh).
     */
    public function fetchDetail(Invoices $invoice, bool $force = false): array
    {
        if (! $force && ($stored = $this->storedDetail($invoice)) !== null) {
            return $stored;
        }

        return $this->fetchAndStoreDetail($invoice);
    }

    /**
     * Acquire the detail from GDT and persist the immutable source payload in Invoices.
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
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Không thể kết nối GDT để lấy chi tiết hóa đơn.', previous: $exception);
        }

        if ($response->status() === 401) {
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

        $hash = hash('sha256', json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ));

        $snapshot = InvoiceInventorySnapshot::query()->firstOrCreate(
            ['invoice_id' => $invoice->id, 'source' => 'gdt_detail'],
            ['status' => 'PENDING'],
        );
        $payloadChanged = $snapshot->payload_hash !== null && $snapshot->payload_hash !== $hash;

        $snapshot->forceFill([
            'payload_hash' => $hash,
            'raw_payload' => $data,
            'status' => $payloadChanged ? 'FETCHED' : ($snapshot->status === 'NORMALIZED' ? 'NORMALIZED' : 'FETCHED'),
            'fetched_at' => now(),
            'last_error' => null,
        ])->save();

        return $data;
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
