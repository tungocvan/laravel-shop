<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    protected $table = 'inventory_items';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'lot_tracking' => 'boolean',
            'expiry_tracking' => 'boolean',
            'allow_fractional_quantity' => 'boolean',
            'is_active' => 'boolean',
            'reorder_level' => 'decimal:6',
            'metadata' => 'array',
        ];
    }

    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class, 'inventory_item_id');
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(InventoryItemAlias::class, 'inventory_item_id');
    }
}
