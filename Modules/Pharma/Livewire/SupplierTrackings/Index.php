<?php

namespace Modules\Pharma\Livewire\SupplierTrackings;

use Livewire\Component;
use Modules\Pharma\Livewire\Concerns\AuthorizesPharmaActions;
use Modules\Pharma\Models\SupplierTracking;
use Modules\Pharma\Services\SupplierTrackingService;

class Index extends Component
{
    use AuthorizesPharmaActions;

    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public string $search = '';

    public string $status = '';

    public string $workingDateFrom = '';

    public string $workingDateTo = '';

    public int $perPage = 10;

    public int $page = 1;

    public array $selectedIds = [];

    public bool $selectPage = false;

    public bool $showBulkDeleteModal = false;

    public bool $showImportExport = false;

    public ?int $expandedFinancialId = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'workingDateFrom' => ['except' => ''],
        'workingDateTo' => ['except' => ''],
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

    public function updatedStatus(): void
    {
        $this->status = in_array($this->status, array_keys($this->statuses()), true) ? $this->status : '';
        $this->resetWorkspacePage();
    }

    public function updatedWorkingDateFrom(): void
    {
        $this->resetWorkspacePage();
    }

    public function updatedWorkingDateTo(): void
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
        $this->selectedIds = array_values(array_intersect(array_map('strval', $this->selectedIds), $pageIds));
        $this->selectPage = $pageIds !== [] && count($this->selectedIds) === count($pageIds);
    }

    public function gotoPage(mixed $page): void
    {
        $this->page = max(1, (int) $page);
        $this->expandedFinancialId = null;
        $this->clearSelection();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'status', 'workingDateFrom', 'workingDateTo']);
        $this->perPage = 10;
        $this->page = 1;
        $this->expandedFinancialId = null;
        $this->clearSelection();
    }

    public function toggleImportExport(): void
    {
        $this->authorizePharmaEdit();
        $this->showImportExport = ! $this->showImportExport;
    }

    public function toggleFinancialDetails(int $id): void
    {
        $this->expandedFinancialId = $this->expandedFinancialId === $id ? null : $id;
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

    public function delete(int $id, SupplierTrackingService $service): void
    {
        $this->authorizePharmaDelete();

        try {
            $service->delete($id);
            $this->clearSelection();
            session()->flash('success', 'Đã xóa dữ liệu theo dõi nhà cung cấp.');
        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', 'Không thể xóa dữ liệu. Vui lòng thử lại.');
        }
    }

    public function deleteSelected(SupplierTrackingService $service): void
    {
        $this->authorizePharmaDelete();
        $pageIds = $this->currentPageIds();
        $ids = array_values(array_intersect(array_map('strval', $this->selectedIds), $pageIds));

        if ($ids === []) {
            $this->showBulkDeleteModal = false;
            $this->clearSelection();

            return;
        }

        try {
            $service->deleteMany($ids);
            $count = count($ids);
            $this->showBulkDeleteModal = false;
            $this->clearSelection();
            session()->flash('success', "Đã xóa {$count} dòng trên trang hiện tại.");
        } catch (\Throwable $e) {
            report($e);
            $this->showBulkDeleteModal = false;
            session()->flash('error', 'Không thể xóa các dòng đã chọn. Vui lòng thử lại.');
        }
    }

    public function getHasSelectedProperty(): bool
    {
        return $this->selectedIds !== [];
    }

    public function getSelectedCountProperty(): int
    {
        return count($this->selectedIds);
    }

    public function money($value): string
    {
        return $value === null || $value === '' ? '0' : number_format((float) $value, 0, ',', '.');
    }

    public function percent($value): string
    {
        return $value === null || $value === '' ? '0%' : number_format((float) $value, 2, ',', '.').'%';
    }

    public function render(SupplierTrackingService $service)
    {
        $this->perPage = $this->normalizePerPage($this->perPage);
        $items = $this->paginated($service);

        if ($items->lastPage() > 0 && $this->page > $items->lastPage()) {
            $this->page = $items->lastPage();
            $this->clearSelection();
            $items = $this->paginated($service);
        }

        return view('Pharma::livewire.supplier-trackings.index', [
            'items' => $items,
            'statuses' => $this->statuses(),
            'perPageOptions' => self::PER_PAGE_OPTIONS,
        ]);
    }

    private function paginated(SupplierTrackingService $service)
    {
        return $service->paginate($this->filters(), $this->perPage, $this->page);
    }

    private function currentPageIds(): array
    {
        return collect($this->paginated(app(SupplierTrackingService::class))->items())
            ->map(fn (SupplierTracking $tracking): string => (string) $tracking->id)
            ->values()
            ->all();
    }

    private function filters(): array
    {
        return [
            'search' => trim($this->search),
            'status' => $this->status,
            'working_date_from' => $this->workingDateFrom,
            'working_date_to' => $this->workingDateTo,
        ];
    }

    private function normalizePerPage(mixed $value): int
    {
        $value = (int) $value;

        return in_array($value, self::PER_PAGE_OPTIONS, true) ? $value : 10;
    }

    private function resetWorkspacePage(): void
    {
        $this->page = 1;
        $this->expandedFinancialId = null;
        $this->clearSelection();
    }

    private function clearSelection(): void
    {
        $this->selectedIds = [];
        $this->selectPage = false;
        $this->showBulkDeleteModal = false;
    }

    private function statuses(): array
    {
        return [
            'active' => 'Đang theo dõi',
            'completed' => 'Hoàn tất',
            'paused' => 'Tạm dừng',
            'cancelled' => 'Hủy',
        ];
    }
}
