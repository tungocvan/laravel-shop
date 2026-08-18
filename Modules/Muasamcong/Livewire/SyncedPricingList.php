<?php

namespace Modules\Muasamcong\Livewire;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Muasamcong\Models\PricingResult;
use Modules\Muasamcong\Services\SyncedPricingExportPreferenceService;

class SyncedPricingList extends Component
{
    use WithPagination;

    private const SYNC_PERMISSION = 'muasamcong.pricing.sync';

    public string $search = '';

    public array $selectedIds = [];

    public bool $showEditModal = false;

    public bool $showExportConfigModal = false;

    public array $exportColumnOrder = [];

    public array $exportSelectedColumns = [];

    public array $exportAlignments = [];

    public ?int $editingId = null;

    public string $editingMedicine = '';

    public string $editingTbmt = '';

    public string $winningName = '';

    public string $winningCode = '';

    public string $decisionNo = '';

    public string $decisionDate = '';

    public string $sttTt202022 = '';

    public string $giaKkKkl = '';

    public string $donGiaVat = '';

    public string $statusMessage = '';

    public string $statusType = '';

    public function mount(): void
    {
        $this->loadExportPreference();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function toggleCurrentPage(array $ids): void
    {
        $this->authorizeMutation();

        $ids = collect($ids)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        $selectedLookup = array_fill_keys(array_map('intval', $this->selectedIds), true);
        $allSelected = $ids->every(fn (int $id): bool => isset($selectedLookup[$id]));

        if ($allSelected) {
            $remove = array_fill_keys($ids->all(), true);
            $this->selectedIds = array_values(array_filter(
                array_map('intval', $this->selectedIds),
                fn (int $id): bool => ! isset($remove[$id])
            ));

            return;
        }

        $this->selectedIds = array_values(array_unique([
            ...array_map('intval', $this->selectedIds),
            ...$ids->all(),
        ]));
    }

    public function clearSelection(): void
    {
        $this->selectedIds = [];
    }

    public function openExportConfig(): void
    {
        $this->authorizeMutation();
        $this->loadExportPreference();
        $this->showExportConfigModal = true;
    }

    public function closeExportConfig(): void
    {
        $this->showExportConfigModal = false;
        $this->loadExportPreference();
    }

    public function moveExportColumn(string $source, string $target): void
    {
        if ($source === $target) {
            return;
        }

        $columns = array_values($this->exportColumnOrder);
        $sourceIndex = array_search($source, $columns, true);
        $targetIndex = array_search($target, $columns, true);

        if ($sourceIndex === false || $targetIndex === false) {
            return;
        }

        array_splice($columns, $sourceIndex, 1);
        $targetIndex = array_search($target, $columns, true);
        array_splice($columns, $targetIndex === false ? count($columns) : $targetIndex, 0, [$source]);
        $this->exportColumnOrder = array_values($columns);
    }

    public function selectAllExportColumns(): void
    {
        foreach ($this->exportColumnOrder as $key) {
            $this->exportSelectedColumns[$key] = true;
        }
    }

    public function clearAllExportColumns(): void
    {
        foreach ($this->exportColumnOrder as $key) {
            $this->exportSelectedColumns[$key] = false;
        }
    }

    public function saveExportConfig(): void
    {
        $this->authorizeMutation();
        $userId = (int) Auth::guard('admin')->id();
        $selected = collect($this->exportSelectedColumns)
            ->filter(fn (mixed $enabled): bool => (bool) $enabled)
            ->keys()
            ->values()
            ->all();

        if ($selected === []) {
            $this->statusType = 'warning';
            $this->statusMessage = 'Cấu hình xuất phải có ít nhất 1 cột.';

            return;
        }

        $saved = app(SyncedPricingExportPreferenceService::class)->save(
            $userId,
            $this->exportColumnOrder,
            $selected,
            $this->exportAlignments,
        );

        $this->applyExportPreference($saved);
        $this->showExportConfigModal = false;
        $this->statusType = 'success';
        $this->statusMessage = 'Đã lưu cấu hình cột, thứ tự hiển thị và canh lề. Các lần xuất sau sẽ tự động dùng cấu hình này.';
    }

    public function editSelected(): void
    {
        $this->authorizeMutation();

        $ids = array_values(array_unique(array_map('intval', $this->selectedIds)));

        if (count($ids) !== 1) {
            $this->statusType = 'warning';
            $this->statusMessage = 'Vui lòng chọn đúng 1 bản ghi để sửa.';

            return;
        }

        $this->openEdit($ids[0]);
    }

    public function openEdit(int $id): void
    {
        $this->authorizeMutation();

        $item = PricingResult::query()->findOrFail($id);

        $this->editingId = $item->id;
        $this->editingMedicine = (string) ($item->ten_thuoc ?: '');
        $this->editingTbmt = (string) ($item->ma_tbmt ?: '');
        $this->winningName = implode("\n", array_values(array_filter((array) $item->winning_name)));
        $this->winningCode = implode("\n", array_values(array_filter((array) $item->winning_code)));
        $this->decisionNo = (string) ($item->so_quyet_dinh ?: '');
        $this->decisionDate = $item->ngay_ban_hanh_quyet_dinh?->format('Y-m-d') ?? '';
        $this->sttTt202022 = (string) ($item->stt_tt20_2022 ?: '');
        $this->giaKkKkl = $item->gia_kk_kkl !== null ? (string) $item->gia_kk_kkl : '';
        $this->donGiaVat = $item->don_gia_vat !== null ? (string) $item->don_gia_vat : '';
        $this->showEditModal = true;
        $this->resetValidation();
    }

    public function closeEdit(): void
    {
        $this->showEditModal = false;
        $this->editingId = null;
    }

    public function saveEdit(): void
    {
        $this->authorizeMutation();

        $validated = $this->validate([
            'winningName' => ['nullable', 'string', 'max:5000'],
            'winningCode' => ['nullable', 'string', 'max:5000'],
            'decisionNo' => ['nullable', 'string', 'max:2000'],
            'decisionDate' => ['nullable', 'date'],
            'sttTt202022' => ['nullable', 'string', 'max:100'],
            'giaKkKkl' => ['nullable', 'numeric', 'min:0'],
            'donGiaVat' => ['nullable', 'numeric', 'min:0'],
        ]);

        $item = PricingResult::query()->findOrFail($this->editingId);
        $winningNames = $this->lines($validated['winningName'] ?? '');
        $winningCodes = $this->lines($validated['winningCode'] ?? '');

        $item->forceFill([
            'winning_name' => $winningNames === [] ? null : $winningNames,
            'winning_code' => $winningCodes === [] ? null : $winningCodes,
            'so_quyet_dinh' => trim((string) ($validated['decisionNo'] ?? '')) ?: null,
            'ngay_ban_hanh_quyet_dinh' => ($validated['decisionDate'] ?? '') !== ''
                ? $validated['decisionDate'].' 00:00:00'
                : null,
            'stt_tt20_2022' => trim((string) ($validated['sttTt202022'] ?? '')) ?: null,
            'gia_kk_kkl' => ($validated['giaKkKkl'] ?? '') !== '' ? (float) $validated['giaKkKkl'] : null,
            'don_gia_vat' => ($validated['donGiaVat'] ?? '') !== '' ? (float) $validated['donGiaVat'] : null,
        ])->save();

        $this->showEditModal = false;
        $this->editingId = null;
        $this->statusType = 'success';
        $this->statusMessage = 'Đã cập nhật thông tin trúng thầu và dữ liệu báo giá bổ sung.';
    }

    public function deleteSelected(): void
    {
        $this->authorizeMutation();

        $ids = array_values(array_unique(array_filter(
            array_map('intval', $this->selectedIds),
            fn (int $id): bool => $id > 0
        )));

        if ($ids === []) {
            $this->statusType = 'warning';
            $this->statusMessage = 'Chưa chọn bản ghi để xóa.';

            return;
        }

        $deleted = PricingResult::query()->whereIn('id', $ids)->delete();
        $this->selectedIds = [];
        $this->statusType = 'success';
        $this->statusMessage = "Đã xóa {$deleted} bản ghi đồng bộ.";

        if ($this->items()->isEmpty() && $this->getPage() > 1) {
            $this->previousPage();
        }
    }

    public function render(): View
    {
        $items = $this->items();
        $currentPageIds = $items->getCollection()->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $selectedLookup = array_fill_keys(array_map('intval', $this->selectedIds), true);
        $currentPageSelected = count(array_filter(
            $currentPageIds,
            fn (int $id): bool => isset($selectedLookup[$id])
        ));

        return view('Muasamcong::livewire.synced-pricing-list', [
            'items' => $items,
            'currentPageIds' => $currentPageIds,
            'currentPageSelected' => $currentPageSelected,
            'exportColumnDefinitions' => SyncedPricingExportPreferenceService::COLUMNS,
        ]);
    }

    private function loadExportPreference(): void
    {
        $userId = (int) Auth::guard('admin')->id();
        if ($userId <= 0) {
            return;
        }

        $this->applyExportPreference(app(SyncedPricingExportPreferenceService::class)->forUser($userId));
    }

    private function applyExportPreference(array $preference): void
    {
        $this->exportColumnOrder = array_values($preference['column_order'] ?? []);
        $selectedLookup = array_fill_keys($preference['selected_columns'] ?? [], true);
        $this->exportSelectedColumns = collect($this->exportColumnOrder)
            ->mapWithKeys(fn (string $key): array => [$key => isset($selectedLookup[$key])])
            ->all();
        $this->exportAlignments = (array) ($preference['alignments'] ?? []);
    }

    private function items(): LengthAwarePaginator
    {
        $keyword = trim($this->search);

        return PricingResult::query()
            ->when($keyword !== '', function ($query) use ($keyword): void {
                $query->where(function ($nested) use ($keyword): void {
                    $nested->where('ten_thuoc', 'like', "%{$keyword}%")
                        ->orWhere('ten_hoat_chat', 'like', "%{$keyword}%")
                        ->orWhere('nhom_thuoc', 'like', "%{$keyword}%")
                        ->orWhere('ma_tbmt', 'like', "%{$keyword}%")
                        ->orWhere('ten_cdt_bmt', 'like', "%{$keyword}%")
                        ->orWhere('so_quyet_dinh', 'like', "%{$keyword}%")
                        ->orWhere('winning_name', 'like', "%{$keyword}%")
                        ->orWhere('winning_code', 'like', "%{$keyword}%")
                        ->orWhere('stt_tt20_2022', 'like', "%{$keyword}%");
                });
            })
            ->orderByDesc('synced_at')
            ->paginate(20);
    }

    private function lines(string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $value) ?: [])
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function authorizeMutation(): void
    {
        $user = Auth::guard('admin')->user();
        abort_unless($user !== null && Gate::forUser($user)->allows(self::SYNC_PERMISSION), 403);
    }
}
