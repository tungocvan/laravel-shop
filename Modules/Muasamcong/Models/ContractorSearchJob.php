<?php

namespace Modules\Muasamcong\Models;

use Illuminate\Database\Eloquent\Model;

class ContractorSearchJob extends Model
{
    protected $table = 'muasamcong_contractor_search_jobs';

    protected $guarded = [];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
