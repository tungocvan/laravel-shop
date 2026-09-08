<?php

namespace Modules\Invoices\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Modules\Invoices\Services\GoogleDriveInvoicePdfSyncService;

class SyncInvoicePdfDriveChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;

    public int $tries = 2;

    public function __construct(
        public readonly string $batchId,
        public readonly string $direction,
        public readonly array $items,
    ) {}

    public function handle(GoogleDriveInvoicePdfSyncService $sync): void
    {
        $key = self::cacheKey($this->batchId);
        $status = Cache::get($key);
        if (! is_array($status) || in_array($status['status'] ?? null, ['completed', 'completed_with_errors', 'connection_lost'], true)) {
            return;
        }

        if (! $sync->isConnected()) {
            $status['status'] = 'connection_lost';
            $status['message'] = 'Google Drive đã mất kết nối. Batch được dừng để tránh phát sinh thêm lỗi.';
            $status['finished_at'] = now()->toISOString();
            Cache::put($key, $status, now()->addHours(6));

            return;
        }

        $status['status'] = 'processing';
        Cache::put($key, $status, now()->addHours(6));

        $success = 0;
        $existing = 0;
        $failed = 0;
        $errors = [];

        foreach ($this->items as $item) {
            try {
                $result = $this->direction === 'upload'
                    ? $sync->uploadInvoice((int) $item['invoice_id'])
                    : $sync->restoreInvoice((int) $item['invoice_id'], (string) $item['drive_id']);
                $result === 'existing' ? $existing++ : $success++;
            } catch (\Throwable $e) {
                $failed++;
                if (count($errors) < 10) {
                    $errors[] = 'HĐ #'.(int) ($item['invoice_id'] ?? 0).': '.$e->getMessage();
                }
            }
        }

        $status = Cache::get($key, $status);
        if (! is_array($status) || ($status['status'] ?? null) === 'connection_lost') {
            return;
        }

        $status['processed'] = (int) ($status['processed'] ?? 0) + count($this->items);
        $status['success'] = (int) ($status['success'] ?? 0) + $success;
        $status['existing'] = (int) ($status['existing'] ?? 0) + $existing;
        $status['failed'] = (int) ($status['failed'] ?? 0) + $failed;
        $status['completed_chunks'] = (int) ($status['completed_chunks'] ?? 0) + 1;
        $status['errors'] = array_slice(array_merge((array) ($status['errors'] ?? []), $errors), 0, 10);

        if ($status['completed_chunks'] >= (int) ($status['chunks'] ?? 0)) {
            $status['status'] = $status['failed'] > 0 ? 'completed_with_errors' : 'completed';
            $status['message'] = $this->direction === 'upload'
                ? 'Đồng bộ PDF Local lên Google Drive đã hoàn tất.'
                : 'Khôi phục PDF Google Drive về Local đã hoàn tất.';
            $status['finished_at'] = now()->toISOString();
        }

        Cache::put($key, $status, now()->addHours(6));
    }

    public static function cacheKey(string $batchId): string
    {
        return 'invoices:pdf-drive-sync:'.$batchId;
    }
}
