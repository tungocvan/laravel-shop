<?php

namespace Modules\Pharma\Livewire\SupplierTrackings;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Pharma\Services\SupplierTrackingService;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public int $perPage = 15;

    public array $selected = [];

    public bool $selectAll = false;

    protected string $paginationTheme = 'tailwind';

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedSelectAll(bool $value): void
    {
        if (! $value) {
            $this->selected = [];

            return;
        }

        $this->selected = app(SupplierTrackingService::class)
            ->getFilteredIds($this->filters())
            ->map(fn ($id) => (string) $id)
            ->toArray();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'status',
        ]);

        $this->perPage = 15;

        $this->resetPage();
        $this->resetSelection();
    }

    public function delete(int $id, SupplierTrackingService $service): void
    {
        try {
            $service->delete($id);

            $this->resetSelection();

            session()->flash('success', 'Đã xóa dữ liệu theo dõi nhà cung cấp.');
        } catch (\Throwable $e) {
            report($e);

            session()->flash('error', 'Không thể xóa dữ liệu. Vui lòng thử lại.');
        }
    }

    public function deleteSelected(SupplierTrackingService $service): void
    {
        if (empty($this->selected)) {
            session()->flash('error', 'Vui lòng chọn ít nhất một dòng cần xóa.');

            return;
        }

        try {
            $service->deleteMany($this->selected);

            $this->resetSelection();

            session()->flash('success', 'Đã xóa các dòng đã chọn.');
        } catch (\Throwable $e) {
            report($e);

            session()->flash('error', 'Không thể xóa các dòng đã chọn. Vui lòng thử lại.');
        }
    }

    public function getHasSelectedProperty(): bool
    {
        return count($this->selected) > 0;
    }

    public function getSelectedCountProperty(): int
    {
        return count($this->selected);
    }

    public function money($value): string
    {
        if ($value === null || $value === '') {
            return '0';
        }

        return number_format((float) $value, 0, ',', '.');
    }

    public function percent($value): string
    {
        if ($value === null || $value === '') {
            return '0%';
        }

        return number_format((float) $value, 2, ',', '.').'%';
    }

    private function filters(): array
    {
        return [
            'search' => trim($this->search),
            'status' => $this->status,
        ];
    }

    private function resetSelection(): void
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    public function render(SupplierTrackingService $service)
    {
        return view('Pharma::livewire.supplier-trackings.index', [
            'items' => $service->paginate(
                filters: $this->filters(),
                perPage: $this->perPage
            ),
            'statuses' => $this->statuses(),
        ]);
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
