<?php

namespace Modules\Pharma\Livewire\DrugBidAward;

use Exception;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Pharma\Livewire\Concerns\AuthorizesPharmaActions;
use Modules\Pharma\Services\DrugBidAwardService;

class Index extends Component
{
    use AuthorizesPharmaActions;
    use WithPagination;

    public $search = '';
    public $filterInvestor = '';
    public $filterCompany = '';
    public $perPage = 10;
    public array $selectedIds = [];
    public bool $selectAll = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterInvestor' => ['except' => ''],
        'filterCompany' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function mount(): void
    {
        $this->authorizePharmaView();
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterInvestor() { $this->resetPage(); }
    public function updatingFilterCompany() { $this->resetPage(); }
    public function updatingPerPage() { $this->resetPage(); }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $service = app(DrugBidAwardService::class);
            $currentItems = $service->getPaginated(
                $this->search,
                $this->filterInvestor,
                $this->filterCompany,
                999999,
                1
            );
            $this->selectedIds = collect($currentItems->items())->map(fn ($item) => (string) $item->id)->toArray();
        } else {
            $this->selectedIds = [];
        }
    }

    public function updatedSelectedIds() { $this->selectAll = false; }

    public function resetFilters()
    {
        $this->reset(['search', 'filterInvestor', 'filterCompany', 'selectedIds', 'selectAll']);
        $this->resetPage();
        $this->dispatch('filters-reset');
    }

    public function deleteAward(DrugBidAwardService $service, int $id)
    {
        $this->authorizePharmaDelete();

        try {
            $service->delete($id);
            $this->selectedIds = array_diff($this->selectedIds, [$id]);
            session()->flash('success', 'Đã xóa bản ghi trúng thầu thành công.');
        } catch (Exception $e) {
            report($e);
            session()->flash('error', 'Không thể xóa bản ghi này.');
        }
    }

    public function deleteSelected(DrugBidAwardService $service)
    {
        $this->authorizePharmaDelete();

        if (empty($this->selectedIds)) {
            return;
        }

        try {
            foreach ($this->selectedIds as $id) {
                $service->delete((int) $id);
            }
            $this->reset(['selectedIds', 'selectAll']);
            session()->flash('success', 'Đã xóa hàng loạt bản ghi thành công.');
        } catch (Exception $e) {
            report($e);
            session()->flash('error', 'Có lỗi xảy ra khi xóa hàng loạt.');
        }
    }

    public function render(DrugBidAwardService $service)
    {
        return view('Pharma::livewire.drug-bid-award.index', [
            'awards' => $service->getPaginated($this->search, $this->filterInvestor, $this->filterCompany, $this->perPage === 'All' ? 999999 : (int) $this->perPage),
            'investors' => $service->getUniqueInvestors(),
            'companies' => $service->getUniqueCompanies(),
        ]);
    }
}
