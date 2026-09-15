<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Collection;
use Modules\Pharma\DTOs\MedicineCatalogItem;
use Modules\Pharma\Models\MedicineVariant;

class MedicineCatalog
{
    public function findBySku(string $sku): ?MedicineCatalogItem
    {
        $variant = MedicineVariant::query()
            ->with(['medicine', 'packages'])
            ->where('sku', trim($sku))
            ->first();

        return $variant ? MedicineCatalogItem::fromVariant($variant) : null;
    }

    public function findByRegistration(string $registration): Collection
    {
        $needle = (new MedicineCatalogNormalizer)->registration($registration);

        if ($needle === null) {
            return collect();
        }

        return MedicineVariant::query()
            ->with(['medicine', 'packages'])
            ->whereHas('medicine', function ($query) use ($needle) {
                $query->whereNotNull('registration_number')
                    ->orWhereNotNull('registration_number_primary');
            })
            ->get()
            ->filter(function (MedicineVariant $variant) use ($needle) {
                $normalizer = new MedicineCatalogNormalizer;

                return $normalizer->registration($variant->medicine->registration_number_primary) === $needle
                    || $normalizer->registration($variant->medicine->registration_number) === $needle;
            })
            ->map(fn (MedicineVariant $variant) => MedicineCatalogItem::fromVariant($variant))
            ->values();
    }

    public function findVariant(int $variantId): ?MedicineCatalogItem
    {
        $variant = MedicineVariant::query()->with(['medicine', 'packages'])->find($variantId);

        return $variant ? MedicineCatalogItem::fromVariant($variant) : null;
    }

    public function search(string $term, int $limit = 20): Collection
    {
        $term = trim($term);
        if ($term === '') {
            return collect();
        }

        return MedicineVariant::query()
            ->with(['medicine', 'packages'])
            ->where(function ($query) use ($term) {
                $query->where('sku', 'like', "%{$term}%")
                    ->orWhere('strength_text', 'like', "%{$term}%")
                    ->orWhere('presentation_text', 'like', "%{$term}%")
                    ->orWhereHas('medicine', fn ($medicine) => $medicine
                        ->where('name', 'like', "%{$term}%")
                        ->orWhere('medicine_code', 'like', "%{$term}%")
                        ->orWhere('active_ingredients', 'like', "%{$term}%")
                        ->orWhere('registration_number', 'like', "%{$term}%")
                        ->orWhere('registration_number_primary', 'like', "%{$term}%")
                        ->orWhereHas('aliases', fn ($alias) => $alias->where('alias', 'like', "%{$term}%")));
            })
            ->limit(max(1, min($limit, 100)))
            ->get()
            ->map(fn (MedicineVariant $variant) => MedicineCatalogItem::fromVariant($variant));
    }

    public function resolve(array $attributes): Collection
    {
        if (filled($attributes['sku'] ?? null)) {
            $item = $this->findBySku((string) $attributes['sku']);

            return $item ? collect([$item]) : collect();
        }

        if (filled($attributes['registration_number'] ?? null)) {
            $matches = $this->findByRegistration((string) $attributes['registration_number']);
            if ($matches->isNotEmpty()) {
                return $matches;
            }
        }

        $term = $attributes['name'] ?? $attributes['brand_name'] ?? $attributes['product_name'] ?? null;

        return filled($term) ? $this->search((string) $term) : collect();
    }
}
