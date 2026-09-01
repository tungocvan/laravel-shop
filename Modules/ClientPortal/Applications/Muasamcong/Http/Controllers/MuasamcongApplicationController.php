<?php

namespace Modules\ClientPortal\Applications\Muasamcong\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\ClientPortal\Applications\Muasamcong\Jobs\SyncPricingResultsJob;
use Modules\ClientPortal\Applications\Muasamcong\Models\SyncRequest;
use Modules\ClientPortal\Applications\Muasamcong\Services\ClientPricingSearchService;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Services\ClientPortalSettingsService;
use Modules\Muasamcong\Models\PricingWishlist;
use Modules\Muasamcong\Services\PricingResultSyncService;
use Throwable;

class MuasamcongApplicationController extends Controller
{
    private const SYNC_PERMISSION = 'client.muasamcong.drug-pricing.sync';
    private const WISHLIST_PERMISSION = 'client.muasamcong.wishlist.view';
    private const PER_PAGE = 20;

    public function dashboard(Request $request, ApplicationRegistry $registry, ClientPortalSettingsService $settings): View
    {
        $application = $registry->find('muasamcong');
        abort_if($application === null, 404);
        $user = $request->user('web');
        $authorizedFeatures = collect($application['features'] ?? [])->filter(function (array $feature) use ($registry, $user): bool {
            $permission = $feature['permission'] ?? null;
            return $permission === null || ($user !== null && $registry->userCan($user, $permission));
        })->values();
        $features = $settings->presentFeatures($application['key'], $authorizedFeatures);
        $applicationPresentation = $settings->applicationPresentation($application);

        return view('ClientPortal::applications.muasamcong.dashboard', compact('application', 'applicationPresentation', 'features'));
    }

