<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicineVariant extends Model
{
    protected $table = 'pharma_medicine_variants';

    protected $fillable = [
        'medicine_id',
        'sku',
        'strength_text',
        'strength_normalized',
        'presentation_text',
        'presentation_normalized',
        'base_unit',
        'content_value',
        'content_uom',
        'variant_identity_key',
        'sku_basis_hash',
        'status',
        'is_default',
    ];

    protected $casts = [
        'content_value' => 'decimal:4',
        'is_default' => 'boolean',
    ];

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class, 'medicine_id');
    }

    public function packages(): HasMany
    {
        return $this->hasMany(MedicinePackage::class, 'medicine_variant_id');
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(MedicineAlias::class, 'medicine_variant_id');
    }
}
