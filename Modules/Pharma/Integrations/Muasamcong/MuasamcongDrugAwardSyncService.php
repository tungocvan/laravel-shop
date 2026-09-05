<?php

namespace Modules\Pharma\Integrations\Muasamcong;

use Illuminate\Support\Facades\Schema;
use LogicException;
use Modules\Muasamcong\Models\KqlcntAwardItem;
use Modules\Pharma\Services\DrugAwardProjectionService;

class MuasamcongDrugAwardSyncService
{
    public function __construct(
        private readonly MuasamcongKqlcntAwardAdapter $adapter,
        private readonly DrugAwardProjectionService $projectionService,
    ) {}

    /**
     * @return array{processed:int,projected:int,failed:int}
     */
    public function sync(?int $afterId = null, int $chunkSize = 250): array
    {
        if (! Schema::hasTable('muasamcong_kqlcnt_award_items')) {
            throw new LogicException('Muasamcong KQLCNT canonical table is not available.');
        }

        $processed = 0;
        $projected = 0;
        $failed = 0;

        KqlcntAwardItem::query()
            ->when($afterId, fn ($query) => $query->whereKey('>', $afterId))
            ->orderBy('id')
            ->chunkById($chunkSize, function ($items) use (&$processed, &$projected, &$failed): void {
                foreach ($items as $item) {
                    $processed++;

                    try {
                        $this->projectionService->project($this->adapter->fromModel($item));
                        $projected++;
                    } catch (\Throwable $exception) {
                        report($exception);
                        $failed++;
                    }
                }
            });

        return compact('processed', 'projected', 'failed');
    }
}
