<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiptLine extends Model
{
    protected $table = 'inventory_receipt_lines';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'source_quantity' => 'decimal:6',
            'conversion_factor' => 'decimal:8',
            'base_quantity' => 'decimal:6',
            'unit_cost' => 'decimal:6',
            'expiry_date' => 'date',
            'manufacture_date' => 'date',
            'metadata' => 'array',
        ];
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class, 'receipt_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }
}
