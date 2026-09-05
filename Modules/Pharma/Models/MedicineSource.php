<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicineSource extends Model
{
    protected $table = 'pharma_medicine_sources';

    protected $fillable = [
        'medicine_id',
        'source_system',
        'source_record_type',
        'source_record_key',
        'source_reference',
        'payload_hash',
        'observed_at',
        'synced_at',
        'last_verified_at',
        'is_active',
        'match_method',
        'match_confidence',
        'metadata',
    ];

    protected $casts = [
        'observed_at' => 'datetime',
        'synced_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'match_confidence' => 'integer',
        'metadata' => 'array',
    ];

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class, 'medicine_id');
    }
}
