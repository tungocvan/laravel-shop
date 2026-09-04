<?php

namespace Modules\Muasamcong\Models;

use Illuminate\Database\Eloquent\Model;

class KqlcntAwardItem extends Model
{
    protected $table = 'muasamcong_kqlcnt_award_items';

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:4',
        'price_plan' => 'decimal:4',
        'winning_price' => 'decimal:4',
        'amount' => 'decimal:4',
        'shelf_life_months' => 'integer',
        'decision_date' => 'date',
        'published_at' => 'datetime',
        'synced_from_catalog_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'raw_payload' => 'array',
    ];
}
