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

class SyncMedicineProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $medicineId) {}

    public function handle(PharmaProductCatalogSyncService $sync): void
    {
        $medicine = Medicine::query()->find($this->medicineId);

        if ($medicine) {
            $sync->syncMedicine($medicine);
        }
    }
}
