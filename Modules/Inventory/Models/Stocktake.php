<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Inventory\Models\Concerns\ProtectsConfirmedDocument;

class Stocktake extends Model
{
    use ProtectsConfirmedDocument;

    protected $table = 'inventory_stocktakes';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'counted_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StocktakeLine::class, 'stocktake_id')->orderBy('line_number');
    }
}
