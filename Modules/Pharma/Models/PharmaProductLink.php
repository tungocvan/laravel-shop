<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Models\Product;

class PharmaProductLink extends Model
{
    protected $table = 'pharma_product_links';

    protected $fillable = [
        'product_id', 'medicine_id', 'medicine_variant_id', 'medicine_package_id',
        'source_key', 'sync_status', 'source_fingerprint', 'last_synced_at', 'last_error',
    ];

    protected $casts = ['last_synced_at' => 'datetime'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
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
}
