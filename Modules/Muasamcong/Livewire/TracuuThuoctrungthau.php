<?php

namespace Modules\Muasamcong\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Modules\Muasamcong\Services\MuaSamCongService;
use Modules\Muasamcong\Services\PricingResultSyncService;
use Modules\Muasamcong\Services\PricingSearchSnapshotService;
use Modules\Muasamcong\Services\PricingTbmtPaginationService;
use Modules\Muasamcong\Services\PricingWishlistService;

class TracuuThuoctrungthau extends Component
{
    private const SYNC_PERMISSION = 'muasamcong.pricing.sync';

    private const WISHLIST_PERMISSION = 'muasamcong.pricing.wishlist';

    private const RESULTS_PER_PAGE = 20;

    public string $keyword = '';

    public string $medicineNameFilter = '';

    public string $activeIngredientFilter = '';

    public string $medicineGroupFilter = '';

    public string $winningCompanyFilter = '';

    public array $results = [];

    public array $selectedSourceIds = [];

    public array $syncedSourceIds = [];

    public array $wishlistSourceIds = [];

    public array $wishlistItems = [];

    public array $recentSearches = [];

    public ?array $detailItem = null;

    public bool $loading = false;

    public string $error = '';

    public string $syncStatus = '';

    public string $syncMessage = '';

    public string $wishlistMessage = '';

    public string $searchDataSource = '';

    public ?string $searchSnapshotAt = null;

    public int $resultPage = 1;

    public int $sourceTotal = 0;

    public bool $sourcePartial = false;

    public function mount(
        PricingWishlistService $wishlistService,
        PricingSearchSnapshotService $snapshotService
    ): void {
        $this->refreshWishlist($wishlistService);
        $this->refreshRecentSearches($snapshotService);
    }

    public function search(
        MuaSamCongService $service,
        PricingTbmtPaginationService $tbmtPaginationService,
        PricingResultSyncService $syncService,
        PricingWishlistService $wishlistService,
        PricingSearchSnapshotService $snapshotService
    ): void {
        $this->runSearch(
            false,
            $service,
            $tbmtPaginationService,
            $syncService,
            $wishlistService,
            $snapshotService
        );
    }

    public function refreshSearch(
        MuaSamCongService $service,
        PricingTbmtPaginationService $tbmtPaginationService,
        PricingResultSyncService $syncService,
        PricingWishlistService $wishlistService,
        PricingSearchSnapshotService $snapshotService
    ): void {
        $this->runSearch(
            true,
            $service,
            $tbmtPaginationService,
            $syncService,
            $wishlistService,
            $snapshotService
        );
    }

    public function searchRecent(
        string $keyword,
        MuaSamCongService $service,
        PricingTbmtPaginationService $tbmtPaginationService,
        PricingResultSyncService $syncService,
        PricingWishlistService $wishlistService,
        PricingSearchSnapshotService $snapshotService
    ): void {
        $this->keyword = trim($keyword);
        $this->runSearch(
            false,
            $service,
            $tbmtPaginationService,
            $syncService,
            $wishlistService,
            $snapshotService
        );
    }

    public function searchWishlist(
        string $keyword,
        MuaSamCongService $service,
        PricingTbmtPaginationService $tbmtPaginationService,
        PricingResultSyncService $syncService,
        PricingWishlistService $wishlistService,
        PricingSearchSnapshotService $snapshotService
    ): void {
        $this->keyword = trim($keyword);
        $this->runSearch(
            false,
            $service,
            $tbmtPaginationService,
            $syncService,
            $wishlistService,
            $snapshotService
        );
    }

    public function updatedMedicineNameFilter(): void
    {
        $this->resultPage = 1;
    }

    public function updatedActiveIngredientFilter(): void
    {
        $this->resultPage = 1;
    }

    public function updatedMedicineGroupFilter(): void
    {
        $this->resultPage = 1;
    }

    public function updatedWinningCompanyFilter(): void
    {
        $this->resultPage = 1;
    }

    public function clearResultFilters(): void
    {
        $this->resetResultFilters();
        $this->resultPage = 1;
    }

