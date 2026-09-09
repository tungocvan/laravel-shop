<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Inventory\Models\Concerns\ProtectsConfirmedDocument;

class Issue extends Model
{
    use ProtectsConfirmedDocument;

    protected $table = 'inventory_issues';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['document_date' => 'datetime', 'confirmed_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(IssueLine::class, 'issue_id')->orderBy('line_number');
    }
}
