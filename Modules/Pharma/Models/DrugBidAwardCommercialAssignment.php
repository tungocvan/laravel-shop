<?php

namespace Modules\Pharma\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrugBidAwardCommercialAssignment extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ENDED = 'ended';

    protected $table = 'pharma_drug_bid_award_commercial_assignments';
    protected $fillable = ['commercial_policy_id','drug_bid_award_id','user_id','assignment_role','share_percentage','effective_from','effective_until','status','created_by','updated_by','ended_by','ended_at','end_reason'];
    protected $casts = ['share_percentage'=>'decimal:4','effective_from'=>'date','effective_until'=>'date','ended_at'=>'datetime'];

    public function policy(): BelongsTo { return $this->belongsTo(DrugBidAwardCommercialPolicy::class, 'commercial_policy_id'); }
    public function award(): BelongsTo { return $this->belongsTo(DrugBidAward::class, 'drug_bid_award_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
}