    public function previousResultPage(): void
    {
        $this->resultPage = max(1, $this->resultPage - 1);
    }

    public function nextResultPage(): void
    {
        $this->resultPage = min($this->resultPageCount(), $this->resultPage + 1);
    }

    public function goToResultPage(int $page): void
    {
        $this->resultPage = max(1, min($this->resultPageCount(), $page));
    }

    public function toggleWishlist(string $sourceId, PricingWishlistService $wishlistService): void
    {
        $this->authorizeWishlist();

        $item = collect($this->results)->first(
            fn (mixed $result): bool => is_array($result) && ($result['id'] ?? null) === $sourceId
        );

        if (! is_array($item) && is_array($this->detailItem) && ($this->detailItem['id'] ?? null) === $sourceId) {
            $item = $this->detailItem;
        }

        if (! is_array($item)) {
            $this->wishlistMessage = 'Không tìm thấy bản ghi để cập nhật Wishlist.';

            return;
        }

        $added = $wishlistService->toggle(
            $this->adminUserId(),
            $this->keyword !== '' ? $this->keyword : (string) ($item['tenThuoc'] ?? ''),
            $item
        );

        $this->wishlistSourceIds = $this->currentWishlistSourceIds($wishlistService);
        $this->refreshWishlist($wishlistService);
        $this->wishlistMessage = $added ? 'Đã thêm vào Wishlist.' : 'Đã bỏ khỏi Wishlist.';
    }

    public function selectAllUnsynced(): void
    {
        $synced = array_fill_keys($this->syncedSourceIds, true);

        $this->selectedSourceIds = collect($this->filteredResults())
            ->pluck('id')
            ->filter(fn (mixed $id): bool => is_string($id) && $id !== '' && ! isset($synced[$id]))
            ->unique()
            ->values()
            ->all();
    }

    public function clearSelection(): void
    {
        $this->selectedSourceIds = [];
    }

    public function openDetail(string $sourceId): void
    {
        $this->detailItem = collect($this->results)->first(
            fn (mixed $item): bool => is_array($item) && ($item['id'] ?? null) === $sourceId
        );
    }

    public function closeDetail(): void
    {
        $this->detailItem = null;
    }

    public function syncSelected(
        MuaSamCongService $sourceService,
        PricingTbmtPaginationService $tbmtPaginationService,
        PricingResultSyncService $syncService
    ): void {
        $this->authorizeSync();
        $this->syncStatus = $this->syncMessage = '';

        if ($this->selectedSourceIds === []) {
            $this->syncStatus = 'error';
            $this->syncMessage = 'Vui lòng chọn ít nhất một bản ghi chưa đồng bộ.';

            return;
        }

        $validated = $this->validate(['keyword' => ['required', 'string', 'min:2', 'max:200']]);
        $freshResult = $sourceService->searchPricing($validated['keyword']);

        if ($tbmtPaginationService->isTbmtKeyword($validated['keyword'])) {
            $freshResult = $tbmtPaginationService->loadAll($validated['keyword'], $freshResult);
        }

        if (! ($freshResult['success'] ?? false)) {
            $this->syncStatus = 'error';
            $this->syncMessage = 'Không thể xác minh lại dữ liệu nguồn trước khi đồng bộ. Vui lòng thử lại.';

            return;
        }

        $freshItems = is_array($freshResult['data']['items'] ?? null) ? $freshResult['data']['items'] : [];
        $report = $syncService->syncSelected($freshItems, $this->selectedSourceIds, $this->adminUserId());

        $this->results = $freshItems;
        $this->sourceTotal = max(count($freshItems), (int) ($freshResult['data']['total'] ?? count($freshItems)));
        $this->sourcePartial = (bool) ($freshResult['data']['partial'] ?? false)
            || (bool) ($freshResult['data']['capped'] ?? false);
        $this->syncedSourceIds = $syncService->existingSourceIds($this->results);
        $this->selectedSourceIds = [];

        $inserted = (int) ($report['inserted'] ?? 0);
        $duplicates = (int) ($report['duplicates'] ?? 0);
        $missing = (int) ($report['missing'] ?? 0);

        if ($inserted > 0) {
            $this->syncStatus = 'success';
            $this->syncMessage = "Đã đồng bộ {$inserted} bản ghi mới từ dữ liệu nguồn đã xác minh.";

            if ($duplicates > 0) {
                $this->syncMessage .= " Bỏ qua {$duplicates} bản ghi đã tồn tại.";
            }

            if ($missing > 0) {
                $this->syncMessage .= " Có {$missing} bản ghi không còn trong kết quả nguồn hiện tại.";
            }

            return;
        }

        $this->syncStatus = 'warning';
        $this->syncMessage = $duplicates > 0
            ? 'Các bản ghi đã chọn đều đã tồn tại, không có dữ liệu mới được đồng bộ.'
            : 'Không có bản ghi hợp lệ để đồng bộ.';
    }

