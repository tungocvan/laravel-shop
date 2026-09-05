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
     * @return array{processed:int,projected:int,failed:int,last_id:int|null,has_more:bool}
     */
    public function sync(?int $afterId = null, int $limit = 250): array
    {
        if (! Schema::hasTable('muasamcong_kqlcnt_award_items')) {
            throw new LogicException('Muasamcong KQLCNT canonical table is not available.');
        }

        $limit = max(1, min($limit, 1000));
        $processed = 0;
        $projected = 0;
        $failed = 0;
        $lastId = null;

        $items = KqlcntAwardItem::query()
            ->when($afterId, fn ($query) => $query->whereKey('>', $afterId))
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($items as $item) {
            $processed++;
            $lastId = (int) $item->getKey();

            try {
                $this->projectionService->project($this->adapter->fromModel($item));
                $projected++;
            } catch (\Throwable $exception) {
                report($exception);
                $failed++;
            }
        }

        $hasMore = $lastId !== null && KqlcntAwardItem::query()->whereKey('>', $lastId)->exists();

        return [
            'processed' => $processed,
            'projected' => $projected,
            'failed' => $failed,
            'last_id' => $lastId,
            'has_more' => $hasMore,
        ];
    }
}
