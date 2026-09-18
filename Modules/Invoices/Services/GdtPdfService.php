<?php

namespace Modules\Invoices\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Invoices\Models\InvoiceInventorySnapshot;
use Modules\Invoices\Models\Invoices;
use Modules\Invoices\Models\InvoiceSourceRecord;
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
     * 429 is retried conservatively using Retry-After when available; other failures remain fail-closed.
     *
     * @param  null|callable(int, int, int): void  $onRateLimitRetry
     */
    public function fetchAndStoreDetail(Invoices $invoice, ?callable $onRateLimitRetry = null): array
    {
        $tokenKey = (string) config('invoices.gdt.cache_key', 'gdt_token');
        $token = Cache::get($tokenKey);
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
        $source->forceFill(['detail_status' => 'FETCHING', 'last_error' => null])->save();

        $attempts = max(1, min(6, (int) config('invoices.gdt.detail_retry_attempts', 4)));
        $backoffs = array_values((array) config('invoices.gdt.detail_retry_backoff_seconds', [5, 10, 20, 40]));

        try {
            for ($attempt = 1; $attempt <= $attempts; $attempt++) {
                try {
                    $origin = $this->frontendOrigin();
                    $response = Http::withOptions([
                        'verify' => (bool) config('invoices.gdt.verify_ssl', true),
                    ])->timeout((int) config('invoices.gdt.timeout', 15))
                        ->withToken($token)
                        ->withHeaders([
                            'Accept' => 'application/json, text/plain, */*',
                            'Origin' => $origin,
                            'Referer' => $origin.'/',
                            'Action' => '',
                            'End-Point' => '/',
                            'request-id' => (string) Str::uuid(),
                        ])
                        ->get(rtrim((string) config('invoices.gdt.base_url'), '/').'/query/invoices/detail', [
                            'nbmst' => $nbmst,
                            'khhdon' => $khhdon,
                            'shdon' => $shdon,
                            'khmshdon' => $khmshdon,
                        ]);
                } catch (ConnectionException $exception) {
                    if ($attempt < $attempts) {
                        sleep(max(1, (int) ($backoffs[$attempt - 1] ?? 5)));

                        continue;
                    }

                    throw new RuntimeException('Không thể kết nối GDT để lấy chi tiết hóa đơn.', previous: $exception);
                }

                if (in_array($response->status(), [401, 403], true)) {
                    $this->logRejectedDetail($response, $invoice, $attempt);
                    if ($response->status() === 401) {
                        Cache::forget($tokenKey);
                    }

                    throw new RuntimeException(
                        $response->status() === 401
                            ? 'Phiên đăng nhập GDT đã hết hạn.'
                            : 'GDT từ chối yêu cầu lấy chi tiết hóa đơn (HTTP 403).'
                    );
                }

                if ($response->status() === 429 && $attempt < $attempts) {
                    $retryAfter = filter_var($response->header('Retry-After'), FILTER_VALIDATE_INT);
                    $delay = $retryAfter !== false
                        ? max(1, min((int) $retryAfter, 120))
                        : max(1, (int) ($backoffs[$attempt - 1] ?? 5));

                    if ($onRateLimitRetry !== null) {
                        $onRateLimitRetry($attempt, $attempts, $delay);
                    }

                    sleep($delay);

                    continue;
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
            }

            throw new RuntimeException('Không thể hoàn tất lấy chi tiết hóa đơn từ GDT.');
        } catch (Throwable $exception) {
            $this->markAcquisitionError($source, $exception->getMessage());
            throw $exception;
        }
    }

    private function logRejectedDetail($response, Invoices $invoice, int $attempt): void
    {
        $payload = $response->json();
        Log::warning('GDT invoice detail rejected.', [
            'invoice_id' => (int) $invoice->id,
            'attempt' => $attempt,
            'status' => $response->status(),
            'message' => is_array($payload) ? ($payload['message'] ?? $payload['error'] ?? null) : null,
            'response_keys' => is_array($payload) ? array_keys($payload) : [],
            'request_context' => [
                'authorization' => 'bearer-token-present',
                'request_id' => 'generated-per-detail-request',
            ],
        ]);
    }

    private function frontendOrigin(): string
    {
        $baseUrl = rtrim((string) config('invoices.gdt.base_url'), '/');

        return preg_replace('#/api$#', '', $baseUrl) ?: $baseUrl;
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
