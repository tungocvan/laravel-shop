<?php

namespace Modules\Pharma\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Partner\Models\Partner;

class DrugBidAwardManagementAssignment extends Model
{
    public const STATUS_ACTIVE = 'active';

    protected $table = 'pharma_drug_bid_award_management_assignments';
    protected $fillable = ['drug_bid_award_id', 'partner_id', 'user_id', 'status', 'created_by', 'updated_by'];

    public function award(): BelongsTo { return $this->belongsTo(DrugBidAward::class, 'drug_bid_award_id'); }
    public function partner(): BelongsTo { return $this->belongsTo(Partner::class, 'partner_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
}
