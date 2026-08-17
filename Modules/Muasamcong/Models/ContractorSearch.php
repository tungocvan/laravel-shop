<?php

namespace Modules\Muasamcong\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractorSearch extends Model
{
    protected $table = 'muasamcong_contractor_searches';

    protected $guarded = [];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'first_searched_at' => 'datetime',
        'last_searched_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ContractorSearchItem::class, 'contractor_search_id');
    }
}
