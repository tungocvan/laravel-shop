<?php

namespace Modules\Muasamcong\Models;

use Illuminate\Database\Eloquent\Model;

class KqlcntRecord extends Model
{
    protected $table = 'muasamcong_kqlcnt_records';

    protected $guarded = [];

    protected $casts = [
        'published' => 'boolean',
        'contracts' => 'array',
        'verified_lots' => 'array',
        'tbmt_raw' => 'array',
        'contracts_raw' => 'array',
        'synced_at' => 'datetime',
    ];
}
