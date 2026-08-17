<?php

namespace Modules\Muasamcong\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Modules\Muasamcong\Models\ContractorManualLot;
use Modules\Muasamcong\Services\HsmtSnapshotService;

class ManualContractorLots extends Component
{
    public string $notifyNo = '';

    public string $contractorCode = '';

    public string $contractorName = '';

    public string $search = '';

    public int $page = 1;

    public int $perPage = 20;

    public array $selected = [];

    public ?string $notice = null;

    public ?string $error = null;

    public function mount(string $notifyNo, string $contractorCode, string $contractorName = ''): void
    {
        $this->notifyNo = trim($notifyNo);
        $this->contractorCode = trim($contractorCode);
        $this->contractorName = trim($contractorName);
        $this->selected = ContractorManualLot::query()
            ->where('notify_no', $this->notifyNo)
            ->where('contractor_code', $this->contractorCode)
            ->pluck('lot_key')
            ->all();
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    public function previousPage(): void
    {
        $this->page = max(1, $this->page - 1);
    }

    public function nextPage(): void
    {
        $this->page = min($this->totalPages(), $this->page + 1);
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    public function saveSelections(HsmtSnapshotService $snapshots): void
    {
        $this->notice = null;
        $this->error = null;

        $snapshot = $snapshots->loadForNotifyNo($this->notifyNo);
        $items = is_array($snapshot['items'] ?? null) ? $snapshot['items'] : [];

        if ($items === []) {
            $this->error = 'Chưa có snapshot HSMT. Hãy tải Danh mục mời thầu (HSMT) trước.';

            return;
        }

        $selectedKeys = array_values(array_unique(array_filter(array_map('strval', $this->selected))));
        $indexed = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $indexed[$this->lotKey($item)] = $item;
        }

        $userId = Auth::guard('admin')->id();
        $now = now();

        DB::transaction(function () use ($selectedKeys, $indexed, $userId, $now): void {
            ContractorManualLot::query()
                ->where('notify_no', $this->notifyNo)
                ->where('contractor_code', $this->contractorCode)
                ->delete();

            foreach ($selectedKeys as $key) {
                $item = $indexed[$key] ?? null;
                if (! is_array($item)) {
                    continue;
                }

                $quantity = $this->numeric($item['quantity'] ?? null);
                $pricePlan = $this->numeric($item['price_plan'] ?? null);
                $lotPrice = $this->numeric($item['lot_price'] ?? null);

                ContractorManualLot::query()->create([
                    'contractor_code' => $this->contractorCode,
                    'notify_no' => $this->notifyNo,
                    'lot_key' => $key,
                    'lot_no' => $item['lot_no'] ?? null,
                    'lot_name' => $item['lot_name'] ?? null,
                    'medicine_name' => $item['medicine_name'] ?? null,
                    'active_ingredient' => $item['active_ingredient'] ?? null,
                    'quantity' => $quantity,
                    'price_plan' => $pricePlan,
                    'lot_price' => $lotPrice,
                    'plan_amount' => $quantity !== null && $pricePlan !== null ? $quantity * $pricePlan : null,
                    'source' => 'manual',
                    'confirmed_by' => $userId,
                    'confirmed_at' => $now,
                    'raw_payload' => $item,
                ]);
            }
        });

        $this->notice = 'Đã lưu '.count($selectedKeys).' lô do người dùng xác nhận cho nhà thầu này.';
    }

    public function render(HsmtSnapshotService $snapshots): View
    {
        $snapshot = $snapshots->loadForNotifyNo($this->notifyNo);
        $items = is_array($snapshot['items'] ?? null) ? $snapshot['items'] : [];
        $term = mb_strtoupper(trim($this->search));

        $filtered = array_values(array_filter($items, function (mixed $item) use ($term): bool {
            if (! is_array($item)) {
                return false;
            }
            if ($term === '') {
                return true;
            }

            $haystack = mb_strtoupper(implode(' ', array_filter([
                $item['lot_no'] ?? null,
                $item['lot_name'] ?? null,
                $item['medicine_name'] ?? null,
                $item['active_ingredient'] ?? null,
                $item['medicine_code'] ?? null,
                $item['medicine_group'] ?? null,
            ])));

            return str_contains($haystack, $term);
        }));

        $totalPages = max(1, (int) ceil(count($filtered) / $this->perPage));
        $this->page = min($this->page, $totalPages);
        $pageItems = array_slice($filtered, ($this->page - 1) * $this->perPage, $this->perPage);
        $pageItems = array_map(function (array $item): array {
            $item['_lot_key'] = $this->lotKey($item);

            return $item;
        }, $pageItems);

        $selectedItems = [];
        $selectedLookup = array_fill_keys($this->selected, true);
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $key = $this->lotKey($item);
            if (isset($selectedLookup[$key])) {
                $selectedItems[] = $item;
            }
        }

        $totals = [
            'count' => count($selectedItems),
            'quantity' => 0.0,
            'plan_amount' => 0.0,
            'lot_price' => 0.0,
        ];
        foreach ($selectedItems as $item) {
            $quantity = $this->numeric($item['quantity'] ?? null);
            $pricePlan = $this->numeric($item['price_plan'] ?? null);
            $lotPrice = $this->numeric($item['lot_price'] ?? null);
            $totals['quantity'] += $quantity ?? 0;
            $totals['plan_amount'] += ($quantity !== null && $pricePlan !== null) ? $quantity * $pricePlan : 0;
            $totals['lot_price'] += $lotPrice ?? 0;
        }

        return view('Muasamcong::livewire.manual-contractor-lots', [
            'hasSnapshot' => $items !== [],
            'items' => $pageItems,
            'filteredTotal' => count($filtered),
            'totalPages' => $totalPages,
            'totals' => $totals,
        ]);
    }

    private function totalPages(): int
    {
        $snapshot = app(HsmtSnapshotService::class)->loadForNotifyNo($this->notifyNo);
        $items = is_array($snapshot['items'] ?? null) ? $snapshot['items'] : [];
        $term = mb_strtoupper(trim($this->search));

        $count = count(array_filter($items, function (mixed $item) use ($term): bool {
            if (! is_array($item)) {
                return false;
            }
            if ($term === '') {
                return true;
            }
            $haystack = mb_strtoupper(implode(' ', array_filter([
                $item['lot_no'] ?? null,
                $item['lot_name'] ?? null,
                $item['medicine_name'] ?? null,
                $item['active_ingredient'] ?? null,
            ])));

            return str_contains($haystack, $term);
        }));

        return max(1, (int) ceil($count / $this->perPage));
    }

    private function lotKey(array $item): string
    {
        $lotNo = trim((string) ($item['lot_no'] ?? ''));
        if ($lotNo !== '') {
            return 'lot:'.$lotNo;
        }

        return 'hash:'.hash('sha256', json_encode([
            $item['lot_name'] ?? null,
            $item['medicine_name'] ?? null,
            $item['active_ingredient'] ?? null,
            $item['medicine_code'] ?? null,
            $item['quantity'] ?? null,
            $item['price_plan'] ?? null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function numeric(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
