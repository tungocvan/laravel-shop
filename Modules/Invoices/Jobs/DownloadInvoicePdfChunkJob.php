<?php

namespace Modules\Invoices\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Modules\Invoices\Services\GdtApiService;
use Modules\Invoices\Services\InvoicePdfService;
use Throwable;

class DownloadInvoicePdfChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 600;

    public function __construct(
        public readonly string $batchId,
        public readonly array $invoiceIds,
    ) {}

    public function handle(InvoicePdfService $pdfService, GdtApiService $gdtApiService): void
    {
        if ($this->isStopped()) {
            return;
        }

        if (! $gdtApiService->hasToken()) {
            $this->markAuthExpired();

            return;
        }

        $this->mutateStatus(function (array $status): array {
            if (($status['status'] ?? null) === 'queued') {
                $status['status'] = 'processing';
            }

            return $status;
        });

        $result = $pdfService->downloadSelected($this->invoiceIds);

        if (! $gdtApiService->hasToken()) {
            $this->markAuthExpired($result);

            return;
        }

        $this->mutateStatus(function (array $status) use ($result): array {
            if (($status['status'] ?? null) === 'auth_expired') {
                return $status;
            }

            $status['completed_chunks'] = (int) ($status['completed_chunks'] ?? 0) + 1;
            $status['processed'] = min(
                (int) ($status['total'] ?? 0),
                (int) ($status['processed'] ?? 0) + count($this->invoiceIds),
            );
            $status['downloaded'] = (int) ($status['downloaded'] ?? 0) + (int) ($result['downloaded'] ?? 0);
            $status['existing'] = (int) ($status['existing'] ?? 0) + (int) ($result['existing'] ?? 0);
            $status['failed'] = (int) ($status['failed'] ?? 0) + (int) ($result['failed'] ?? 0);
            $status['errors'] = array_slice(array_values(array_merge(
                (array) ($status['errors'] ?? []),
                (array) ($result['errors'] ?? []),
            )), 0, 20);

            if ($status['completed_chunks'] >= (int) ($status['chunks'] ?? 0)) {
                $status['status'] = $status['failed'] > 0 ? 'completed_with_errors' : 'completed';
                $status['finished_at'] = now()->toISOString();
            }

            return $status;
        });
    }

    public function failed(Throwable $exception): void
    {
        $this->mutateStatus(function (array $status) use ($exception): array {
            if (($status['status'] ?? null) === 'auth_expired') {
                return $status;
            }

            $status['completed_chunks'] = (int) ($status['completed_chunks'] ?? 0) + 1;
            $status['processed'] = min(
                (int) ($status['total'] ?? 0),
                (int) ($status['processed'] ?? 0) + count($this->invoiceIds),
            );
            $status['failed'] = (int) ($status['failed'] ?? 0) + count($this->invoiceIds);
            $status['errors'] = array_slice(array_values(array_merge(
                (array) ($status['errors'] ?? []),
                ['Queue: '.$exception->getMessage()],
            )), 0, 20);

            if ($status['completed_chunks'] >= (int) ($status['chunks'] ?? 0)) {
                $status['status'] = 'completed_with_errors';
                $status['finished_at'] = now()->toISOString();
            }

            return $status;
        });
    }

    private function markAuthExpired(array $result = []): void
    {
        $this->mutateStatus(function (array $status) use ($result): array {
            $status['status'] = 'auth_expired';
            $status['downloaded'] = (int) ($status['downloaded'] ?? 0) + (int) ($result['downloaded'] ?? 0);
            $status['existing'] = (int) ($status['existing'] ?? 0) + (int) ($result['existing'] ?? 0);
            $status['failed'] = (int) ($status['failed'] ?? 0) + (int) ($result['failed'] ?? 0);
            $status['message'] = 'Phiên đăng nhập GDT đã hết hạn. Batch PDF đã dừng để tránh phát sinh thêm lỗi. Vui lòng kết nối lại GDT rồi thử lại.';
            $status['errors'] = array_slice(array_values(array_merge(
                (array) ($status['errors'] ?? []),
                (array) ($result['errors'] ?? []),
                [$status['message']],
            )), 0, 20);
            $status['finished_at'] = now()->toISOString();

            return $status;
        });
    }

    private function isStopped(): bool
    {
        $status = Cache::get(self::cacheKey($this->batchId));

        return is_array($status) && in_array($status['status'] ?? null, ['auth_expired', 'completed', 'completed_with_errors'], true);
    }

    private function mutateStatus(callable $callback): void
    {
        $cacheKey = self::cacheKey($this->batchId);

        Cache::lock($cacheKey.':lock', 10)->block(3, function () use ($cacheKey, $callback): void {
            $status = Cache::get($cacheKey);

            if (! is_array($status)) {
                return;
            }

            Cache::put($cacheKey, $callback($status), now()->addHours(6));
        });
    }

    public static function cacheKey(string $batchId): string
    {
        return 'invoices:monthly-pdf-batch:'.$batchId;
    }
}