    public function render(): View
    {
        $filteredResults = $this->filteredResults();
        $pageCount = max(1, (int) ceil(count($filteredResults) / self::RESULTS_PER_PAGE));
        $this->resultPage = max(1, min($this->resultPage, $pageCount));
        $offset = ($this->resultPage - 1) * self::RESULTS_PER_PAGE;

        return view('Muasamcong::livewire.tracuu-thuoctrungthau', [
            'displayResults' => array_slice($filteredResults, $offset, self::RESULTS_PER_PAGE),
            'filteredResultCount' => count($filteredResults),
            'resultPageCount' => $pageCount,
            'resultOffset' => $offset,
        ]);
    }

    private function runSearch(
        bool $forceRefresh,
        MuaSamCongService $service,
        PricingTbmtPaginationService $tbmtPaginationService,
        PricingResultSyncService $syncService,
        PricingWishlistService $wishlistService,
        PricingSearchSnapshotService $snapshotService
    ): void {
        $validated = $this->validate(
            ['keyword' => ['required', 'string', 'min:2', 'max:200']],
            ['keyword.required' => 'Vui lòng nhập từ khóa.']
        );

        $this->prepareSearchState();

        $snapshot = $forceRefresh ? null : $snapshotService->find($validated['keyword']);

        if ($snapshot !== null && is_array($snapshot->result_payload)) {
            $result = $snapshot->result_payload;
            $this->searchDataSource = 'database';
            $this->searchSnapshotAt = $snapshot->searched_at?->toIso8601String();
        } else {
            $result = $service->searchPricing($validated['keyword']);

            if ($tbmtPaginationService->isTbmtKeyword($validated['keyword'])) {
                $result = $tbmtPaginationService->loadAll($validated['keyword'], $result);
            }

            if ($result['success'] ?? false) {
                $snapshot = $snapshotService->store($validated['keyword'], $result, $this->adminUserIdOrNull());
                $this->searchDataSource = 'api';
                $this->searchSnapshotAt = $snapshot->searched_at?->toIso8601String();
            }
        }

        if (! ($result['success'] ?? false)) {
            $this->error = $result['message'] ?? 'Không thể tra cứu thuốc trúng thầu.';
            $this->loading = false;

            return;
        }

        $this->applySearchResult($result, $syncService, $wishlistService);
        $this->refreshRecentSearches($snapshotService);
        $this->loading = false;
    }

    private function prepareSearchState(): void
    {
        $this->loading = true;
        $this->error = $this->syncStatus = $this->syncMessage = $this->wishlistMessage = '';
        $this->results = $this->selectedSourceIds = $this->syncedSourceIds = [];
        $this->detailItem = null;
        $this->resetResultFilters();
        $this->resultPage = 1;
        $this->sourceTotal = 0;
        $this->sourcePartial = false;
        $this->searchDataSource = '';
        $this->searchSnapshotAt = null;
    }

