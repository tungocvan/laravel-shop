<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceListItem extends Model
{
    protected $table = 'pharma_price_list_items';

    protected $fillable = [
        'price_list_id',
        'medicine_id',
        'medicine_variant_id',
        'medicine_package_id',
        'identity_key',
        'declared_price_snapshot',
        'company_sale_price',
        'actual_receivable_price',
        'invoice_price',
        'effective_from',
        'effective_to',
        'status',
        'note',
    ];

    protected $casts = [
        'declared_price_snapshot' => 'decimal:2',
        'company_sale_price' => 'decimal:2',
        'actual_receivable_price' => 'decimal:2',
        'invoice_price' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (PriceListItem $item): void {
            $item->identity_key = self::makeIdentityKey(
                (int) $item->medicine_variant_id,
                $item->medicine_package_id ? (int) $item->medicine_package_id : null,
            );
        });
    }

    public static function makeIdentityKey(int $variantId, ?int $packageId): string
    {
        return "variant:{$variantId}:package:".($packageId ?? 0);
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class, 'price_list_id');
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
};
