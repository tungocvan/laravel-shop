<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;

class StockBalance extends Model
{
    public const CREATED_AT = null;

    protected $table = 'inventory_balances';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['quantity_on_hand' => 'decimal:6', 'updated_at' => 'datetime'];
    }
}