    private function applySearchResult(
        array $result,
        PricingResultSyncService $syncService,
        PricingWishlistService $wishlistService
    ): void {
        $this->results = is_array($result['data']['items'] ?? null) ? $result['data']['items'] : [];
        $this->sourceTotal = max(count($this->results), (int) ($result['data']['total'] ?? count($this->results)));
        $this->sourcePartial = (bool) ($result['data']['partial'] ?? false)
            || (bool) ($result['data']['capped'] ?? false);
        $this->syncedSourceIds = $syncService->existingSourceIds($this->results);
        $this->wishlistSourceIds = $this->currentWishlistSourceIds($wishlistService);
    }

    private function resultPageCount(): int
    {
        return max(1, (int) ceil(count($this->filteredResults()) / self::RESULTS_PER_PAGE));
    }

    private function filteredResults(): array
    {
        $medicineNeedle = $this->normalizeFilter($this->medicineNameFilter);
        $ingredientNeedle = $this->normalizeFilter($this->activeIngredientFilter);
        $groupNeedle = $this->normalizeFilter($this->medicineGroupFilter);
        $winnerNeedle = $this->normalizeFilter($this->winningCompanyFilter);

        if ($medicineNeedle === '' && $ingredientNeedle === '' && $groupNeedle === '' && $winnerNeedle === '') {
            return $this->results;
        }

        return collect($this->results)
            ->filter(function (mixed $item) use ($medicineNeedle, $ingredientNeedle, $groupNeedle, $winnerNeedle): bool {
                if (! is_array($item)) {
                    return false;
                }

                if ($medicineNeedle !== '' && ! $this->containsFilter($item['tenThuoc'] ?? null, $medicineNeedle)) {
                    return false;
                }

                if ($ingredientNeedle !== '' && ! $this->containsFilter($item['tenHoatChat'] ?? null, $ingredientNeedle)) {
                    return false;
                }

                if ($groupNeedle !== ''
                    && ! $this->containsFilter($item['nhomThuoc'] ?? $item['groupMedicine'] ?? null, $groupNeedle)) {
                    return false;
                }

                if ($winnerNeedle !== '' && ! $this->winnerMatches($item, $winnerNeedle)) {
                    return false;
                }

                return true;
            })
            ->values()
            ->all();
    }

    private function winnerMatches(array $item, string $needle): bool
    {
        foreach ((array) ($item['winningName'] ?? []) as $name) {
            if ($this->containsFilter($name, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function containsFilter(mixed $value, string $needle): bool
    {
        return is_scalar($value)
            && str_contains($this->normalizeFilter((string) $value), $needle);
    }

    private function normalizeFilter(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    private function resetResultFilters(): void
    {
        $this->medicineNameFilter = '';
        $this->activeIngredientFilter = '';
        $this->medicineGroupFilter = '';
        $this->winningCompanyFilter = '';
    }

    private function refreshWishlist(PricingWishlistService $wishlistService): void
    {
        $user = Auth::guard('admin')->user();
        $this->wishlistItems = $user === null
            ? []
            : $wishlistService->recentForUser((int) $user->getAuthIdentifier());
    }

    private function refreshRecentSearches(PricingSearchSnapshotService $snapshotService): void
    {
        $this->recentSearches = $snapshotService->recent();
    }

    private function currentWishlistSourceIds(PricingWishlistService $wishlistService): array
    {
        $user = Auth::guard('admin')->user();

        return $user === null
            ? []
            : $wishlistService->sourceIdsForUser((int) $user->getAuthIdentifier(), $this->results);
    }

    private function adminUserId(): int
    {
        $user = Auth::guard('admin')->user();
        abort_unless($user !== null, 403);

        return (int) $user->getAuthIdentifier();
    }

    private function adminUserIdOrNull(): ?int
    {
        $user = Auth::guard('admin')->user();

        return $user === null ? null : (int) $user->getAuthIdentifier();
    }

    private function authorizeSync(): void
    {
        $user = Auth::guard('admin')->user();
        abort_unless($user !== null && Gate::forUser($user)->allows(self::SYNC_PERMISSION), 403);
    }

    private function authorizeWishlist(): void
    {
        $user = Auth::guard('admin')->user();
        abort_unless($user !== null && Gate::forUser($user)->allows(self::WISHLIST_PERMISSION), 403);
    }
}
