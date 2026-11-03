<?php

namespace Modules\Pharma\Services;

use Modules\Pharma\Models\InventoryIssue;
use Modules\Pharma\Models\InventoryIssueDeferredSupply;
use Modules\Pharma\Models\InventoryTransaction;

final class InventoryIssueDocumentLines
{
    public function forIssue(InventoryIssue $issue): array
    {
        $issue->loadMissing(['items.medicine', 'deferredSupplies.medicine']);
        $movements = InventoryTransaction::query()
            ->where('source_type', InventoryIssue::class)
            ->where('source_id', $issue->getKey())
            ->where('type', 'issue')
            ->where('quantity_delta', '<', 0)
            ->get(['medicine_id', 'batch_number', 'expiry_date', 'quantity_delta']);

        $key = static fn ($medicineId, $batch, $expiry): string => (int) $medicineId.'|'.(string) $batch.'|'.($expiry instanceof \DateTimeInterface ? $expiry->format('Y-m-d') : substr((string) $expiry, 0, 10));
        $remaining = $movements->groupBy(fn ($movement) => $key($movement->medicine_id, $movement->batch_number, $movement->expiry_date))
            ->map(fn ($rows) => (float) abs($rows->sum('quantity_delta')))->all();

        $posted = collect();
        foreach ($issue->items as $item) {
            $movementKey = $key($item->medicine_id, $item->batch_number, $item->expiry_date);
            $quantity = min((float) $item->quantity, max(0, $remaining[$movementKey] ?? 0));
            if ($quantity <= 0) {
                continue;
            }
            $posted->push(['item' => $item, 'quantity' => $quantity, 'value' => $quantity * (float) $item->unit_price]);
            $remaining[$movementKey] -= $quantity;
        }

        $pending = $issue->deferredSupplies
            ->where('status', InventoryIssueDeferredSupply::PENDING)
            ->map(function ($supply) use ($issue): array {
                $item = $issue->items->first(function ($row) use ($supply): bool {
                    if ($supply->drug_bid_award_allocation_id !== null) {
                        return (int) $row->drug_bid_award_allocation_id === (int) $supply->drug_bid_award_allocation_id;
                    }
                    return (int) $row->medicine_id === (int) $supply->medicine_id;
                });
                $quantity = (float) $supply->quantity;
                return [
                    'supply' => $supply,
                    'item' => $item,
                    'quantity' => $quantity,
                    'value' => $item ? $quantity * (float) $item->unit_price : null,
                ];
            })->values();

        return [
            'posted' => $posted,
            'pending' => $pending,
            'posted_total' => $posted->sum('value'),
            'pending_total' => $pending->sum(fn ($row) => $row['value'] ?? 0),
        ];
    }
}
