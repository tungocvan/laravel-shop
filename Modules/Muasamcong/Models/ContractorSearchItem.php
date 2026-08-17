<?php

namespace Modules\Muasamcong\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractorSearchItem extends Model
{
    protected $table = 'muasamcong_contractor_search_items';

    protected $guarded = [];

    protected $casts = [
        'created_date' => 'datetime',
        'raw_payload' => 'array',
    ];

    public function search(): BelongsTo
    {
        return $this->belongsTo(ContractorSearch::class, 'contractor_search_id');
    }
}
