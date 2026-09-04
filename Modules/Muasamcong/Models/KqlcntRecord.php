<?php

namespace Modules\Muasamcong\Models;

use Illuminate\Database\Eloquent\Model;

class KqlcntRecord extends Model
{
    protected $table = 'muasamcong_kqlcnt_records';

    protected $guarded = [];

    protected $casts = [
        'published' => 'boolean',
        'current_contractor_won' => 'boolean',
        'contract_period' => 'integer',
        'contracts' => 'array',
        'all_winners' => 'array',
        'verified_lots' => 'array',
        'tbmt_raw' => 'array',
        'contracts_raw' => 'array',
        'synced_at' => 'datetime',
        'hsmt_synced_at' => 'datetime',
        'imported_at' => 'datetime',
    ];
}
