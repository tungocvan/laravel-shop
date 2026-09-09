<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lot extends Model
{
    protected $table = 'inventory_lots';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'manufacture_date' => 'date',
            'metadata' => 'array',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
