<?php

namespace Modules\Pharma\Livewire\Medicine;

use Exception;
use Livewire\Component;
use Modules\Pharma\Livewire\Concerns\AuthorizesPharmaActions;
use Modules\Pharma\Services\MedicineService;

class Index extends Component
{
    use AuthorizesPharmaActions;

    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public string $search = '';

    public int $page = 1;

    public int $perPage = 10;

    public string $filterCircularGroup = '';

    public string $filterSpecialControl = '';

    public array $selectedIds = [];

    public bool $selectPage = false;

    protected $listeners = ['refreshComponent' => '$refresh'];

    public function mount(): void
    {
        $this->authorizePharmaView();
        $this->perPage = $this->normalizePerPage($this->perPage);
    }

    public function updatedSearch(): void
    {
        $this->resetWorkspacePage();
    }

    public function updatedFilterCircularGroup(): void
    {
        $this->resetWorkspacePage();
    }

    public function updatedFilterSpecialControl(): void
    {
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
        $this->selectedIds = array_values(array_intersect(
            array_map('strval', $this->selectedIds),
            $pageIds,
        ));

        $this->selectPage = $pageIds !== [] && count($this->selectedIds) === count($pageIds);
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'filterCircularGroup', 'filterSpecialControl']);
        $this->page = 1;
        $this->clearSelection();
    }

    public function gotoPage(mixed $page): void
    {
        $this->page = max(1, (int) $page);
        $this->clearSelection();
    }

    public function deleteMedicine(MedicineService $medicineService, int $id): void
    {
        $this->authorizePharmaDelete();

        try {
            $medicineService->delete($id);
            $this->clearSelection();
            session()->flash('success', 'Đã xóa hồ sơ thuốc ra khỏi hệ thống.');
        } catch (Exception $exception) {
            report($exception);
            session()->flash('error', 'Không thể xóa bản ghi này.');
        }
    }

    public function deleteSelected(MedicineService $medicineService): void
    {
        $this->authorizePharmaDelete();

        $pageIds = $this->currentPageIds();
        $ids = array_values(array_intersect(array_map('strval', $this->selectedIds), $pageIds));

        if ($ids === []) {
            $this->clearSelection();

            return;
        }

        try {
            foreach ($ids as $id) {
                $medicineService->delete((int) $id);
            }

            $this->clearSelection();
            session()->flash('success', 'Đã xóa các bản ghi được chọn trên trang hiện tại.');
        } catch (Exception $exception) {
            report($exception);
            session()->flash('error', 'Có lỗi xảy ra khi xóa hàng loạt dữ liệu.');
        }
    }

    public function render(MedicineService $medicineService)
    {
        $this->perPage = $this->normalizePerPage($this->perPage);

        $medicines = $medicineService->getPaginatedMedicines(
            $this->search,
            $this->perPage,
            $this->page,
            $this->filterCircularGroup,
            $this->filterSpecialControl,
        );

        if ($medicines->lastPage() > 0 && $this->page > $medicines->lastPage()) {
            $this->page = $medicines->lastPage();
            $this->clearSelection();
            $medicines = $medicineService->getPaginatedMedicines(
                $this->search,
                $this->perPage,
                $this->page,
                $this->filterCircularGroup,
                $this->filterSpecialControl,
            );
        }

        return view('Pharma::livewire.medicine.index', [
            'medicines' => $medicines,
            'circularGroups' => $medicineService->getUniqueCircularGroups(),
            'perPageOptions' => self::PER_PAGE_OPTIONS,
        ]);
    }

    private function currentPageIds(): array
    {
        $medicineService = app(MedicineService::class);
        $currentItems = $medicineService->getPaginatedMedicines(
            $this->search,
            $this->normalizePerPage($this->perPage),
            $this->page,
            $this->filterCircularGroup,
            $this->filterSpecialControl,
        );

        return collect($currentItems->items())
            ->map(static fn ($medicine): string => (string) $medicine->id)
            ->values()
            ->all();
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
    }

    private function normalizePerPage(mixed $value): int
    {
        $value = (int) $value;

        return in_array($value, self::PER_PAGE_OPTIONS, true) ? $value : 10;
    }
}
