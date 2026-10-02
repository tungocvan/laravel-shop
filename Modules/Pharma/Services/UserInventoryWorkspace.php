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

        $averageCost = null;
        $inventoryValue = null;
        if ($canViewCosts) {
            $supplier = $this->activeSupplierCosts()->get($balance->medicine_id)?->average_cost_price;
            $averageCost = $balance->manual_cost_price !== null
                ? (float) $balance->manual_cost_price
                : ($supplier !== null ? (float) $supplier : null);
            $inventoryValue = $averageCost === null ? null : (float) $balance->quantity_on_hand * $averageCost;
        }

        $transactions = InventoryTransaction::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('medicine_id', $balance->medicine_id)
            ->where('batch_number', $balance->batch_number)
            ->whereDate('expiry_date', $balance->expiry_date)
            ->latest('created_at')
            ->latest('id')
            ->get();

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
                'created_at' => $transaction->created_at,
                'source' => $source,
            ];
        });

        return [
            'balance' => $balance,
            'average_cost_price' => $averageCost,
            'inventory_value' => $inventoryValue,
            'movements' => $movements,
        ];
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
