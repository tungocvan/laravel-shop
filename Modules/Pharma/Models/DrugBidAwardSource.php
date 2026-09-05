<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrugBidAwardSource extends Model
{
    protected $table = 'pharma_drug_bid_award_sources';

    protected $fillable = [
        'drug_bid_award_id',
        'source_system',
        'source_record_type',
        'source_record_key',
        'source_reference',
        'source_channel',
        'sync_source',
        'source_payload_hash',
        'source_observed_at',
        'synced_at',
        'last_verified_at',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'source_observed_at' => 'datetime',
        'synced_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function award(): BelongsTo
    {
        return $this->belongsTo(DrugBidAward::class, 'drug_bid_award_id');
    }
}
