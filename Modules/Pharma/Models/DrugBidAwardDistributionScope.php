<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Partner\Models\Partner;

class DrugBidAwardDistributionScope extends Model
{
    protected $table = 'pharma_drug_bid_award_distribution_scopes';

    protected $fillable = [
        'result_key', 'bidding_notice_code', 'province_code',
        'effective_from', 'effective_until', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_until' => 'date',
    ];

    public function partners(): BelongsToMany
    {
        return $this->belongsToMany(
            Partner::class,
            'pharma_drug_bid_award_distribution_scope_partners',
            'distribution_scope_id',
            'partner_id'
        )->withTimestamps();
    }
}
