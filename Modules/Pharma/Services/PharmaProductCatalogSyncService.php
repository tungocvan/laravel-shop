<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Models\MedicinePackage;
use Modules\Pharma\Models\MedicineVariant;
use Modules\Pharma\Models\PharmaProductLink;
use Modules\Product\Models\Product;

class PharmaProductCatalogSyncService
{
    /**
     * Explicitly invoked projection only. Never changes Product commercial fields.
     * Caller is responsible for scheduling after the Pharma write transaction commits.
     */
    public function syncVariant(MedicineVariant $variant, ?MedicinePackage $package = null): PharmaProductLink
    {
        return DB::transaction(function () use ($variant, $package): PharmaProductLink {
            $variant = MedicineVariant::query()->with('medicine')->lockForUpdate()->findOrFail($variant->getKey());
            $medicine = $variant->medicine;

            if (! $medicine || ! filled($variant->sku)) {
                throw new InvalidArgumentException('A valid Pharma medicine and SKU are required.');
            }

            if ($package) {
                $package = MedicinePackage::query()->findOrFail($package->getKey());
                if ((int) $package->medicine_variant_id !== (int) $variant->getKey()) {
                    throw new InvalidArgumentException('Package does not belong to the variant.');
                }
            } elseif ($variant->packages()->exists()) {
                throw new InvalidArgumentException('Packages exist; sync an orderable package instead.');
            }

            $sourceKey = $package ? 'package:'.$package->getKey() : 'variant:'.$variant->getKey();
            $title = trim($medicine->name.' - '.($package?->packaging_text ?: $variant->presentation_text ?: $variant->sku));
            $fingerprint = hash('sha256', json_encode([
                'medicine' => $medicine->getKey(),
                'variant' => $variant->getKey(),
                'package' => $package?->getKey(),
                'title' => $title,
                'sku' => $variant->sku,
                'catalog_status' => $medicine->catalog_status,
                'product_type' => $medicine->product_type,
                'registration_number' => $medicine->registration_number,
                'variant_status' => $variant->status,
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

            $link = PharmaProductLink::query()->where('source_key', $sourceKey)->lockForUpdate()->first();
            if ($link && ($medicine->catalog_status !== Medicine::CATALOG_ACTIVE || $variant->status !== 'active' || ($package && ! $package->is_orderable))) {
                $link->forceFill(['sync_status' => 'stale', 'last_error' => 'Pharma source is no longer eligible.'])->save();
                $link->product()->update(['is_active' => false]);
                return $link;
            }

            if (! $link && $package && ! $package->is_orderable) {
                throw new InvalidArgumentException('Package is not orderable.');
            }

            if ($link && $link->source_fingerprint === $fingerprint && $link->sync_status === 'synced') {
                return $link;
            }

            if (! $link && $medicine->catalog_status !== Medicine::CATALOG_ACTIVE && filled($medicine->catalog_status)) {
                throw new InvalidArgumentException('Medicine is not active in Pharma catalog.');
            }

            if (! $link) {
                $slug = Str::slug($title);
                $slug = substr($slug ?: 'pharma', 0, 160).'-pharma-'.$variant->getKey().($package ? '-'.$package->getKey() : '');
                $product = Product::query()->create([
                    'title' => $title,
                    'slug' => $slug,
                    'regular_price' => null,
                    'sale_price' => null,
                    'quantity' => 0,
                    'is_active' => false,
                    'affiliate_commission_rate' => null,
                ]);
                $link = new PharmaProductLink(['product_id' => $product->getKey(), 'source_key' => $sourceKey]);
            } else {
                $product = Product::query()->findOrFail($link->product_id);
                $product->forceFill(['title' => $title])->save();
            }

            $link->fill([
                'medicine_id' => $medicine->getKey(),
                'medicine_variant_id' => $variant->getKey(),
                'medicine_package_id' => $package?->getKey(),
                'source_fingerprint' => $fingerprint,
                'sync_status' => 'synced',
                'last_synced_at' => now(),
                'last_error' => null,
            ])->save();

            return $link;
        });
    }

    public function syncMedicine(Medicine $medicine): int
    {
        $count = 0;
        $medicine->load('variants.packages');

        foreach ($medicine->variants as $variant) {
            if ($variant->status !== 'active') {
                continue;
            }

            $packages = $variant->packages->where('is_orderable', true);
            if ($packages->isEmpty()) {
                if ($variant->packages->isNotEmpty()) {
                    continue;
                }
                $this->syncVariant($variant);
                $count++;
                continue;
            }

            foreach ($packages as $package) {
                $this->syncVariant($variant, $package);
                $count++;
            }
        }

        return $count;
    }
}
