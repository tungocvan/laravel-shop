<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class StockMovement extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'inventory_movements';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'quantity_delta' => 'decimal:6',
            'occurred_at' => 'datetime',
            'created_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn (): never => throw new LogicException('Inventory stock movements are immutable.'));
        static::deleting(fn (): never => throw new LogicException('Inventory stock movements are immutable.'));
    }
}
