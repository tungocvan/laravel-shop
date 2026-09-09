<?php

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\StockMovement;
use Modules\Inventory\Support\DecimalQuantity;

class MovementReversalService
{
    public function __construct(private readonly StockPostingService $stockPosting) {}

    public function reverse(int $movementId, string $reason, ?int $actorId = null): StockMovement
    {
        return DB::transaction(function () use ($movementId, $reason, $actorId): StockMovement {
            $original = StockMovement::query()->lockForUpdate()->findOrFail($movementId);

            $posted = $this->stockPosting->postBatch([[
                'movement_type' => 'REVERSAL',
                'warehouse_id' => $original->warehouse_id,
                'inventory_item_id' => $original->inventory_item_id,
                'lot_id' => $original->lot_id,
                'quantity_delta' => DecimalQuantity::negate((string) $original->quantity_delta),
                'base_uom' => $original->base_uom,
                'document_type' => 'movement_reversal',
                'document_id' => $original->getKey(),
                'document_line_id' => null,
                'movement_role' => 'REVERSAL',
                'source_type' => $original->source_type,
                'source_identity_key' => $original->source_identity_key,
                'occurred_at' => now(),
                'posted_by' => $actorId,
                'reversal_of_movement_id' => $original->getKey(),
                'metadata' => ['reason' => $reason],
            ]]);

            return $posted->firstOrFail();
        }, 3);
    }
}
