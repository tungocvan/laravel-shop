<?php

namespace Modules\Muasamcong\Models;

use Illuminate\Database\Eloquent\Model;

class PricingSearchSnapshot extends Model
{
    protected $table = 'muasamcong_pricing_search_snapshots';

    protected $guarded = [];

    protected $casts = [
        'result_payload' => 'array',
        'source_partial' => 'boolean',
        'searched_at' => 'datetime',
        'last_accessed_at' => 'datetime',
    ];
}
