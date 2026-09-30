<?php

namespace Modules\Pharma\Services;

use Modules\Pharma\Models\InventoryBalance;
use Modules\Pharma\Models\InventoryIssue;

final class UserOrderStockReadinessService
{
    public function forIssue(InventoryIssue $issue): array
    {
        $issue->loadMissing('items.medicine');
        $medicineIds = $issue->items->pluck('medicine_id')->filter()->unique()->values();

        $balances = InventoryBalance::query()
            ->where('warehouse_id', $issue->warehouse_id)
            ->whereIn('medicine_id', $medicineIds)
            ->where('quantity_on_hand', '>', 0)
            ->whereDate('expiry_date', '>=', now()->toDateString())
            ->orderBy('medicine_id')
            ->orderBy('expiry_date')
            ->orderBy('batch_number')
            ->get()
            ->groupBy('medicine_id');

        $rows = $issue->items->map(function ($item) use ($balances): array {
            $lots = ($balances[$item->medicine_id] ?? collect())->map(fn (InventoryBalance $balance): array => [
                'balance_id' => (int) $balance->id,
                'batch_number' => (string) $balance->batch_number,
                'expiry_date' => $balance->expiry_date?->format('d/m/Y'),
                'quantity_on_hand' => (float) $balance->quantity_on_hand,
            ])->values();

            $requested = (float) $item->quantity;
            $available = (float) $lots->sum('quantity_on_hand');

            return [
                'item_id' => (int) $item->id,
                'medicine_id' => (int) $item->medicine_id,
                'medicine_code' => $item->medicine?->medicine_code,
                'medicine_name' => $item->medicine?->name ?? 'Thuốc #'.$item->medicine_id,
                'requested_quantity' => $requested,
                'available_stock' => $available,
                'shortage_quantity' => max(0, $requested - $available),
                'is_ready' => $available + 0.00005 >= $requested,
                'lots' => $lots->all(),
            ];
        })->values();

        return [
            'warehouse_id' => (int) $issue->warehouse_id,
            'is_ready' => $rows->every(fn (array $row): bool => $row['is_ready']),
            'rows' => $rows->all(),
        ];
    }
}
