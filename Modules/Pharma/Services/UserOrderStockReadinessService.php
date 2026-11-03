<?php

namespace Modules\Pharma\Services;

use Modules\Pharma\Models\InventoryBalance;
use Modules\Pharma\Models\InventoryIssue;

final class UserOrderStockReadinessService
{
    /**
     * Read-only available stock for a medicine in the issue warehouse.
     * Approval must never mutate inventory balances.
     */
    public function availableForMedicine(InventoryIssue $issue, int $medicineId): float
    {
        return (float) InventoryBalance::query()
            ->where('warehouse_id', $issue->warehouse_id)
            ->where('medicine_id', $medicineId)
            ->where('quantity_on_hand', '>', 0)
            ->whereDate('expiry_date', '>=', now()->toDateString())
            ->sum('quantity_on_hand');
    }

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
            ->get(['medicine_id', 'drug_bid_award_allocation_id', 'quantity', 'expected_supply_date', 'note']);
        $deferredByAllocation = $deferredSupplies->filter(fn ($supply) => $supply->drug_bid_award_allocation_id !== null)
            ->keyBy(fn ($supply) => (int) $supply->drug_bid_award_allocation_id);
        $deferredByMedicine = $deferredSupplies->filter(fn ($supply) => $supply->drug_bid_award_allocation_id === null)
            ->keyBy(fn ($supply) => (int) $supply->medicine_id);

        $itemAllocationIds = $issue->items->pluck('drug_bid_award_allocation_id', 'id')->map(fn ($id) => (int) $id);

        $assignedMedicineSupply = [];
        $rows = $rows->map(function (array $row) use ($deferredByAllocation, $deferredByMedicine, $itemAllocationIds, &$assignedMedicineSupply): array {
            $allocationId = $itemAllocationIds->get($row['item_id']);
            if ($allocationId) {
                $supply = $deferredByAllocation->get((int) $allocationId);
            } else {
                // Medicine-level deferred demand belongs to the order, not every split lot row.
                $medicineId = (int) $row['medicine_id'];
                $supply = isset($assignedMedicineSupply[$medicineId]) ? null : $deferredByMedicine->get($medicineId);
                $assignedMedicineSupply[$medicineId] = true;
            }
            // A previously partially approved order stores only the issued quantity on the item.
            // Restore demand from the pending deferred supply for a repeat approval.
            $row['requested_quantity'] += (float) ($supply?->quantity ?? 0);
            $row['shortage_quantity'] = max(0, $row['requested_quantity'] - $row['available_stock']);
            $row['is_ready'] = $row['available_stock'] + 0.00005 >= $row['requested_quantity'];
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
        $fulfillableRows = $rows->reject(fn (array $row): bool => $row['has_supply_note']);
        $canPostDirectly = $fulfillableRows->isNotEmpty()
            && $fulfillableRows->every(
                fn (array $row): bool => (float) $row['available_stock'] + 0.00005 >= (float) $row['requested_quantity']
            );

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
