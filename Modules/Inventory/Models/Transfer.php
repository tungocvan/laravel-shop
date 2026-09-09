<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Inventory\Models\Concerns\ProtectsConfirmedDocument;

class Transfer extends Model
{
    use ProtectsConfirmedDocument;

    protected $table = 'inventory_transfers';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['document_date' => 'datetime', 'confirmed_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    public function sourceWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(TransferLine::class, 'transfer_id')->orderBy('line_number');
    }
}
