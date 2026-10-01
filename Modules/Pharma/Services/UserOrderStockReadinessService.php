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

        $deferredSupplies = $issue->deferredSupplies()
            ->where('status', \Modules\Pharma\Models\InventoryIssueDeferredSupply::PENDING)
            ->get(['drug_bid_award_allocation_id', 'expected_supply_date', 'note'])
            ->keyBy(fn ($supply) => (int) $supply->drug_bid_award_allocation_id);

        $itemAllocationIds = $issue->items->pluck('drug_bid_award_allocation_id', 'id')
            ->map(fn ($id) => (int) $id);

        $rows = $rows->map(function (array $row) use ($deferredSupplies, $itemAllocationIds): array {
            $allocationId = $itemAllocationIds->get($row['item_id']);
            $supply = $allocationId ? $deferredSupplies->get((int) $allocationId) : null;
            $row['supply_expected_date'] = $supply?->expected_supply_date?->format('Y-m-d');
            $row['supply_note'] = $supply?->note;
            $row['has_supply_note'] = ! $row['is_ready'] && $supply !== null;
            $row['has_complete_supply_note'] = $row['has_supply_note']
                && $supply->expected_supply_date !== null
                && trim((string) $supply->note) !== '';
            $row['approval_ready'] = $row['is_ready'] || $row['has_complete_supply_note'];
            return $row;
        });

        $hasStockedItem = $rows->contains(fn (array $row): bool => $row['is_ready']);
        $allRowsCovered = $rows->every(fn (array $row): bool => $row['approval_ready']);
        $canPostDirectly = $deferredSupplies->isEmpty() && $rows->isNotEmpty() && $rows->every(function (array $row): bool {
            return collect($row['lots'])->contains(
                fn (array $lot): bool => (float) $lot['quantity_on_hand'] + 0.00005 >= (float) $row['requested_quantity']
            );
        });

        return [
            'warehouse_id' => (int) $issue->warehouse_id,
            'is_ready' => $rows->every(fn (array $row): bool => $row['is_ready']),
            'can_post_directly' => $canPostDirectly,
            'has_stocked_item' => $hasStockedItem,
            'can_approve' => $hasStockedItem && $allRowsCovered,
            'rows' => $rows->all(),
        ];
    }
}
