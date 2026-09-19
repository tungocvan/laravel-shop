<?php

namespace Modules\Pharma\Jobs;

use App\Dossiers\Models\Dossier;
use App\Dossiers\Services\DossierStorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Modules\Pharma\Models\MedicineProfile;
use Throwable;

class DeleteHsspDossier implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    /** @var list<int> */
    public array $backoff = [30, 120, 300];

    public function __construct(
        public readonly int $profileId,
        public readonly int $dossierId,
    ) {
        $this->onQueue('pharma');
    }

    public function handle(DossierStorageService $storage): void
    {
        $profile = MedicineProfile::query()->find($this->profileId);
        $dossier = Dossier::query()->with('attachments')->find($this->dossierId);

        if (! $profile && ! $dossier) {
            return;
        }

        if ($dossier) {
            foreach ($dossier->attachments as $attachment) {
                $storage->deleteAttachmentStorage($attachment);
            }
        }

        DB::transaction(function () use ($profile, $dossier): void {
            $dossier?->delete();
            $profile?->delete();
        });
    }

    public function failed(?Throwable $exception): void
    {
        Dossier::query()->whereKey($this->dossierId)->update([
            'status' => 'delete_failed',
        ]);
    }
}
