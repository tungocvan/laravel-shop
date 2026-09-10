<?php

namespace Modules\Invoices\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Invoices\Services\GdtInvoiceService;
use Modules\Invoices\Services\InvoiceSourceCoverageService;
use Throwable;

class RecoverMissingGdtDetailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 900;

    public function __construct(
        public string $start,
        public string $end,
        public bool $vatIn = false,
        public ?string $syncId = null,
        public int $round = 1,
    ) {}

    public function handle(GdtInvoiceService $service, InvoiceSourceCoverageService $coverage): void
    {
        $before = $coverage->coverage($this->start, $this->end, $this->vatIn);
        $missingBefore = max(0, (int) $before['total'] - (int) $before['detail_ready']);

        if ($missingBefore === 0) {
            $this->complete($before);
            return;
        }

        $this->appendLog(sprintf(
            '[RAW] Auto recovery vòng %d: còn %d detail; chỉ xử lý phần chưa READY.',
            $this->round,
            $missingBefore,
        ));

        $stats = $service->recoverMissingDetailsFromLocalRange(
            $this->start,
            $this->end,
            fn (string $message) => $this->appendLog($message),
            $this->vatIn,
        );

        $after = $coverage->coverage($this->start, $this->end, $this->vatIn);
        $missingAfter = max(0, (int) $after['total'] - (int) $after['detail_ready']);
        $this->appendLog(sprintf(
            '[RAW] Auto recovery vòng %d: tải mới %d · đã có %d · lỗi %d · còn thiếu %d.',
            $this->round,
            $stats['fetched'],
            $stats['reused'],
            $stats['failed'],
            $missingAfter,
        ));

        if ($missingAfter === 0) {
            $this->complete($after);
            return;
        }

        if (! Cache::has((string) config('invoices.gdt.cache_key', 'gdt_token'))) {
            $this->updateStatus('partial', 'Auto recovery tạm dừng vì phiên đăng nhập GDT đã hết hạn.', [
                'missing_detail' => $missingAfter,
                'auto_recovery_pending' => false,
            ]);
            return;
        }

        $maxRounds = max(1, (int) config('invoices.gdt.auto_recovery_max_rounds', 6));
        if ($this->round >= $maxRounds) {
            $this->updateStatus('partial', sprintf(
                'Auto recovery đã chạy %d vòng; còn thiếu %d detail. Có thể đồng bộ lại sau để tiếp tục recovery phần còn thiếu.',
                $this->round,
                $missingAfter,
            ), [
                'missing_detail' => $missingAfter,
                'auto_recovery_pending' => false,
            ]);
            return;
        }

        $delays = array_values((array) config('invoices.gdt.auto_recovery_backoff_seconds', [60, 180, 300, 600, 900]));
        $delay = max(1, (int) ($delays[$this->round - 1] ?? end($delays) ?: 300));

        $this->updateStatus('recovering', sprintf(
            'Còn thiếu %d detail. Hệ thống sẽ tự recovery vòng %d sau %d giây.',
            $missingAfter,
            $this->round + 1,
            $delay,
        ), [
            'missing_detail' => $missingAfter,
            'auto_recovery_pending' => true,
            'auto_recovery_round' => $this->round + 1,
        ]);

        self::dispatch($this->start, $this->end, $this->vatIn, $this->syncId, $this->round + 1)
            ->delay(now()->addSeconds($delay));
    }

    public function failed(Throwable $exception): void
    {
        Log::warning('[GDT AUTO RECOVERY] Job thất bại.', [
            'sync_id' => $this->syncId,
            'round' => $this->round,
            'error' => $exception->getMessage(),
        ]);
        $this->updateStatus('partial', 'Auto recovery gặp lỗi: '.$exception->getMessage(), [
            'auto_recovery_pending' => false,
        ]);
    }

    private function complete(array $coverage): void
    {
        $this->updateStatus('completed', sprintf(
            'Auto recovery hoàn tất: RAW detail %d/%d.',
            $coverage['detail_ready'],
            $coverage['total'],
        ), [
            'missing_detail' => 0,
            'auto_recovery_pending' => false,
            'finished_at' => now()->toIso8601String(),
        ]);
    }

    private function statusKey(): ?string
    {
        return $this->syncId ? 'invoices:gdt-sync:'.$this->syncId : null;
    }

    private function appendLog(string $message): void
    {
        $key = $this->statusKey();
        if (! $key) return;
        $status = Cache::get($key, []);
        $status['logs'] ??= [];
        $status['logs'][] = '['.now()->format('H:i:s').'] '.$message;
        Cache::put($key, $status, now()->addHours(24));
    }

    private function updateStatus(string $state, string $message, array $extra = []): void
    {
        $key = $this->statusKey();
        if (! $key) return;
        $status = Cache::get($key, []);
        $status['state'] = $state;
        $status['message'] = $message;
        $status['logs'] ??= [];
        $status['logs'][] = '['.now()->format('H:i:s').'] '.$message;
        $status = array_merge($status, $extra);
        Cache::put($key, $status, now()->addHours(24));
    }
}
