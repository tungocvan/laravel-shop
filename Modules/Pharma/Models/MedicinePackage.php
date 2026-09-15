<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicinePackage extends Model
{
    protected $table = 'pharma_medicine_packages';

    protected $fillable = [
        'medicine_variant_id',
        'package_code',
        'packaging_text',
        'packaging_normalized',
        'outer_package_type',
        'inner_package_type',
        'outer_quantity',
        'inner_quantity',
        'base_quantity',
        'container_volume',
        'container_volume_uom',
        'gtin',
        'barcode',
        'package_identity_key',
        'is_orderable',
        'is_inventory_unit',
    ];

    protected $casts = [
        'outer_quantity' => 'integer',
        'inner_quantity' => 'integer',
        'base_quantity' => 'decimal:4',
        'container_volume' => 'decimal:4',
        'is_orderable' => 'boolean',
        'is_inventory_unit' => 'boolean',
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(MedicineVariant::class, 'medicine_variant_id');
    }
}
