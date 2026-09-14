<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicineAlias extends Model
{
    protected $table = 'pharma_medicine_aliases';

    protected $fillable = [
        'medicine_id',
        'medicine_variant_id',
        'alias_type',
        'alias_value',
        'normalized_value',
        'source',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class, 'medicine_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(MedicineVariant::class, 'medicine_variant_id');
    }
}
