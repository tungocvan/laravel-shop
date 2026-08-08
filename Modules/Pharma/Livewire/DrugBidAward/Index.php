<?php

namespace Modules\Pharma\Livewire\DrugBidAward;

use Exception;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Pharma\Services\DrugBidAwardService;

class Index extends Component
{
    use WithPagination;

    // Bộ lọc và tìm kiếm
    public $search = '';

    public $filterInvestor = '';

    public $filterCompany = '';

    public $perPage = 10;

    // Quản lý Checkbox xóa hàng loạt
    public array $selectedIds = [];

    public bool $selectAll = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterInvestor' => ['except' => ''],
        'filterCompany' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterInvestor()
    {
        $this->resetPage();
    }

    public function updatingFilterCompany()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

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

    public function updatedSelectedIds()
    {
        $this->selectAll = false;
    }

    public function resetFilters()
    {
        // Reset toàn bộ các thuộc tính filter trên Backend về mặc định
        $this->reset(['search', 'filterInvestor', 'filterCompany', 'selectedIds', 'selectAll']);
        $this->resetPage();

        // Bắn sự kiện thông báo cho Frontend biết bộ lọc đã được reset
        $this->dispatch('filters-reset');
    }

    public function deleteAward(DrugBidAwardService $service, int $id)
    {
        try {
            $service->delete($id);
            $this->selectedIds = array_diff($this->selectedIds, [$id]);
            session()->flash('success', 'Đã xóa bản ghi trúng thầu thành công.');
        } catch (Exception $e) {
            session()->flash('error', 'Không thể xóa bản ghi này.');
        }
    }

    public function deleteSelected(DrugBidAwardService $service)
    {
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
