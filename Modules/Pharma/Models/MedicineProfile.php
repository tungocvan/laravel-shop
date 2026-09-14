<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicineProfile extends Model
{
    public const STATUS_NEEDS_REVIEW = 'needs_review';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_INCOMPLETE = 'incomplete';

    protected $table = 'pharma_medicine_profiles';

    protected $fillable = [
        'medicine_id',
        'profile_version',
        'profile_status',
        'profile_link',
        'source',
        'effective_from',
        'effective_to',
        'verified_at',
        'is_current',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'verified_at' => 'datetime',
        'is_current' => 'boolean',
    ];

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class, 'medicine_id');
    }
}
