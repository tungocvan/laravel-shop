<?php

namespace Modules\Muasamcong\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Modules\Muasamcong\Services\MuaSamCongService;
use Modules\Muasamcong\Services\PricingResultSyncService;

class TracuuThuoctrungthau extends Component
{
    private const SYNC_PERMISSION = 'muasamcong.pricing.sync';

    public string $keyword = '';

    public array $results = [];

    public array $selectedSourceIds = [];

    public array $syncedSourceIds = [];

    public ?array $detailItem = null;

    public bool $loading = false;

    public string $error = '';

    public string $syncStatus = '';

    public string $syncMessage = '';

    public function search(MuaSamCongService $service, PricingResultSyncService $syncService): void
    {
        $validated = $this->validate([
            'keyword' => ['required', 'string', 'min:2', 'max:200'],
        ], [
            'keyword.required' => 'Vui lòng nhập từ khóa.',
        ]);

        $this->loading = true;
        $this->error = '';
        $this->syncStatus = '';
        $this->syncMessage = '';
        $this->results = [];
        $this->selectedSourceIds = [];
        $this->syncedSourceIds = [];
        $this->detailItem = null;

        $result = $service->searchPricing($validated['keyword']);

        if (! ($result['success'] ?? false)) {
            $this->error = $result['message'] ?? 'Không thể tra cứu thuốc trúng thầu.';
            $this->loading = false;

            return;
        }

        $this->results = is_array($result['data']['items'] ?? null)
            ? $result['data']['items']
            : [];
        $this->syncedSourceIds = $syncService->existingSourceIds($this->results);
        $this->loading = false;
    }

    public function selectAllUnsynced(): void
    {
        $synced = array_fill_keys($this->syncedSourceIds, true);

        $this->selectedSourceIds = collect($this->results)
            ->pluck('id')
            ->filter(fn (mixed $id): bool => is_string($id) && $id !== '' && ! isset($synced[$id]))
            ->unique()
            ->values()
            ->all();
    }

    public function clearSelection(): void
    {
        $this->selectedSourceIds = [];
    }

    public function openDetail(string $sourceId): void
    {
        $this->detailItem = collect($this->results)
            ->first(fn (mixed $item): bool => is_array($item) && ($item['id'] ?? null) === $sourceId);
    }

    public function closeDetail(): void
    {
        $this->detailItem = null;
    }

    public function syncSelected(PricingResultSyncService $syncService): void
    {
        $this->authorizeSync();
        $this->syncStatus = '';
        $this->syncMessage = '';

        if ($this->selectedSourceIds === []) {
            $this->syncStatus = 'error';
            $this->syncMessage = 'Vui lòng chọn ít nhất một bản ghi chưa đồng bộ.';

            return;
        }

        $user = Auth::guard('admin')->user();
        $report = $syncService->syncSelected($this->results, $this->selectedSourceIds, $user?->getAuthIdentifier());

        $this->syncedSourceIds = $syncService->existingSourceIds($this->results);
        $this->selectedSourceIds = [];

        $inserted = (int) ($report['inserted'] ?? 0);
        $duplicates = (int) ($report['duplicates'] ?? 0);
        $missing = (int) ($report['missing'] ?? 0);

        if ($inserted > 0) {
            $this->syncStatus = 'success';
            $this->syncMessage = "Đã đồng bộ {$inserted} bản ghi mới.";

            if ($duplicates > 0) {
                $this->syncMessage .= " Bỏ qua {$duplicates} bản ghi đã tồn tại.";
            }

            if ($missing > 0) {
                $this->syncMessage .= " Có {$missing} bản ghi không còn trong kết quả hiện tại.";
            }

            return;
        }

        $this->syncStatus = 'warning';
        $this->syncMessage = $duplicates > 0
            ? 'Các bản ghi đã chọn đều đã tồn tại, không có dữ liệu mới được đồng bộ.'
            : 'Không có bản ghi hợp lệ để đồng bộ.';
    }

    public function render(): View
    {
        return view('Muasamcong::livewire.tracuu-thuoctrungthau');
    }

    private function authorizeSync(): void
    {
        $user = Auth::guard('admin')->user();

        abort_unless(
            $user !== null && Gate::forUser($user)->allows(self::SYNC_PERMISSION),
            403
        );
    }
}
