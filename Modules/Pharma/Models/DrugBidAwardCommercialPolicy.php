<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DrugBidAwardCommercialPolicy extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ARCHIVED = 'archived';
    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_AMOUNT_PER_UNIT = 'amount_per_unit';
    public const TYPE_FIXED = 'fixed';

    protected $table = 'pharma_drug_bid_award_commercial_policies';
    protected $fillable = ['result_key','bidding_notice_code','name','commission_type','commission_value','commission_basis','effective_from','effective_until','status','notes','created_by','updated_by','activated_by','activated_at','archived_by','archived_at'];
    protected $casts = ['commission_value'=>'decimal:4','effective_from'=>'date','effective_until'=>'date','activated_at'=>'datetime','archived_at'=>'datetime'];

    public function assignments(): HasMany
    {
        return $this->hasMany(DrugBidAwardCommercialAssignment::class, 'commercial_policy_id');
    }
}
