<?php

namespace Modules\Pharma\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Pharma\Models\InventoryBalance;
use Modules\Pharma\Models\InventoryIssue;
use Modules\Pharma\Models\InventoryReceipt;
use Modules\Pharma\Models\InventoryTransaction;
use Modules\Pharma\Models\SupplierTracking;

final class UserInventoryWorkspace
{
    public function __construct(private readonly InventoryService $inventory)
    {
    }

    public function browse(
        ?string $search = null,
        ?string $expiry = null,
        ?string $costStatus = null,
        ?string $sort = null,
        int $perPage = 25,
        int $page = 1,
        bool $canViewCosts = false,
    ): LengthAwarePaginator {
        $warehouse = $this->inventory->defaultWarehouse();

        $query = InventoryBalance::query()
            ->with('medicine')
            ->select('pharma_inventory_balances.*');

        if ($canViewCosts) {
            $query->leftJoinSub($this->activeSupplierCostSubquery(), 'supplier_costs', fn ($join) => $join->on('supplier_costs.medicine_id', '=', 'pharma_inventory_balances.medicine_id'))
                ->selectRaw('COALESCE(pharma_inventory_balances.manual_cost_price, supplier_costs.average_cost_price) as average_cost_price')
                ->selectRaw('(pharma_inventory_balances.quantity_on_hand * COALESCE(pharma_inventory_balances.manual_cost_price, supplier_costs.average_cost_price)) as inventory_value');
        }

        $query
            ->where('pharma_inventory_balances.warehouse_id', $warehouse->id)
            ->where('pharma_inventory_balances.quantity_on_hand', '>', 0)
            ->when(filled($search), function (Builder $query) use ($search): void {
                $query->whereHas('medicine', fn (Builder $medicine) => $medicine
                    ->where('name', 'like', '%'.trim((string) $search).'%')
                    ->orWhere('active_ingredients', 'like', '%'.trim((string) $search).'%'));
            });

        $this->applyExpiryFilter($query, (string) $expiry);
        if ($canViewCosts) {
            $this->applyCostFilter($query, (string) $costStatus);
        }

        match ($canViewCosts ? $sort : null) {
            'value_desc' => $query->orderByDesc('inventory_value'),
            'value_asc' => $query->orderByRaw('inventory_value IS NULL, inventory_value ASC'),
            default => $query->orderBy('pharma_inventory_balances.expiry_date')->orderBy('pharma_inventory_balances.id'),
        };

        return $query->paginate(
            perPage: in_array($perPage, [25, 50, 100], true) ? $perPage : 25,
            page: max(1, $page),
        );
    }

