<?php

namespace Modules\Pharma\Livewire\DrugBidAward;

use Exception;
use Livewire\Component;
use Modules\Pharma\Livewire\Concerns\AuthorizesPharmaActions;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Services\DrugBidAwardService;

class Index extends Component
{
    use AuthorizesPharmaActions;

    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public string $search = '';
    public string $filterInvestor = '';
    public string $filterCompany = '';
    public string $filterSource = '';
    public string $filterMatchStatus = '';
    public int $perPage = 10;
    public int $page = 1;
    public array $selectedIds = [];
    public bool $selectPage = false;
    public bool $showBulkDeleteModal = false;

    protected $queryString = [
        'search' => ['except' => ''], 'filterInvestor' => ['except' => ''],
        'filterCompany' => ['except' => ''], 'filterSource' => ['except' => ''],
        'filterMatchStatus' => ['except' => ''], 'perPage' => ['except' => 10], 'page' => ['except' => 1],
    ];

    public function mount(): void
    {
        $this->authorizePharmaView();
        $this->perPage = $this->normalizePerPage($this->perPage);
    }

    public function updatedSearch(): void { $this->resetWorkspacePage(); }
    public function updatedFilterInvestor(): void { $this->resetWorkspacePage(); }
    public function updatedFilterCompany(): void { $this->resetWorkspacePage(); }

    public function updatedFilterSource(): void
    {
        $this->filterSource = in_array($this->filterSource, $this->sourceOptions(), true) ? $this->filterSource : '';
        $this->resetWorkspacePage();
    }

    public function updatedFilterMatchStatus(): void
    {
        $this->filterMatchStatus = in_array($this->filterMatchStatus, $this->matchStatusValues(), true) ? $this->filterMatchStatus : '';
        $this->resetWorkspacePage();
    }

    public function updatedPerPage(mixed $value): void
    {
        $this->perPage = $this->normalizePerPage($value);
        $this->resetWorkspacePage();
    }

    public function updatedSelectPage(bool $value): void { $this->selectedIds = $value ? $this->currentPageIds() : []; }

    public function updatedSelectedIds(): void
    {
        $pageIds = $this->currentPageIds();
        $this->selectedIds = array_values(array_intersect(array_map('strval', $this->selectedIds), $pageIds));
        $this->selectPage = $pageIds !== [] && count($this->selectedIds) === count($pageIds);
    }

    public function gotoPage(mixed $page): void
    {
        $this->page = max(1, (int) $page);
        $this->clearSelection();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'filterInvestor', 'filterCompany', 'filterSource', 'filterMatchStatus']);
        $this->page = 1;
        $this->clearSelection();
    }

    public function confirmBulkDelete(): void
    {
        $this->authorizePharmaDelete();
        $this->updatedSelectedIds();
        $this->showBulkDeleteModal = $this->selectedIds !== [];
    }

    public function cancelBulkDelete(): void { $this->showBulkDeleteModal = false; }

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
            foreach ($ids as $id) { $service->delete((int) $id); }
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
        return view('Pharma::livewire.drug-bid-award.index', [
            'awards' => $awards, 'perPageOptions' => self::PER_PAGE_OPTIONS,
            'sourceOptions' => [DrugBidAward::SOURCE_MANUAL => 'Nhập thủ công', DrugBidAward::SOURCE_MUASAMCONG => 'Mua sắm công'],
            'matchStatusOptions' => [
                DrugBidAward::MATCH_VERIFIED => 'Đã đối soát', DrugBidAward::MATCH_PROVISIONAL => 'Tạm khớp',
                DrugBidAward::MATCH_AMBIGUOUS => 'Mơ hồ', DrugBidAward::MATCH_UNRESOLVED => 'Chưa đối soát',
            ],
        ]);
    }

    private function paginated(DrugBidAwardService $service)
    {
        return $service->getPaginated($this->search, $this->filterInvestor, $this->filterCompany, $this->perPage, $this->page, $this->filterSource ?: null, $this->filterMatchStatus ?: null);
    }

    private function currentPageIds(): array
    {
        return collect($this->paginated(app(DrugBidAwardService::class))->items())->map(fn (DrugBidAward $award): string => (string) $award->id)->values()->all();
    }

    private function normalizePerPage(mixed $value): int
    {
        $value = (int) $value;
        return in_array($value, self::PER_PAGE_OPTIONS, true) ? $value : 10;
    }

    private function resetWorkspacePage(): void { $this->page = 1; $this->clearSelection(); }

    private function clearSelection(): void
    {
        $this->selectedIds = [];
        $this->selectPage = false;
        $this->showBulkDeleteModal = false;
    }

    private function sourceOptions(): array { return ['', DrugBidAward::SOURCE_MANUAL, DrugBidAward::SOURCE_MUASAMCONG]; }

    private function matchStatusValues(): array
    {
        return ['', DrugBidAward::MATCH_VERIFIED, DrugBidAward::MATCH_PROVISIONAL, DrugBidAward::MATCH_AMBIGUOUS, DrugBidAward::MATCH_UNRESOLVED];
    }
}
