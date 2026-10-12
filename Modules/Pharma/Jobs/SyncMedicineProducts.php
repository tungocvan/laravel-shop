<?php

namespace Modules\Pharma\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Services\PharmaProductCatalogSyncService;
use Throwable;
use Illuminate\Support\Facades\Log;
use Modules\Pharma\Models\PharmaProductLink;

class SyncMedicineProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function __construct(public readonly int $medicineId) {}

    public function handle(PharmaProductCatalogSyncService $sync): void
    {
        $medicine = Medicine::query()->find($this->medicineId);

        if ($medicine) {
            $sync->syncMedicine($medicine);
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Pharma Product catalog synchronization failed', [
            'medicine_id' => $this->medicineId,
            'error' => $exception?->getMessage(),
        ]);

        PharmaProductLink::query()->where('medicine_id', $this->medicineId)
            ->where('sync_status', '!=', 'stale')
            ->update([
                'sync_status' => 'error',
                'last_error' => mb_substr($exception?->getMessage() ?? 'Queue job failed', 0, 2000),
            ]);
    }
}