    public function detail(int $balanceId, bool $canViewCosts = false): ?array
    {
        $warehouse = $this->inventory->defaultWarehouse();

        $balance = InventoryBalance::query()
            ->with('medicine')
            ->where('warehouse_id', $warehouse->id)
            ->find($balanceId);

        if (! $balance) {
            return null;
        }

        // A balance id is only the entry point. The stock card is medicine-scoped so
        // every posted movement across the medicine's lots is visible in one ledger.
        $balances = InventoryBalance::query()
            ->with('medicine')
            ->where('warehouse_id', $warehouse->id)
            ->where('medicine_id', $balance->medicine_id)
            ->orderBy('expiry_date')
            ->orderBy('batch_number')
            ->get();

        $averageCost = null;
        $inventoryValue = null;
        if ($canViewCosts) {
            $supplier = $this->activeSupplierCosts()->get($balance->medicine_id)?->average_cost_price;
            $averageCost = $supplier !== null ? (float) $supplier : null;
            $inventoryValue = $balances->sum(function (InventoryBalance $lot) use ($averageCost): float {
                $cost = $lot->manual_cost_price !== null ? (float) $lot->manual_cost_price : $averageCost;

                return $cost === null ? 0.0 : (float) $lot->quantity_on_hand * $cost;
            });
        }

        $transactions = InventoryTransaction::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('medicine_id', $balance->medicine_id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        // The stock card presents the effective ledger, not the audit trail.
        // Pair each reversal with exactly one earlier matching posted movement.
        // This matters when the same document is posted, reverted and posted again:
        // an old reversal must not hide the later effective posting.
        $openMovementIds = [];
        $cancelledMovementIds = [];
        foreach ($transactions as $transaction) {
            $baseType = match ($transaction->type) {
                'receipt_reversal' => 'receipt',
                'issue_reversal' => 'issue',
                default => $transaction->type,
            };
            if (! in_array($baseType, ['receipt', 'issue'], true)) {
                continue;
            }

            $key = implode('|', [
                $transaction->source_type,
                $transaction->source_id,
                $transaction->batch_number,
                $transaction->expiry_date?->format('Y-m-d'),
                $baseType,
            ]);

            if (in_array($transaction->type, ['receipt_reversal', 'issue_reversal'], true)) {
                $originalId = array_pop($openMovementIds[$key]);
                if ($originalId !== null) {
                    $cancelledMovementIds[$originalId] = true;
                }
                continue;
            }

            $openMovementIds[$key] ??= [];
            $openMovementIds[$key][] = (int) $transaction->id;
        }

        $transactions = $transactions
            ->filter(function (InventoryTransaction $transaction) use ($cancelledMovementIds): bool {
                if ($transaction->type === 'opening') {
                    return true;
                }

                return in_array($transaction->type, ['receipt', 'issue'], true)
                    && ! isset($cancelledMovementIds[(int) $transaction->id]);
            })
            ->sortByDesc(fn (InventoryTransaction $transaction): string => sprintf('%s-%020d', $transaction->created_at?->format('YmdHis.u') ?? '', $transaction->id))
            ->values();

        $receiptIds = $transactions->where('source_type', InventoryReceipt::class)->pluck('source_id')->filter()->map(fn ($id) => (int) $id)->unique();
        $issueIds = $transactions->where('source_type', InventoryIssue::class)->pluck('source_id')->filter()->map(fn ($id) => (int) $id)->unique();
        $receipts = InventoryReceipt::query()->where('warehouse_id', $warehouse->id)->whereIn('id', $receiptIds)->get(['id', 'number'])->keyBy('id');
        $issues = InventoryIssue::query()->where('warehouse_id', $warehouse->id)->whereIn('id', $issueIds)->get(['id', 'number'])->keyBy('id');

        $movements = $transactions->map(function (InventoryTransaction $transaction) use ($receipts, $issues): array {
            $source = null;
            if ($transaction->source_type === InventoryReceipt::class) {
                $record = $receipts->get((int) $transaction->source_id);
                $source = $record ? ['kind' => 'receipt', 'id' => (int) $record->id, 'number' => $record->number] : null;
            } elseif ($transaction->source_type === InventoryIssue::class) {
                $record = $issues->get((int) $transaction->source_id);
                $source = $record ? ['kind' => 'issue', 'id' => (int) $record->id, 'number' => $record->number] : null;
            }

            return [
                'id' => (int) $transaction->id,
                'type' => (string) $transaction->type,
                'quantity_delta' => (float) $transaction->quantity_delta,
                'balance_after' => (float) $transaction->balance_after,
                'batch_number' => (string) $transaction->batch_number,
                'expiry_date' => $transaction->expiry_date,
                'created_at' => $transaction->created_at,
                'source' => $source,
            ];
        });

        return [
            'balance' => $balance,
            'balances' => $balances,
            'total_quantity_on_hand' => (float) $balances->sum('quantity_on_hand'),
            'average_cost_price' => $averageCost,
            'inventory_value' => $inventoryValue,
            'total_received' => (float) $movements->sum(fn (array $movement): float => max(0, (float) $movement['quantity_delta'])),
            'total_issued' => (float) abs($movements->sum(fn (array $movement): float => min(0, (float) $movement['quantity_delta']))),
            'movements' => $movements,
        ];
    }

    public function balanceLinksForItems(iterable $items): array
    {
        $warehouse = $this->inventory->defaultWarehouse();
        $keys = collect($items)
            ->filter(fn ($item): bool => filled($item->medicine_id) && filled($item->batch_number) && $item->expiry_date !== null)
            ->map(fn ($item): array => [
                'item_id' => (int) $item->id,
                'medicine_id' => (int) $item->medicine_id,
                'batch_number' => (string) $item->batch_number,
                'expiry_date' => $item->expiry_date->toDateString(),
            ]);

        if ($keys->isEmpty()) {
            return [];
        }

        $balances = InventoryBalance::query()
            ->where('warehouse_id', $warehouse->id)
            ->where(function (Builder $query) use ($keys): void {
                foreach ($keys->unique(fn (array $key): string => $key['medicine_id'].'|'.$key['batch_number'].'|'.$key['expiry_date']) as $key) {
                    $query->orWhere(fn (Builder $candidate) => $candidate
                        ->where('medicine_id', $key['medicine_id'])
                        ->where('batch_number', $key['batch_number'])
                        ->whereDate('expiry_date', $key['expiry_date']));
                }
            })
            ->get(['id', 'medicine_id', 'batch_number', 'expiry_date'])
            ->keyBy(fn (InventoryBalance $balance): string => $balance->medicine_id.'|'.$balance->batch_number.'|'.$balance->expiry_date->toDateString());

        return $keys->mapWithKeys(function (array $key) use ($balances): array {
            $balance = $balances->get($key['medicine_id'].'|'.$key['batch_number'].'|'.$key['expiry_date']);

            return $balance ? [$key['item_id'] => (int) $balance->id] : [];
        })->all();
    }

    public function summary(bool $canViewCosts = false): array
    {
        $warehouse = $this->inventory->defaultWarehouse();
        $costs = $canViewCosts ? $this->activeSupplierCosts() : collect();
        $today = now()->startOfDay();
        $nearExpiryEnd = $today->copy()->addMonths(6);
        $balances = InventoryBalance::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('quantity_on_hand', '>', 0)
            ->get(['medicine_id', 'quantity_on_hand', 'expiry_date', 'manual_cost_price']);

        $value = function (InventoryBalance $row) use ($costs): float {
            $supplier = $costs->get($row->medicine_id)?->average_cost_price;
            $cost = $row->manual_cost_price !== null ? (float) $row->manual_cost_price : ($supplier !== null ? (float) $supplier : null);

            return $cost === null ? 0.0 : (float) $row->quantity_on_hand * $cost;
        };

        $unpriced = $balances->filter(function (InventoryBalance $row) use ($costs): bool {
            $supplier = $costs->get($row->medicine_id)?->average_cost_price;
            $cost = $row->manual_cost_price !== null ? (float) $row->manual_cost_price : ($supplier !== null ? (float) $supplier : null);

            return $cost === null || $cost <= 0;
        });
        $expired = $balances->filter(fn (InventoryBalance $row): bool => $row->expiry_date->lt($today));
        $nearExpiry = $balances->filter(fn (InventoryBalance $row): bool => $row->expiry_date->gte($today) && $row->expiry_date->lte($nearExpiryEnd));

        return [
            'balance_count' => $balances->count(),
            'inventory_value' => $canViewCosts ? $balances->sum($value) : null,
            'unpriced_count' => $canViewCosts ? $unpriced->count() : null,
            'valid_count' => $balances->filter(fn (InventoryBalance $balance) => $balance->expiry_date->gte($today))->count(),
            'valid_value' => $canViewCosts ? $balances->filter(fn (InventoryBalance $balance) => $balance->expiry_date->gte($today))->sum($value) : null,
            'expired_count' => $expired->count(),
            'expired_value' => $canViewCosts ? $expired->sum($value) : null,
            'near_expiry_count' => $nearExpiry->count(),
            'near_expiry_value' => $canViewCosts ? $nearExpiry->sum($value) : null,
        ];
    }

    private function activeSupplierCostSubquery()
    {
        $today = now()->toDateString();

        return SupplierTracking::query()
            ->select('medicine_id', DB::raw('AVG(cost_price) as average_cost_price'))
            ->where('status', 'active')
            ->whereNotNull('cost_price')
            ->where(fn ($query) => $query->whereNull('start_date')->orWhereDate('start_date', '<=', $today))
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', $today))
            ->groupBy('medicine_id');
    }

    private function activeSupplierCosts()
    {
        $today = now()->toDateString();

        return SupplierTracking::query()
            ->select('medicine_id', DB::raw('AVG(cost_price) as average_cost_price'))
            ->where('status', 'active')
            ->whereNotNull('cost_price')
            ->where(fn ($query) => $query->whereNull('start_date')->orWhereDate('start_date', '<=', $today))
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', $today))
            ->groupBy('medicine_id')
            ->get()
            ->keyBy('medicine_id');
    }

    private function applyExpiryFilter(Builder $query, string $warning): void
    {
        $today = now()->startOfDay();

        match ($warning) {
            'expired' => $query->whereDate('expiry_date', '<', $today),
            'lt1' => $query->whereDate('expiry_date', '>=', $today)->whereDate('expiry_date', '<', $today->copy()->addMonth()),
            'lt3' => $query->whereDate('expiry_date', '>=', $today)->whereDate('expiry_date', '<', $today->copy()->addMonths(3)),
            'lt6' => $query->whereDate('expiry_date', '>=', $today)->whereDate('expiry_date', '<', $today->copy()->addMonths(6)),
            'safe' => $query->whereDate('expiry_date', '>=', $today->copy()->addMonths(6)),
            default => null,
        };
    }

    private function applyCostFilter(Builder $query, string $status): void
    {
        match ($status) {
            'priced' => $query->whereRaw('COALESCE(pharma_inventory_balances.manual_cost_price, supplier_costs.average_cost_price) > 0'),
            'unpriced' => $query->where(fn ($query) => $query
                ->whereNull('pharma_inventory_balances.manual_cost_price')
                ->whereNull('supplier_costs.average_cost_price')
                ->orWhereRaw('COALESCE(pharma_inventory_balances.manual_cost_price, supplier_costs.average_cost_price) <= 0')),
            default => null,
        };
    }
}
