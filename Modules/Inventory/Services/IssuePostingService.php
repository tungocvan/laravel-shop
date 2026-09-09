<?php

namespace Modules\Inventory\Services;

use DomainException;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\Issue;
use Modules\Inventory\Models\IssueLine;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Support\DecimalQuantity;

class IssuePostingService
{
    public function __construct(private readonly StockPostingService $stockPosting) {}

    public function confirm(int $issueId, ?int $actorId = null): Issue
    {
        return DB::transaction(function () use ($issueId, $actorId): Issue {
            $issue = Issue::query()->lockForUpdate()->findOrFail($issueId);

            if ($issue->status === 'CONFIRMED') {
                return $issue;
            }

            if ($issue->status !== 'DRAFT') {
                throw new DomainException('Only draft issues can be confirmed.');
            }

            $warehouse = Warehouse::query()->findOrFail($issue->warehouse_id);
            if (! $warehouse->is_active) {
                throw new DomainException('Issue warehouse must be active.');
            }

            $lines = IssueLine::query()->where('issue_id', $issue->getKey())->orderBy('id')->lockForUpdate()->get();
            if ($lines->isEmpty()) {
                throw new DomainException('Issue must contain at least one line.');
            }

            $movements = [];

            foreach ($lines as $line) {
                $item = $line->item()->firstOrFail();

                if (! DecimalQuantity::isPositive((string) $line->base_quantity)) {
                    throw new DomainException('Issue quantity must be positive.');
                }

                if ($line->base_uom !== $item->base_uom) {
                    throw new DomainException('Issue line base UOM must match the inventory item base UOM.');
                }

                if ($item->lot_tracking && $line->lot_id === null) {
                    throw new DomainException('Lot selection is required for lot-tracked inventory items.');
                }

                if ($line->lot_id !== null) {
                    $lot = $line->lot()->firstOrFail();
                    if ((int) $lot->inventory_item_id !== (int) $item->getKey()) {
                        throw new DomainException('Issue lot does not belong to the selected inventory item.');
                    }
                }

                $movements[] = [
                    'movement_type' => 'ISSUE',
                    'warehouse_id' => $issue->warehouse_id,
                    'inventory_item_id' => $item->getKey(),
                    'lot_id' => $line->lot_id,
                    'quantity_delta' => DecimalQuantity::negate((string) $line->base_quantity),
                    'base_uom' => $item->base_uom,
                    'document_type' => 'issue',
                    'document_id' => $issue->getKey(),
                    'document_line_id' => $line->getKey(),
                    'movement_role' => 'OUT',
                    'occurred_at' => $issue->document_date ?? now(),
                    'posted_by' => $actorId,
                ];
            }

            $this->stockPosting->postBatch($movements);

            $issue->status = 'CONFIRMED';
            $issue->confirmed_by = $actorId;
            $issue->confirmed_at = now();
            $issue->save();

            return $issue->refresh();
        }, 3);
    }
}
