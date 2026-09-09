<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItemAlias extends Model
{
    protected $table = 'inventory_item_aliases';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['confirmed_at' => 'datetime', 'metadata' => 'array'];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
