<?php

namespace Modules\Pharma\Livewire\Medicine;

use Exception;
use Livewire\Component;
use Modules\Pharma\Services\MedicineService;

class Index extends Component
{
    public $search = '';

    public $page = 1;

    public $perPage = 10;

    // Các thuộc tính phục vụ bộ lọc mới
    public $filterCircularGroup = '';

    public $filterSpecialControl = '';

    // Trạng thái checkbox chọn hàng loạt
    public array $selectedIds = [];

    public bool $selectAll = false;

    protected $listeners = ['refreshComponent' => '$refresh'];

    public function updatedSearch()
    {
        $this->page = 1;
        $this->clearSelection();
    }

    public function updatedFilterCircularGroup()
    {
        $this->page = 1;
        $this->clearSelection();
    }

    public function updatedFilterSpecialControl()
    {
        $this->page = 1;
        $this->clearSelection();
    }

    public function updatedPerPage()
    {
        $this->page = 1;
        $this->clearSelection();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $medicineService = app(MedicineService::class);
            $currentItems = $medicineService->getPaginatedMedicines(
                $this->search,
                $this->perPage === 'All' ? 999999 : (int) $this->perPage,
                $this->page,
                $this->filterCircularGroup,
                $this->filterSpecialControl
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
        $this->reset(['search', 'filterCircularGroup', 'filterSpecialControl', 'page']);
        $this->clearSelection();
    }

    private function clearSelection()
    {
        $this->selectedIds = [];
        $this->selectAll = false;
    }

    public function gotoPage($page)
    {
        $this->page = $page;
        $this->clearSelection();
    }

    public function deleteMedicine(MedicineService $medicineService, int $id)
    {
        try {
            $medicineService->delete($id);
            $this->clearSelection();
            session()->flash('success', 'Đã xóa hồ sơ thuốc ra khỏi hệ thống.');
        } catch (Exception $e) {
            session()->flash('error', 'Không thể xóa bản ghi này.');
        }
    }

    public function deleteSelected(MedicineService $medicineService)
    {
        if (empty($this->selectedIds)) {
            return;
        }

        try {
            foreach ($this->selectedIds as $id) {
                $medicineService->delete((int) $id);
            }
            $this->clearSelection();
            session()->flash('success', 'Đã xóa các bản ghi được chọn thành công.');
        } catch (Exception $e) {
            session()->flash('error', 'Có lỗi xảy ra khi xóa hàng loạt dữ liệu.');
        }
    }

    public function render(MedicineService $medicineService)
    {
        // Lấy danh sách các phân nhóm duy nhất phục vụ ô Select Filter ở giao diện
        $circularGroups = $medicineService->getUniqueCircularGroups();

        $medicines = $medicineService->getPaginatedMedicines(
            $this->search,
            $this->perPage === 'All' ? 999999 : (int) $this->perPage,
            $this->page,
            $this->filterCircularGroup,
            $this->filterSpecialControl
        );

        return view('Pharma::livewire.medicine.index', [
            'medicines' => $medicines,
            'circularGroups' => $circularGroups,
        ]);
    }
}
