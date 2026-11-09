<?php

namespace Modules\Pharma\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Canonical effective lot cost used by Pharma stock and movement reports.
 * A positive manual lot price wins; otherwise use the average positive
 * supplier cost, falling back to positive supplier import price.
 */
final class InventoryLotCostResolver
{
    public function activeSupplierCostQuery(): Builder
    {
        $today = now()->toDateString();

        return DB::table('pharma_supplier_trackings')
            ->selectRaw('medicine_id, AVG(COALESCE(NULLIF(cost_price, 0), NULLIF(import_price, 0))) as average_cost_price')
            ->where('status', 'active')
            ->whereRaw('COALESCE(NULLIF(cost_price, 0), NULLIF(import_price, 0)) > 0')
            ->where(fn ($query) => $query->whereNull('start_date')->orWhereDate('start_date', '<=', $today))
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', $today))
            ->groupBy('medicine_id');
    }

    public function effectiveCost(?float $manualCost, ?float $supplierAverage): ?float
    {
        if ($manualCost !== null && $manualCost > 0) {
            return $manualCost;
        }

        return $supplierAverage !== null && $supplierAverage > 0 ? $supplierAverage : null;
    }
}
