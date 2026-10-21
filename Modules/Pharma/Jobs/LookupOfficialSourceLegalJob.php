<?php

namespace Modules\Pharma\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Partner\Services\BusinessRegistryLookupGateway;
use Modules\Pharma\Models\OfficialSourceLegalLookup;
use Throwable;

class LookupOfficialSourceLegalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 90;

    public function __construct(public readonly int $lookupId) {}

    public function handle(BusinessRegistryLookupGateway $service): void
    {
        $lookup = OfficialSourceLegalLookup::query()->find($this->lookupId);
        if (! $lookup || $lookup->status !== 'queued') {
            return;
        }

        $lookup->update(['status' => 'running', 'error_message' => null]);

        try {
            $candidates = array_slice($service->search($lookup->query_name), 0, 10);
            $lookup->update([
                'status' => $candidates === [] ? 'empty' : 'ready',
                'candidates' => array_merge(array_values(array_filter($lookup->candidates ?? [], static fn (array $item): bool => ($item['source'] ?? '') === 'excel_import')), $candidates),
                'candidate_count' => count($candidates),
                'completed_at' => now(),
            ]);
        } catch (Throwable $e) {
            $lookup->update([
                'status' => 'failed',
                'error_message' => mb_substr($e->getMessage(), 0, 1000),
                'completed_at' => now(),
            ]);
        }
    }

    public function failed(Throwable $e): void
    {
        OfficialSourceLegalLookup::query()->whereKey($this->lookupId)->update([
            'status' => 'failed',
            'error_message' => mb_substr($e->getMessage(), 0, 1000),
            'completed_at' => now(),
        ]);
    }
}
