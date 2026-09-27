<?php

namespace Modules\Pharma\Livewire\DrugBidAward;

use Exception;
use Livewire\Component;
use Modules\Pharma\Integrations\Muasamcong\MuasamcongDrugAwardSyncService;
use Modules\Pharma\Livewire\Concerns\AuthorizesPharmaActions;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Services\DrugBidAwardService;
use Modules\Pharma\Services\DrugBidAwardImportExport;

class Index extends Component
{
    use AuthorizesPharmaActions;

    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public string $search = '';

    public string $filterTbmt = '';

    public string $filterInvestor = '';

    public string $filterCompany = '';

    public string $valueSort = '';

    public bool $showImportExport = false;

    public bool $showFilters = true;

    public string $filterSource = '';

    public string $filterBusinessSetup = '';

    public int $perPage = 10;

    public int $page = 1;

    public array $selectedIds = [];

    public bool $selectPage = false;

    public bool $showBulkDeleteModal = false;

    public ?int $syncAfterId = null;

    public bool $syncHasMore = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterTbmt' => ['except' => ''],
        'filterInvestor' => ['except' => ''],
        'filterSource' => ['except' => ''],
        'filterBusinessSetup' => ['except' => ''],
        'valueSort' => ['except' => ''],
        'perPage' => ['except' => 10],
        'page' => ['except' => 1],
    ];

    public function mount(): void
    {
        $this->authorizePharmaView();
        $this->perPage = $this->normalizePerPage($this->perPage);
    }

    public function updatedSearch(): void
    {
        $this->resetWorkspacePage();
    }

    public function updatedFilterTbmt(): void
    {
        $this->resetWorkspacePage();
    }

    public function updatedFilterInvestor(): void
    {
        $this->resetWorkspacePage();
    }

    public function updatedValueSort(): void
    {
        $this->valueSort = in_array($this->valueSort, ['', 'desc', 'asc'], true) ? $this->valueSort : '';
        $this->resetWorkspacePage();
    }

    public function updatedFilterSource(): void
    {
        $this->filterSource = in_array($this->filterSource, $this->sourceOptions(), true) ? $this->filterSource : '';
        $this->resetWorkspacePage();
    }

    public function updatedFilterBusinessSetup(): void
    {
        $allowed = ['', 'commercial_missing', 'commercial_ready', 'allocation_missing', 'allocation_ready'];
        $this->filterBusinessSetup = in_array($this->filterBusinessSetup, $allowed, true) ? $this->filterBusinessSetup : '';
        $this->resetWorkspacePage();
    }

    public function updatedPerPage(mixed $value): void
    {
        $this->perPage = $this->normalizePerPage($value);
        $this->resetWorkspacePage();
    }

    public function updatedSelectPage(bool $value): void
    {
        $this->selectedIds = $value ? $this->currentPageIds() : [];
    }

    public function updatedSelectedIds(): void
    {
        $pageIds = $this->currentPageIds();
        $this->selectedIds = array_values(array_intersect(array_map('strval', $this->selectedIds), $pageIds));
        $this->selectPage = $pageIds !== [] && count($this->selectedIds) === count($pageIds);
    }

    public function setAwardSelected(mixed $id, bool $selected): void
    {
        if (! is_numeric($id)) {
            return;
        }

        $value = (string) ((int) $id);
        $pageIds = $this->currentPageIds();

        if (! in_array($value, $pageIds, true)) {
            return;
        }

        $selectedIds = array_values(array_intersect(array_map('strval', $this->selectedIds), $pageIds));

        if ($selected) {
            $selectedIds[] = $value;
        } else {
            $selectedIds = array_values(array_diff($selectedIds, [$value]));
        }

        $this->selectedIds = array_values(array_unique($selectedIds));
        $this->selectPage = $pageIds !== [] && count($this->selectedIds) === count($pageIds);
    }

    public function clearAwardSelection(): void
    {
        $this->clearSelection();
    }

    public function exportSelectedAwards(DrugBidAwardImportExport $exportService)
    {
        $this->authorizePharmaEdit();
        $this->updatedSelectedIds();

        if ($this->selectedIds === []) {
            session()->flash('error', 'Vui lòng chọn ít nhất một TBMT để export.');

            return null;
        }

        $path = $exportService->export([
            'selected_ids' => $this->selectedIds,
        ]);

        return response()
            ->download($exportService->exportAbsolutePath($path), basename($path))
            ->deleteFileAfterSend(true);
    }


    public function editResultGroup(int $representativeId)
    {
        $this->authorizePharmaEdit();

        return $this->redirectRoute('admin.pharma.drug-bid-awards.edit', ['id' => $representativeId], navigate: false);
    }

    public function gotoPage(mixed $page): void
    {
        $this->page = max(1, (int) $page);
        $this->clearSelection();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'filterTbmt', 'filterInvestor', 'filterSource', 'filterBusinessSetup', 'valueSort']);
        $this->page = 1;
        $this->clearSelection();
        $this->dispatch('filters-reset');
    }

    public function syncMuasamcong(MuasamcongDrugAwardSyncService $syncService): void
    {
        $this->authorizePharmaEdit();

        try {
            $result = $syncService->sync($this->syncAfterId, 250);
            $this->syncAfterId = $result['last_id'];
            $this->syncHasMore = $result['has_more'];
            $this->page = 1;
            $this->clearSelection();

            $message = "Đồng bộ KQLCNT: {$result['projected']}/{$result['processed']} bản ghi thành công";

            if ($result['failed'] > 0) {
                $message .= ", {$result['failed']} lỗi";
            }

            $message .= $result['has_more'] ? '. Còn dữ liệu, có thể đồng bộ tiếp.' : '. Đã hết batch hiện tại.';
            session()->flash($result['failed'] > 0 ? 'error' : 'success', $message);
        } catch (\Throwable $exception) {
            report($exception);
            session()->flash('error', 'Không thể đồng bộ KQLCNT lúc này. Dữ liệu Pharma hiện có vẫn sử dụng bình thường.');
        }
    }

    public function restartMuasamcongSync(): void
    {
        $this->authorizePharmaEdit();
        $this->syncAfterId = null;
        $this->syncHasMore = false;
    }

    public function confirmBulkDelete(): void
    {
        $this->authorizePharmaDelete();
        $this->updatedSelectedIds();
        $this->showBulkDeleteModal = $this->selectedIds !== [];
    }

    public function cancelBulkDelete(): void
    {
        $this->showBulkDeleteModal = false;
    }

    public function deleteAward(DrugBidAwardService $service, int $id): void
    {
        $this->authorizePharmaDelete();

        try {
            $service->delete($id);
            $this->clearSelection();
            session()->flash('success', 'Đã xóa bản ghi trúng thầu thành công.');
        } catch (Exception $exception) {
            report($exception);
            session()->flash('error', 'Không thể xóa bản ghi này.');
        }
    }

    public function deleteResultGroup(DrugBidAwardService $service, int $representativeId): void
    {
        $this->authorizePharmaDelete();

        try {
            $deleted = $service->deleteResultGroup($representativeId);
            $this->page = 1;
            $this->clearSelection();
            session()->flash('success', "Đã xóa TBMT và {$deleted} sản phẩm trúng thầu liên quan.");
        } catch (Exception $exception) {
            report($exception);
            session()->flash('error', 'Không thể xóa TBMT này. Vui lòng kiểm tra dữ liệu phân bổ hoặc dữ liệu liên quan.');
        }
    }

    public function deleteSelected(DrugBidAwardService $service): void
    {
        $this->authorizePharmaDelete();
        $ids = array_values(array_intersect(array_map('strval', $this->selectedIds), $this->currentPageIds()));

        if ($ids === []) {
            $this->showBulkDeleteModal = false;
            $this->clearSelection();

            return;
        }

        try {
            foreach ($ids as $id) {
                $service->delete((int) $id);
            }

            $count = count($ids);
            $this->showBulkDeleteModal = false;
            $this->clearSelection();
            session()->flash('success', "Đã xóa {$count} bản ghi trên trang hiện tại.");
        } catch (Exception $exception) {
            report($exception);
            $this->showBulkDeleteModal = false;
            session()->flash('error', 'Có lỗi xảy ra khi xóa hàng loạt.');
        }
    }

    public function render(DrugBidAwardService $service)
    {
        $this->perPage = $this->normalizePerPage($this->perPage);
        $awards = $this->paginated($service);

        if ($awards->lastPage() > 0 && $this->page > $awards->lastPage()) {
            $this->page = $awards->lastPage();
            $this->clearSelection();
            $awards = $this->paginated($service);
        }

        $dashboard = $this->dashboardMetrics();

        return view('Pharma::livewire.drug-bid-award.index', [
            'awards' => $awards,
            'dashboard' => $dashboard,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'tbmtOptions' => $this->distinctOptions('bidding_notice_code'),
            'investorOptions' => $this->distinctOptions('investor_name'),
            'medicineOptions' => $this->distinctOptions('medicine_name'),
            'sourceOptions' => [
                DrugBidAward::SOURCE_MANUAL => 'Nhập thủ công',
                DrugBidAward::SOURCE_MUASAMCONG => 'Mua sắm công',
            ],
        ]);
    }

    private function paginated(DrugBidAwardService $service)
    {
        return $service->getResultGroupsPaginated(
            $this->search,
            $this->filterInvestor,
            null,
            $this->perPage,
            $this->page,
            $this->filterSource ?: null,
            null,
            $this->filterTbmt,
            $this->valueSort,
            $this->filterBusinessSetup ?: null,
        );
    }

    private function dashboardMetrics(): array
    {
        $groupKey = "COALESCE(NULLIF(bidding_notice_code, ''), CONCAT('award-', id))";
        $totalGroups = DrugBidAward::query()
            ->selectRaw("{$groupKey} as result_key")
            ->distinct()
            ->get()
            ->count();
        $totalValue = (float) (DrugBidAward::query()
            ->selectRaw('SUM(COALESCE(amount, COALESCE(winning_price, unit_price, 0) * COALESCE(quantity, 0))) as total_value')
            ->value('total_value') ?? 0);
        $allocatedGroups = DrugBidAward::query()
            ->whereHas('allocations', fn ($query) => $query->where('status', 'active'))
            ->selectRaw($groupKey.' as result_key')->distinct()->get()->count();
        $commercialGroups = DrugBidAward::query()
            ->whereHas('allocations.managementAssignments', fn ($query) => $query->where('status', 'active'))
            ->selectRaw($groupKey.' as result_key')->distinct()->get()->count();

        return [
            'total' => $totalGroups,
            'value' => $totalValue,
            'allocated' => $allocatedGroups,
            'commercial' => $commercialGroups,
            'pending' => max(0, $totalGroups - min($allocatedGroups, $commercialGroups)),
        ];
    }

    private function currentPageIds(): array
    {
        return collect($this->paginated(app(DrugBidAwardService::class))->items())
            ->map(fn (DrugBidAward $award): string => (string) $award->representative_id)
            ->values()
            ->all();
    }

    private function distinctOptions(string $column): array
    {
        return DrugBidAward::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->select($column)
            ->distinct()
            ->orderBy($column)
            ->limit(500)
            ->pluck($column)
            ->all();
    }

    private function normalizePerPage(mixed $value): int
    {
        $value = (int) $value;

        return in_array($value, self::PER_PAGE_OPTIONS, true) ? $value : 10;
    }

    private function resetWorkspacePage(): void
    {
        $this->page = 1;
        $this->clearSelection();
    }

    private function clearSelection(): void
    {
        $this->selectedIds = [];
        $this->selectPage = false;
        $this->showBulkDeleteModal = false;
    }

    private function sourceOptions(): array
    {
        return ['', DrugBidAward::SOURCE_MANUAL, DrugBidAward::SOURCE_MUASAMCONG];
    }
}
