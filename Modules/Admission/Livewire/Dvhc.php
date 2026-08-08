<?php

namespace Modules\Admission\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Admission\Models\AdmissionLocation;
use Modules\Admission\Services\ImportExport;

class Dvhc extends Component
{
    public $search = '';

    public $selectedProvince = '';

    public $editingProvinceName = '';

    public $provinces = [];

    public $wards = [];

    public $rows = [];

    public array $selectedIds = [];

    public bool $selectAll = false;

    public function mount()
    {
        $this->loadProvinces();
        $this->loadData();
    }

    // =========================
    // LOAD PROVINCES
    // =========================
    public function loadProvinces()
    {
        $this->provinces = AdmissionLocation::query()
            ->select('province_name')
            ->distinct()
            ->orderBy('province_name')
            ->get()
            ->toArray();
    }

    // =========================
    // CHỌN TỈNH
    // =========================
    public function updatedSelectedProvince()
    {
        $this->clearSelection();

        if (! $this->selectedProvince) {
            $this->editingProvinceName = '';
            $this->loadData();

            return;
        }

        $this->editingProvinceName = $this->selectedProvince;

        $this->wards = AdmissionLocation::where('province_name', $this->selectedProvince)
            ->orderBy('ward_name')
            ->get()
            ->toArray();

        $this->loadData();
    }

    // =========================
    // UPDATE TỈNH (ALL ROWS)
    // =========================
    public function updateProvinceName()
    {
        $this->authorizeAction();

        if (! $this->selectedProvince || ! $this->editingProvinceName) {
            return;
        }

        $this->editingProvinceName = trim($this->editingProvinceName);

        $this->validate([
            'editingProvinceName' => ['required', 'string', 'max:255'],
        ]);

        AdmissionLocation::where('province_name', $this->selectedProvince)
            ->update([
                'province_name' => $this->editingProvinceName,
            ]);

        $this->selectedProvince = $this->editingProvinceName;

        $this->loadProvinces();
        $this->loadData();
        session()->flash('success', 'Đã cập nhật tên Tỉnh/Thành phố.');
    }

    // =========================
    // UPDATE PHƯỜNG
    // =========================
    public function updateRow($index)
    {
        $this->authorizeAction();

        $row = $this->rows[$index] ?? null;

        if (! $row) {
            return;
        }

        $data = $this->validate([
            "rows.{$index}.province_name" => ['required', 'string', 'max:255'],
            "rows.{$index}.ward_name" => ['required', 'string', 'max:255'],
        ]);

        $provinceName = trim($data['rows'][$index]['province_name']);
        $wardName = trim($data['rows'][$index]['ward_name']);

        DB::transaction(function () use ($row, $provinceName, $wardName): void {
            AdmissionLocation::query()
                ->where('province_code', $row['province_code'])
                ->update(['province_name' => $provinceName]);

            AdmissionLocation::query()
                ->whereKey($row['id'])
                ->update(['ward_name' => $wardName]);
        });

        $this->selectedProvince = $provinceName;
        $this->editingProvinceName = $provinceName;
        $this->loadProvinces();
        $this->loadData();
        session()->flash('success', 'Đã cập nhật tên tỉnh và phường/xã.');
    }

    // =========================
    // LOAD TABLE
    // =========================
    public function updatedSearch()
    {
        $this->clearSelection();
        $this->loadData();
    }

    public function updatedSelectAll(bool $selected): void
    {
        $this->selectedIds = $selected
            ? array_map(static fn (array $row): string => (string) $row['id'], $this->rows)
            : [];
    }

    public function updatedSelectedIds(): void
    {
        $visibleIds = array_map(static fn (array $row): string => (string) $row['id'], $this->rows);
        $selectedVisibleIds = array_intersect($visibleIds, array_map('strval', $this->selectedIds));

        $this->selectAll = $visibleIds !== [] && count($selectedVisibleIds) === count($visibleIds);
    }

    public function deleteSelected(): void
    {
        $this->authorizeAction();

        $ids = collect($this->selectedIds)
            ->filter(static fn (mixed $id): bool => filter_var($id, FILTER_VALIDATE_INT) !== false)
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            $this->addError('selectedIds', 'Vui lòng chọn ít nhất một dòng để xóa.');

            return;
        }

        $deleted = AdmissionLocation::query()->whereKey($ids)->delete();

        $this->clearSelection();
        $this->loadProvinces();
        $this->loadData();

        session()->flash('success', "Đã xóa {$deleted} đơn vị hành chính.");
    }

    public function loadData()
    {
        $this->rows = AdmissionLocation::query()

            ->when($this->selectedProvince, fn ($q) => $q->where('province_name', $this->selectedProvince)
            )

            ->when($this->search, function ($q) {
                $q->where('ward_name', 'like', "%{$this->search}%");
            })

            ->orderBy('ward_name')
            ->limit(200)
            ->get()
            ->toArray();
    }

    #[On('import-export-completed')]
    public function refreshAfterImport(string $serviceClass): void
    {
        if ($serviceClass !== ImportExport::class) {
            return;
        }

        $this->loadProvinces();
        $this->loadData();
        $this->clearSelection();
    }

    public function render()
    {
        return view('Admission::livewire.dvhc');
    }

    private function clearSelection(): void
    {
        $this->selectedIds = [];
        $this->selectAll = false;
        $this->resetErrorBag('selectedIds');
    }

    private function authorizeAction(): void
    {
        abort_unless(
            auth('admin')->check() && auth('admin')->user()->can('manage_admission_locations'),
            403
        );
    }
}
