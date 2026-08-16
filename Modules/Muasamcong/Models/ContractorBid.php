<?php

namespace Modules\Muasamcong\Models;

use Illuminate\Database\Eloquent\Model;

class ContractorBid extends Model
{
    protected $table = 'muasamcong_contractor_bids';

    protected $guarded = [];

    protected $casts = [
        'created_date' => 'datetime',
        'raw_payload' => 'array',
        'synced_at' => 'datetime',
    ];
}
