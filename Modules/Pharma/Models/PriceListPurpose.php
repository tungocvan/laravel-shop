<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceListPurpose extends Model
{
    protected $table = 'pharma_price_list_purposes';

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
        'sort_order',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function priceLists(): HasMany
    {
        return $this->hasMany(PriceList::class, 'purpose_id');
    }
}
