<?php

namespace Modules\Muasamcong\Livewire;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Muasamcong\Models\PricingResult;

class SyncedPricingList extends Component
{
    use WithPagination;

    private const SYNC_PERMISSION = 'muasamcong.pricing.sync';

    public string $search = '';

    public array $selectedIds = [];

    public bool $showEditModal = false;

    public ?int $editingId = null;

    public string $editingMedicine = '';

    public string $editingTbmt = '';

    public string $winningName = '';

    public string $winningCode = '';

    public string $decisionNo = '';

    public string $decisionDate = '';

    public string $statusMessage = '';

    public string $statusType = '';

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
        ])->save();

        $this->showEditModal = false;
        $this->editingId = null;
        $this->statusType = 'success';
        $this->statusMessage = 'Đã cập nhật thông tin trúng thầu.';
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
        ]);
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
                        ->orWhere('winning_code', 'like', "%{$keyword}%");
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
