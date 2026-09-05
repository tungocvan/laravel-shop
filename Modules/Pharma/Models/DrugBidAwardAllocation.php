<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Partner\Models\Partner;

class DrugBidAwardAllocation extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'pharma_drug_bid_award_allocations';

    protected $fillable = [
        'drug_bid_award_id', 'partner_id', 'allocated_quantity', 'status',
        'effective_from', 'effective_until', 'notes', 'created_by', 'updated_by',
        'cancelled_by', 'cancelled_at', 'cancellation_reason',
    ];

    protected $casts = [
        'allocated_quantity' => 'decimal:4',
        'effective_from' => 'date',
        'effective_until' => 'date',
        'cancelled_at' => 'datetime',
    ];

    public function award(): BelongsTo
    {
        return $this->belongsTo(DrugBidAward::class, 'drug_bid_award_id');
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(DrugBidAwardContract::class, 'drug_bid_award_allocation_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
