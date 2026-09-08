<?php

namespace Modules\Invoices\Livewire;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Component;
use Modules\Invoices\Jobs\SyncInvoicePdfDriveChunkJob;
use Modules\Invoices\Services\GoogleDriveInvoicePdfSyncService;

class InvoicePdfDriveSync extends Component
{
    public string $year = '';

    public string $month = '';

    public array $snapshot = [];

    public ?string $batchId = null;

    public array $batchStatus = [];

    public ?string $notice = null;

    public ?string $error = null;

    protected GoogleDriveInvoicePdfSyncService $sync;

    public function boot(GoogleDriveInvoicePdfSyncService $sync): void
    {
        $this->sync = $sync;
    }

    public function mount(): void
    {
        $this->year = (string) request()->query('year', now()->year);
        $this->month = (string) request()->query('month', now()->month);
        $this->normalizePeriod();
        $this->refreshSnapshot();
    }

    public function syncPeriod(string $year, string $month): void
    {
        $this->year = $year;
        $this->month = $month;
        $this->normalizePeriod();
        $this->refreshSnapshot();
    }

    public function refreshSnapshot(): void
    {
        $this->notice = null;
        $this->error = null;

        if (! $this->hasMonth()) {
            $this->snapshot = [];

            return;
        }

        try {
            $this->snapshot = $this->sync->snapshot((int) $this->year, (int) $this->month);
        } catch (\Throwable $e) {
            $this->snapshot = ['connected' => false];
            $this->error = $e->getMessage();
        }
    }

    public function queueUpload(): void
    {
        $this->authorizePermission();
        $this->refreshSnapshot();
        if ($this->error || ! $this->hasMonth()) {
            return;
        }

        $items = collect($this->snapshot['upload'] ?? [])->map(fn ($id) => ['invoice_id' => (int) $id])->all();
        $this->dispatchBatch('upload', $items);
    }

    public function queueRestore(): void
    {
        $this->authorizePermission();
        $this->refreshSnapshot();
        if ($this->error || ! $this->hasMonth()) {
            return;
        }

        $this->dispatchBatch('restore', (array) ($this->snapshot['restore'] ?? []));
    }

    public function refreshBatchStatus(): void
    {
        if (! $this->batchId) {
            return;
        }

        $status = Cache::get(SyncInvoicePdfDriveChunkJob::cacheKey($this->batchId));
        if (! is_array($status)) {
            return;
        }

        $this->batchStatus = $status;
        if (in_array($status['status'] ?? null, ['completed', 'completed_with_errors', 'connection_lost'], true)) {
            $this->refreshSnapshot();
        }
    }

    public function dismissBatch(): void
    {
        $this->batchId = null;
        $this->batchStatus = [];
        $this->refreshSnapshot();
    }

    public function render()
    {
        return view('Invoices::livewire.invoice-pdf-drive-sync');
    }

    private function dispatchBatch(string $direction, array $items): void
    {
        if (! ($this->snapshot['connected'] ?? false)) {
            $this->error = 'Google Drive chưa kết nối. Vui lòng kết nối Google Drive trước khi đồng bộ PDF.';

            return;
        }

        if ($items === []) {
            $this->notice = $direction === 'upload'
                ? 'Không còn PDF Local nào cần đồng bộ lên Google Drive trong tháng này.'
                : 'Không có PDF nào chỉ tồn tại trên Google Drive cần khôi phục về Local.';

            return;
        }

        $batchId = (string) Str::uuid();
        $chunks = array_chunk($items, 25);
        $status = [
            'status' => 'queued',
            'direction' => $direction,
            'year' => (int) $this->year,
            'month' => (int) $this->month,
            'total' => count($items),
            'chunks' => count($chunks),
            'completed_chunks' => 0,
            'processed' => 0,
            'success' => 0,
            'existing' => 0,
            'failed' => 0,
            'errors' => [],
            'message' => 'Đã đưa '.count($items).' PDF vào '.count($chunks).' queue để xử lý Google Drive.',
            'started_at' => now()->toISOString(),
            'finished_at' => null,
        ];

        Cache::put(SyncInvoicePdfDriveChunkJob::cacheKey($batchId), $status, now()->addHours(6));
        foreach ($chunks as $chunk) {
            SyncInvoicePdfDriveChunkJob::dispatch($batchId, $direction, $chunk);
        }

        $this->batchId = $batchId;
        $this->batchStatus = $status;
    }

    private function normalizePeriod(): void
    {
        if ((int) $this->year < 2000 || (int) $this->year > 2100) {
            $this->year = '';
        }
        if ((int) $this->month < 1 || (int) $this->month > 12) {
            $this->month = '';
        }
    }

    private function hasMonth(): bool
    {
        return $this->year !== '' && $this->month !== '';
    }

    private function authorizePermission(): void
    {
        abort_unless(auth('admin')->check() && auth('admin')->user()->can('invoices-download'), 403);
    }
}
