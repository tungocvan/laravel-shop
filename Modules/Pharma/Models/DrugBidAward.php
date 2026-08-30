<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrugBidAward extends Model
{
    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_MUASAMCONG = 'muasamcong';

    protected $table = 'pharma_drug_bid_awards';

    protected $fillable = [
        'medicine_id',
        'medicine_name',
        'packaging_specification',
        'quantity',
        'unit_price',
        'bidding_notice_code',
        'investor_name',
        'decision_number',
        'decision_date',
        'contract_duration_months',
        'winning_company_name',
        'decision_document_url',
        'source_type',
        'source_id',
        'source_synced_at',
        'source_payload_hash',
    ];

    protected $casts = [
        'medicine_id' => 'integer',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'decision_date' => 'date',
        'contract_duration_months' => 'integer',
        'source_synced_at' => 'datetime',
    ];

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class, 'medicine_id');
    }

    public function isExternalSource(): bool
    {
        return $this->source_type !== self::SOURCE_MANUAL;
    }
}
