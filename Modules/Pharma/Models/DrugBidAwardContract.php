<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrugBidAwardContract extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SIGNED = 'signed';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const COMMITTED_STATUSES = [
        self::STATUS_SIGNED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
    ];

    protected $table = 'pharma_drug_bid_award_contracts';

    protected $fillable = [
        'drug_bid_award_allocation_id', 'contract_number', 'contract_date',
        'contract_quantity', 'contract_value', 'start_date', 'end_date', 'status',
        'notes', 'created_by', 'updated_by', 'cancelled_by', 'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'contract_date' => 'date',
        'contract_quantity' => 'decimal:4',
        'contract_value' => 'decimal:4',
        'start_date' => 'date',
        'end_date' => 'date',
        'cancelled_at' => 'datetime',
    ];

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(DrugBidAwardAllocation::class, 'drug_bid_award_allocation_id');
    }

    public function isCommitted(): bool
    {
        return in_array($this->status, self::COMMITTED_STATUSES, true);
    }
}
