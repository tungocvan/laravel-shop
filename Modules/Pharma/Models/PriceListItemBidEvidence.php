<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceListItemBidEvidence extends Model
{
    protected $table = 'pharma_price_list_item_bid_evidence';

    protected $fillable = [
        'price_list_item_id', 'drug_bid_award_id', 'source_system', 'source_record_key',
        'bid_price', 'quantity', 'unit', 'investor_code', 'investor_name', 'contractor_code',
        'contractor_name', 'decision_number', 'award_date', 'medicine_id', 'medicine_variant_id',
        'medicine_package_id', 'captured_at', 'captured_by', 'metadata',
    ];

    protected $casts = [
        'bid_price' => 'decimal:4',
        'quantity' => 'decimal:4',
        'award_date' => 'date',
        'captured_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function priceListItem(): BelongsTo
    {
        return $this->belongsTo(PriceListItem::class, 'price_list_item_id');
    }

    public function award(): BelongsTo
    {
        return $this->belongsTo(DrugBidAward::class, 'drug_bid_award_id');
    }
}
