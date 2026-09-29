<?php

namespace Modules\Pharma\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Pharma\Models\InventoryBalance;
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
    ): LengthAwarePaginator {
        $warehouse = $this->inventory->defaultWarehouse();
        $costSubquery = $this->activeSupplierCostSubquery();

        $query = InventoryBalance::query()
            ->with('medicine')
            ->leftJoinSub($costSubquery, 'supplier_costs', fn ($join) => $join->on('supplier_costs.medicine_id', '=', 'pharma_inventory_balances.medicine_id'))
            ->select('pharma_inventory_balances.*')
            ->selectRaw('COALESCE(pharma_inventory_balances.manual_cost_price, supplier_costs.average_cost_price) as average_cost_price')
            ->selectRaw('(pharma_inventory_balances.quantity_on_hand * COALESCE(pharma_inventory_balances.manual_cost_price, supplier_costs.average_cost_price)) as inventory_value')
            ->where('pharma_inventory_balances.warehouse_id', $warehouse->id)
            ->where('pharma_inventory_balances.quantity_on_hand', '>', 0)
            ->when(filled($search), function (Builder $query) use ($search): void {
                $query->whereHas('medicine', fn (Builder $medicine) => $medicine
                    ->where('medicine_code', 'like', '%'.trim((string) $search).'%')
                    ->orWhere('name', 'like', '%'.trim((string) $search).'%'));
            });

        $this->applyExpiryFilter($query, (string) $expiry);
        $this->applyCostFilter($query, (string) $costStatus);

        match ($sort) {
            'value_desc' => $query->orderByDesc('inventory_value'),
            'value_asc' => $query->orderByRaw('inventory_value IS NULL, inventory_value ASC'),
            default => $query->orderBy('pharma_inventory_balances.expiry_date')->orderBy('pharma_inventory_balances.id'),
        };

        return $query->paginate(
            perPage: in_array($perPage, [25, 50, 100], true) ? $perPage : 25,
            page: max(1, $page),
        );
    }

    public function summary(): array
    {
        $warehouse = $this->inventory->defaultWarehouse();
        $costs = $this->activeSupplierCosts();
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
            'inventory_value' => $balances->sum($value),
            'unpriced_count' => $unpriced->count(),
            'expired_count' => $expired->count(),
            'expired_value' => $expired->sum($value),
            'near_expiry_count' => $nearExpiry->count(),
            'near_expiry_value' => $nearExpiry->sum($value),
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
