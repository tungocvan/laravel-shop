<?php

namespace Modules\ClientPortal\Applications\Muasamcong\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Muasamcong\Services\MuaSamCongService;
use Modules\Muasamcong\Services\PricingResultSyncService;
use Modules\Muasamcong\Services\PricingTbmtPaginationService;

class SyncPricingResultsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 180;

    public function __construct(public readonly string $keyword, public readonly array $sourceIds, public readonly ?int $userId)
    {
        $this->onQueue('default');
    }

    public function handle(MuaSamCongService $sourceService, PricingTbmtPaginationService $tbmtPaginationService, PricingResultSyncService $syncService): void
    {
        $result = $sourceService->searchPricing($this->keyword);
        if ($tbmtPaginationService->isTbmtKeyword($this->keyword)) {
            $result = $tbmtPaginationService->loadAll($this->keyword, $result);
        }
        if (! ($result['success'] ?? false)) {
            $this->fail((string) ($result['message'] ?? 'Không thể xác minh lại dữ liệu Mua sắm công.'));
            return;
        }
        $items = is_array($result['data']['items'] ?? null) ? $result['data']['items'] : [];
        $syncService->syncSelected($items, $this->sourceIds, $this->userId);
    }
}
