<?php

namespace Modules\Pharma\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Pharma\Models\OfficialSourceSyncBatch;
use Modules\Pharma\Services\OfficialFacilityImport\OfficialSourceMirrorService;
use Throwable;

class PersistOfficialSourceSnapshotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $batchId,
        public readonly array $facilities,
    ) {
    }

    public function handle(OfficialSourceMirrorService $service): void
    {
        $batch = OfficialSourceSyncBatch::query()->findOrFail($this->batchId);
        $service->persist($batch, $this->facilities);
    }

    public function failed(Throwable $exception): void
    {
        OfficialSourceSyncBatch::query()->whereKey($this->batchId)->update([
            'status' => 'FAILED',
            'completed_at' => now(),
            'error_message' => $exception->getMessage(),
        ]);
    }
}
