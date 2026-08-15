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
use RuntimeException;
use Throwable;

class ProcessGdtInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(
        public string $start,
        public string $end,
        public bool $vatIn = false,
        public ?string $syncId = null,
    ) {}

    public function handle(GdtInvoiceService $service): void
    {
        $this->updateStatus('processing', 'Worker bắt đầu xử lý.');

        Log::info('[GDT JOB] Bắt đầu xử lý hóa đơn.', [
            'sync_id' => $this->syncId,
            'start' => $this->start,
            'end' => $this->end,
            'type' => $this->vatIn ? 'purchase' : 'sold',
        ]);

        $file = $service->processRange(
            $this->start,
            $this->end,
            fn (string $message) => $this->appendLog($message),
            $this->vatIn
        );

        if (! is_string($file) || ! is_file($file) || ! is_readable($file)) {
            throw new RuntimeException('Đồng bộ kết thúc nhưng không tạo được file Excel trên server.');
        }

        $this->updateStatus('completed', 'Đồng bộ hoàn tất và file Excel đã được tạo.', [
            'file' => basename($file),
            'direction' => $this->vatIn ? 'vat_in' : 'vat_out',
            'finished_at' => now()->toIso8601String(),
        ]);

        Log::info('[GDT JOB] Hoàn tất xử lý hóa đơn.', [
            'sync_id' => $this->syncId,
            'file' => $file,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        $this->updateStatus('failed', 'Đồng bộ thất bại: '.$exception->getMessage(), [
            'finished_at' => now()->toIso8601String(),
        ]);

        Log::error('[GDT JOB] Xử lý hóa đơn thất bại.', [
            'sync_id' => $this->syncId,
            'error' => $exception->getMessage(),
        ]);
    }

    private function statusKey(): ?string
    {
        return $this->syncId ? 'invoices:gdt-sync:'.$this->syncId : null;
    }

    private function appendLog(string $message): void
    {
        $key = $this->statusKey();
        if (! $key) {
            return;
        }

        $status = Cache::get($key, []);
        $status['logs'] ??= [];
        $status['logs'][] = '['.now()->format('H:i:s').'] '.$message;
        Cache::put($key, $status, now()->addHours(24));
    }

    private function updateStatus(string $state, string $message, array $extra = []): void
    {
        $key = $this->statusKey();
        if (! $key) {
            return;
        }

        $status = Cache::get($key, []);
        $status['state'] = $state;
        $status['message'] = $message;
        $status['logs'] ??= [];
        $status['logs'][] = '['.now()->format('H:i:s').'] '.$message;
        $status = array_merge($status, $extra);

        Cache::put($key, $status, now()->addHours(24));
    }
}
