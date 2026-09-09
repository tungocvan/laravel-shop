<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Inventory\Models\Concerns\ProtectsConfirmedParent;

class StocktakeLine extends Model
{
    use ProtectsConfirmedParent;

    protected $table = 'inventory_stocktake_lines';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'system_quantity_snapshot' => 'decimal:6',
            'counted_quantity' => 'decimal:6',
            'posted_variance' => 'decimal:6',
        ];
    }

    public function stocktake(): BelongsTo
    {
        return $this->belongsTo(Stocktake::class, 'stocktake_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }

    protected function confirmedParentTable(): string
    {
        return 'inventory_stocktakes';
    }

    protected function confirmedParentForeignKey(): string
    {
        return 'stocktake_id';
    }
}
