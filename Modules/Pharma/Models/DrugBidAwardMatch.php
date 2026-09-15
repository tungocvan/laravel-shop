<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrugBidAwardMatch extends Model
{
    public const STATUS_EXACT = 'exact';
    public const STATUS_HIGH_CONFIDENCE = 'high_confidence';
    public const STATUS_REVIEW_REQUIRED = 'review_required';
    public const STATUS_UNMATCHED = 'unmatched';

    public const REVIEW_AUTO = 'auto';
    public const REVIEW_PENDING = 'pending';
    public const REVIEW_CONFIRMED = 'confirmed';
    public const REVIEW_REJECTED = 'rejected';
    public const REVIEW_IGNORED = 'ignored';
    public const REVIEW_STALE = 'stale';

    public const LEVEL_MEDICINE = 'medicine';
    public const LEVEL_VARIANT = 'variant';
    public const LEVEL_PACKAGE = 'package';

    protected $table = 'pharma_drug_bid_award_matches';

    protected $fillable = [
        'drug_bid_award_id', 'medicine_id', 'medicine_variant_id', 'medicine_package_id',
        'match_status', 'match_method', 'confidence', 'resolution_level', 'review_status',
        'is_manual', 'matched_by', 'matched_at', 'source_identity_hash', 'matched_identity_hash',
        'review_reason', 'metadata',
    ];

    protected $casts = [
        'confidence' => 'integer',
        'is_manual' => 'boolean',
        'matched_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function award(): BelongsTo
    {
        return $this->belongsTo(DrugBidAward::class, 'drug_bid_award_id');
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class, 'medicine_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(MedicineVariant::class, 'medicine_variant_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(MedicinePackage::class, 'medicine_package_id');
    }

    public function isManualConfirmed(): bool
    {
        return $this->is_manual && $this->review_status === self::REVIEW_CONFIRMED;
    }
}
