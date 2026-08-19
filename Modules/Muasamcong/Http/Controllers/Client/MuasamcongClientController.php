<?php

namespace Modules\Muasamcong\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\ClientApplicationRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Muasamcong\Jobs\SyncClientPricingResultsJob;
use Modules\Muasamcong\Services\MuaSamCongService;
use Modules\Muasamcong\Services\PricingResultSyncService;
use Modules\Muasamcong\Services\PricingSearchSnapshotService;
use Throwable;

class MuasamcongClientController extends Controller
{
    private const SYNC_PERMISSION = 'client.muasamcong.drug-pricing.sync';

    private const PER_PAGE = 20;

    public function dashboard(Request $request, ClientApplicationRegistry $registry): View
    {
        $application = $registry->find('muasamcong');
        abort_if($application === null, 404);

        $user = $request->user('web');
        $features = collect($application['features'] ?? [])
            ->filter(function (array $feature) use ($registry, $user): bool {
                $permission = $feature['permission'] ?? null;

                return $permission === null || ($user !== null && $registry->userCan($user, $permission));
            })
            ->values();

        return view('Muasamcong::client.dashboard', compact('application', 'features'));
    }

    public function drugPricing(
        Request $request,
        MuaSamCongService $service,
        PricingSearchSnapshotService $snapshots,
        PricingResultSyncService $syncService,
        ClientApplicationRegistry $registry,
    ): View {
        $validated = $request->validate([
            'keyword' => ['nullable', 'string', 'min:2', 'max:200'],
            'medicine_name' => ['nullable', 'string', 'max:200'],
            'active_ingredient' => ['nullable', 'string', 'max:200'],
            'medicine_group' => ['nullable', 'string', 'max:200'],
            'winning_company' => ['nullable', 'string', 'max:200'],
            'sort_price' => ['nullable', 'in:asc,desc'],
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
        $dataSource = '';

        if ($keyword !== '') {
            $snapshot = null;

            try {
                $snapshot = $snapshots->find($keyword);
            } catch (Throwable) {
                // Search remains available when snapshot storage is unavailable.
            }

            if ($snapshot !== null && is_array($snapshot->result_payload)) {
                $result = $snapshot->result_payload;
                $dataSource = 'database';
            } else {
                $result = $service->searchPricing($keyword);
                $dataSource = 'api';

                if ($result['success'] ?? false) {
                    try {
                        $snapshots->store($keyword, $result, $request->user('web')?->getKey());
                    } catch (Throwable) {
                        // Do not make client search depend on snapshot persistence.
                    }
                }
            }

            if ($result['success'] ?? false) {
                $allItems = collect($result['data']['items'] ?? [])
                    ->filter(fn (mixed $item): bool => is_array($item))
                    ->values();

                $filtered = $this->filterPricingItems($allItems, $filters);
                $summary = $this->priceSummary($filtered, (int) ($result['data']['total'] ?? $allItems->count()));

                $page = max(1, (int) ($validated['page'] ?? 1));
                $items = new LengthAwarePaginator(
                    $filtered->forPage($page, self::PER_PAGE)->values(),
                    $filtered->count(),
                    self::PER_PAGE,
                    $page,
                    ['path' => $request->url(), 'query' => $request->query()]
                );

                try {
                    $syncedSourceIds = $syncService->existingSourceIds($allItems->all());
                } catch (Throwable) {
                    $syncedSourceIds = [];
                }
            }
        }

        $user = $request->user('web');
        $canSync = $user !== null && $registry->userCan($user, self::SYNC_PERMISSION);

        return view('Muasamcong::client.drug-pricing', compact(
            'keyword',
            'filters',
            'result',
            'items',
            'summary',
            'syncedSourceIds',
            'canSync',
            'dataSource'
        ));
    }

    public function drugPricingDetail(
        Request $request,
        string $sourceId,
        MuaSamCongService $service,
        PricingSearchSnapshotService $snapshots,
        PricingResultSyncService $syncService,
        ClientApplicationRegistry $registry,
    ): View {
        $validated = $request->validate([
            'keyword' => ['required', 'string', 'min:2', 'max:200'],
        ]);
        $keyword = trim($validated['keyword']);
        $result = null;

        try {
            $snapshot = $snapshots->find($keyword);
            $result = $snapshot?->result_payload;
        } catch (Throwable) {
            $result = null;
        }

        if (! is_array($result)) {
            $result = $service->searchPricing($keyword);
        }

        abort_unless($result['success'] ?? false, 404);

        $item = collect($result['data']['items'] ?? [])->first(
            fn (mixed $row): bool => is_array($row) && (string) ($row['id'] ?? '') === $sourceId
        );
        abort_unless(is_array($item), 404);

        $synced = false;
        try {
            $synced = in_array($sourceId, $syncService->existingSourceIds([$item]), true);
        } catch (Throwable) {
            $synced = false;
        }

        $user = $request->user('web');
        $canSync = $user !== null && $registry->userCan($user, self::SYNC_PERMISSION);

        return view('Muasamcong::client.drug-pricing-detail', compact('keyword', 'item', 'synced', 'canSync'));
    }

    public function queueDrugPricingSync(Request $request, ClientApplicationRegistry $registry): RedirectResponse
    {
        $user = $request->user('web');
        abort_if($user === null || ! $registry->userCan($user, self::SYNC_PERMISSION), 403);

        $validated = $request->validate([
            'keyword' => ['required', 'string', 'min:2', 'max:200'],
            'selected_ids' => ['required', 'array', 'min:1', 'max:100'],
            'selected_ids.*' => ['required', 'uuid'],
        ]);

        $sourceIds = array_values(array_unique($validated['selected_ids']));

        SyncClientPricingResultsJob::dispatch(
            trim($validated['keyword']),
            $sourceIds,
            $user->getKey(),
        );

        return back()->with('status', 'Đã đưa '.count($sourceIds).' bản ghi vào hàng đợi đồng bộ. Bạn có thể tiếp tục sử dụng ứng dụng.');
    }

    private function filterPricingItems(Collection $items, array $filters): Collection
    {
        $filtered = $items->filter(function (array $item) use ($filters): bool {
            return $this->matches($item['tenThuoc'] ?? null, $filters['medicine_name'])
                && $this->matches($item['tenHoatChat'] ?? null, $filters['active_ingredient'])
                && $this->matches($item['nhomThuoc'] ?? $item['groupMedicine'] ?? null, $filters['medicine_group'])
                && $this->matches(implode('; ', array_map('strval', (array) ($item['winningName'] ?? []))), $filters['winning_company']);
        })->values();

        if ($filters['sort_price'] === 'asc') {
            return $filtered->sortBy(fn (array $item): float => is_numeric($item['donGia'] ?? null) ? (float) $item['donGia'] : PHP_FLOAT_MAX)->values();
        }

        if ($filters['sort_price'] === 'desc') {
            return $filtered->sortByDesc(fn (array $item): float => is_numeric($item['donGia'] ?? null) ? (float) $item['donGia'] : -1)->values();
        }

        return $filtered;
    }

    private function matches(mixed $value, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }

        if (! is_scalar($value)) {
            return false;
        }

        return mb_stripos((string) $value, $needle) !== false;
    }

    private function priceSummary(Collection $items, int $sourceTotal): array
    {
        $prices = $items
            ->pluck('donGia')
            ->filter(fn (mixed $price): bool => is_numeric($price))
            ->map(fn (mixed $price): float => (float) $price);

        return [
            'total' => $items->count(),
            'source_total' => $sourceTotal,
            'lowest_price' => $prices->isEmpty() ? null : $prices->min(),
            'average_price' => $prices->isEmpty() ? null : $prices->avg(),
            'highest_price' => $prices->isEmpty() ? null : $prices->max(),
        ];
    }

    private function emptySummary(): array
    {
        return [
            'total' => 0,
            'source_total' => 0,
            'lowest_price' => null,
            'average_price' => null,
            'highest_price' => null,
        ];
    }
}
