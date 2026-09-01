<?php

namespace Modules\ClientPortal\Applications\Muasamcong\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\ClientPortal\Applications\Muasamcong\Models\SyncRequest;
use Modules\Muasamcong\Services\MuaSamCongService;
use Modules\Muasamcong\Services\PricingResultSyncService;
use Modules\Muasamcong\Services\PricingTbmtPaginationService;
use RuntimeException;
use Throwable;

class SyncPricingResultsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 180;

    public function __construct(
        public readonly string $keyword,
        public readonly array $sourceIds,
        public readonly ?int $userId,
        public readonly string $syncRequestId,
    ) {
        $this->onQueue('default');
    }

    public function handle(MuaSamCongService $sourceService, PricingTbmtPaginationService $tbmtPaginationService, PricingResultSyncService $syncService): void
    {
        $request = SyncRequest::query()->find($this->syncRequestId);
        $request?->forceFill([
            'status' => 'processing',
            'started_at' => now(),
            'error_message' => null,
        ])->save();

        $result = $sourceService->searchPricing($this->keyword);
        if ($tbmtPaginationService->isTbmtKeyword($this->keyword)) {
            $result = $tbmtPaginationService->loadAll($this->keyword, $result);
        }

        if (!($result['success'] ?? false)) {
            throw new RuntimeException((string) ($result['message'] ?? 'Không thể xác minh lại dữ liệu Mua sắm công.'));
        }

        $items = is_array($result['data']['items'] ?? null) ? $result['data']['items'] : [];
        $summary = $syncService->syncSelected($items, $this->sourceIds, $this->userId);

        $request?->forceFill([
            'status' => 'completed',
            'inserted_count' => (int) ($summary['inserted'] ?? 0),
            'duplicate_count' => (int) ($summary['duplicates'] ?? 0),
            'missing_count' => (int) ($summary['missing'] ?? 0),
            'finished_at' => now(),
        ])->save();
    }

    public function failed(?Throwable $exception): void
    {
        SyncRequest::query()->whereKey($this->syncRequestId)->update([
            'status' => 'failed',
            'error_message' => $exception?->getMessage() ?: 'Đồng bộ thất bại.',
            'finished_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
