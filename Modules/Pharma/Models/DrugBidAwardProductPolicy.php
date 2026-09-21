<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrugBidAwardProductPolicy extends Model
{
    protected $table = 'pharma_drug_bid_award_product_policies';
    protected $fillable = ['drug_bid_award_id', 'commission_percentage', 'created_by', 'updated_by'];
    protected $casts = ['commission_percentage' => 'decimal:4'];

    public function award(): BelongsTo
    {
        return $this->belongsTo(DrugBidAward::class, 'drug_bid_award_id');
    }
}