    public function drugPricing(Request $request, ClientPricingSearchService $search, PricingResultSyncService $syncService, ApplicationRegistry $registry): View
    {
        $validated = $request->validate([
            'keyword' => ['nullable', 'string', 'min:2', 'max:200'],
            'medicine_name' => ['nullable', 'string', 'max:200'],
            'active_ingredient' => ['nullable', 'string', 'max:200'],
            'medicine_group' => ['nullable', 'string', 'max:200'],
            'winning_company' => ['nullable', 'string', 'max:200'],
            'sort_price' => ['nullable', 'in:asc,desc'],
            'refresh' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $keyword = trim((string) ($validated['keyword'] ?? ''));
        $filters = [
            'medicine_name' => trim((string) ($validated['medicine_name'] ?? '')),
            'active_ingredient' => trim((string) ($validated['active_ingredient'] ?? '')),
            'medicine_group' => trim((string) ($validated['medicine_group'] ?? '')),
            'winning_company' => trim((string) ($validated['winning_company'] ?? '')),
            'sort_price' => (string) ($validated['sort_price'] ?? ''),
        ];

        $result = null;
        $allItems = collect();
        $items = new LengthAwarePaginator([], 0, self::PER_PAGE, 1, ['path' => $request->url()]);
        $summary = $this->emptySummary();
        $syncedSourceIds = [];
        $wishlistSourceIds = [];
        $dataSource = '';
        $user = $request->user('web');
        $canSync = $user !== null && $registry->userCan($user, self::SYNC_PERMISSION);
        $canWishlist = $user !== null && $registry->userCan($user, self::WISHLIST_PERMISSION);

        if ($keyword !== '') {
            $searchResult = $search->search($keyword, $user?->getKey(), (bool) ($validated['refresh'] ?? false));
            $result = $searchResult['result'];
            $dataSource = $searchResult['source'];

            if ($result['success'] ?? false) {
                $allItems = collect($result['data']['items'] ?? [])->filter(fn (mixed $item): bool => is_array($item))->values();
                $filtered = $this->filterPricingItems($allItems, $filters);
                $summary = $this->priceSummary($filtered, (int) ($result['data']['total'] ?? $allItems->count()));
                $page = max(1, (int) ($validated['page'] ?? 1));
                $pageItems = $filtered->forPage($page, self::PER_PAGE)->values();
                $items = new LengthAwarePaginator($pageItems, $filtered->count(), self::PER_PAGE, $page, ['path' => $request->url(), 'query' => $request->except('refresh')]);

                try { $syncedSourceIds = $syncService->existingSourceIds($allItems->all()); } catch (Throwable) { $syncedSourceIds = []; }

                if ($canWishlist) {
                    $pageSourceIds = $pageItems->pluck('id')->filter()->map(fn ($id): string => (string) $id)->values()->all();
                    if ($pageSourceIds !== []) {
                        try {
                            $wishlistSourceIds = PricingWishlist::query()
                                ->where('user_id', $user->getKey())
                                ->whereIn('source_id', $pageSourceIds)
                                ->pluck('source_id')
                                ->map(fn ($id): string => (string) $id)
                                ->all();
                        } catch (Throwable) {
                            $wishlistSourceIds = [];
                        }
                    }
                }
            }
        }

        return view('ClientPortal::applications.muasamcong.drug-pricing', compact(
            'keyword', 'filters', 'result', 'items', 'summary', 'syncedSourceIds',
            'wishlistSourceIds', 'canSync', 'canWishlist', 'dataSource'
        ));
    }

    public function drugPricingDetail(Request $request, string $sourceId, ClientPricingSearchService $search, PricingResultSyncService $syncService, ApplicationRegistry $registry): View
    {
        $validated = $request->validate(['keyword' => ['required', 'string', 'min:2', 'max:200']]);
        $keyword = trim($validated['keyword']);
        $user = $request->user('web');
        $searchResult = $search->search($keyword, $user?->getKey());
        $result = $searchResult['result'];
        abort_unless($result['success'] ?? false, 404);

        $item = collect($result['data']['items'] ?? [])->first(fn (mixed $row): bool => is_array($row) && (string) ($row['id'] ?? '') === $sourceId);
        abort_unless(is_array($item), 404);

        $synced = false;
        try { $synced = in_array($sourceId, $syncService->existingSourceIds([$item]), true); } catch (Throwable) {}
        $canSync = $user !== null && $registry->userCan($user, self::SYNC_PERMISSION);
        $canWishlist = $user !== null && $registry->userCan($user, self::WISHLIST_PERMISSION);
        $wishlisted = false;
        if ($canWishlist) {
            try {
                $wishlisted = PricingWishlist::query()->where('user_id', $user->getKey())->where('source_id', $sourceId)->exists();
            } catch (Throwable) {}
        }

        return view('ClientPortal::applications.muasamcong.drug-pricing-detail', compact('keyword', 'item', 'synced', 'canSync', 'canWishlist', 'wishlisted'));
    }

    public function queueDrugPricingSync(Request $request): RedirectResponse
    {
        $user = $request->user('web');
        abort_if($user === null || ! $user->can(self::SYNC_PERMISSION), 403);
        $validated = $request->validate([
            'keyword' => ['required', 'string', 'min:2', 'max:200'],
            'selected_ids' => ['required', 'array', 'min:1', 'max:100'],
            'selected_ids.*' => ['required', 'uuid'],
        ]);

        $sourceIds = array_values(array_unique($validated['selected_ids']));
        $syncRequestId = (string) Str::uuid();

        SyncRequest::query()->create([
            'id' => $syncRequestId,
            'user_id' => $user->getKey(),
            'application_key' => 'muasamcong',
            'feature_key' => 'drug-pricing',
            'keyword' => trim($validated['keyword']),
            'source_ids' => $sourceIds,
            'selected_count' => count($sourceIds),
            'status' => 'queued',
        ]);

        SyncPricingResultsJob::dispatch(trim($validated['keyword']), $sourceIds, $user->getKey(), $syncRequestId);

        return back()
            ->with('status', 'Đã đưa '.count($sourceIds).' bản ghi vào hàng đợi đồng bộ. Bạn có thể tiếp tục sử dụng ứng dụng.')
            ->with('sync_request_id', $syncRequestId);
    }

    public function drugPricingSyncStatus(Request $request, string $syncRequest): JsonResponse
    {
        $user = $request->user('web');
        abort_if($user === null, 401);

        $record = SyncRequest::query()
            ->whereKey($syncRequest)
            ->where('user_id', $user->getKey())
            ->where('application_key', 'muasamcong')
            ->where('feature_key', 'drug-pricing')
            ->firstOrFail();

        return response()->json([
            'id' => $record->getKey(),
            'status' => $record->status,
            'selected' => $record->selected_count,
            'inserted' => $record->inserted_count,
            'duplicates' => $record->duplicate_count,
            'missing' => $record->missing_count,
            'error' => $record->error_message,
            'started_at' => $record->started_at?->toIso8601String(),
            'finished_at' => $record->finished_at?->toIso8601String(),
        ]);
    }

    private function filterPricingItems(Collection $items, array $filters): Collection
    {
        $filtered = $items->filter(function (array $item) use ($filters): bool {
            return $this->matches($item['tenThuoc'] ?? null, $filters['medicine_name'])
                && $this->matches($item['tenHoatChat'] ?? null, $filters['active_ingredient'])
                && $this->matches($item['nhomThuoc'] ?? $item['groupMedicine'] ?? null, $filters['medicine_group'])
                && $this->matches(implode('; ', array_map('strval', (array) ($item['winningName'] ?? []))), $filters['winning_company']);
        })->values();
        if ($filters['sort_price'] === 'asc') return $filtered->sortBy(fn (array $item): float => is_numeric($item['donGia'] ?? null) ? (float) $item['donGia'] : PHP_FLOAT_MAX)->values();
        if ($filters['sort_price'] === 'desc') return $filtered->sortByDesc(fn (array $item): float => is_numeric($item['donGia'] ?? null) ? (float) $item['donGia'] : -1)->values();
        return $filtered;
    }

    private function matches(mixed $value, string $needle): bool
    {
        if ($needle === '') return true;
        return is_scalar($value) && mb_stripos((string) $value, $needle) !== false;
    }

    private function priceSummary(Collection $items, int $sourceTotal): array
    {
        $prices = $items->pluck('donGia')->filter(fn (mixed $price): bool => is_numeric($price))->map(fn (mixed $price): float => (float) $price);
        return ['total' => $items->count(), 'source_total' => $sourceTotal, 'lowest_price' => $prices->isEmpty() ? null : $prices->min(), 'average_price' => $prices->isEmpty() ? null : $prices->avg(), 'highest_price' => $prices->isEmpty() ? null : $prices->max()];
    }

    private function emptySummary(): array
    {
        return ['total' => 0, 'source_total' => 0, 'lowest_price' => null, 'average_price' => null, 'highest_price' => null];
    }
}
