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
        'decision_date' => 'date',
        'published_at' => 'datetime',
        'raw_payload' => 'array',
    ];
}
