<?php

namespace Modules\Pharma\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Pharma\DTOs\MedicineCatalogItem;
use Modules\Pharma\Models\MedicineVariant;

class MedicineCatalog
{
    public function browse(?string $search = null, int $perPage = 25, int $page = 1, ?string $filter = null, bool $allowSupplierPricing = false, ?string $circularGroup = null): LengthAwarePaginator
    {
        $search = trim((string) $search);
        $perPage = in_array($perPage, [25, 50, 100], true) ? $perPage : 25;

        $paginator = MedicineVariant::query()
            ->with([
                'medicine.currentProfile',
                'medicine.drugBidAwards:id,medicine_id',
                'medicine.supplierTrackings:id,medicine_id,import_price',
                'packages',
            ])
            ->whereHas('medicine')
            ->when($filter === 'awarded', fn ($query) => $query->whereHas('medicine.drugBidAwards'))
            ->when($filter === 'profile', fn ($query) => $query->whereHas('medicine.currentProfile'))
            ->when($filter === 'supplier-priced' && $allowSupplierPricing, fn ($query) => $query->whereHas('medicine.supplierTrackings', fn ($tracking) => $tracking->whereNotNull('import_price')))
            ->when(filled($circularGroup), fn ($query) => $query->whereHas('medicine', fn ($medicine) => $medicine->where('circular_group', $circularGroup)))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested->where('sku', 'like', "%{$search}%")
                        ->orWhere('strength_text', 'like', "%{$search}%")
                        ->orWhere('presentation_text', 'like', "%{$search}%")
                        ->orWhereHas('medicine', fn ($medicine) => $medicine
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('medicine_code', 'like', "%{$search}%")
                            ->orWhere('active_ingredients', 'like', "%{$search}%")
                            ->orWhere('registration_number', 'like', "%{$search}%")
                            ->orWhere('registration_number_primary', 'like', "%{$search}%"));
                });
            })
            ->leftJoin('pharma_medicines as catalog_medicines', 'catalog_medicines.id', '=', 'pharma_medicine_variants.medicine_id')
            ->select('pharma_medicine_variants.*')
            ->orderByRaw("CASE WHEN catalog_medicines.circular_group IS NULL OR catalog_medicines.circular_group = '' THEN 1 ELSE 0 END")
            ->orderBy('catalog_medicines.circular_group')
            ->orderByRaw("CASE WHEN catalog_medicines.circular_order_number IS NULL OR catalog_medicines.circular_order_number = '' THEN 1 ELSE 0 END")
            ->orderBy('catalog_medicines.circular_order_number')
            ->orderBy('catalog_medicines.name')
            ->orderBy('pharma_medicine_variants.sku')
            ->paginate($perPage, ['*'], 'page', max(1, $page));

        $paginator->setCollection(
            $paginator->getCollection()
                ->map(fn (MedicineVariant $variant) => MedicineCatalogItem::fromVariant($variant))
        );

        return $paginator;
    }

    public function circularGroups(): Collection
    {
        return MedicineVariant::query()
            ->join('pharma_medicines as group_medicines', 'group_medicines.id', '=', 'pharma_medicine_variants.medicine_id')
            ->whereNotNull('group_medicines.circular_group')
            ->where('group_medicines.circular_group', '!=', '')
            ->select('group_medicines.circular_group')
            ->selectRaw('MIN(group_medicines.circular_order_number) as circular_order_number')
            ->groupBy('group_medicines.circular_group')
            ->orderByRaw("CASE WHEN MIN(group_medicines.circular_order_number) IS NULL OR MIN(group_medicines.circular_order_number) = '' THEN 1 ELSE 0 END")
            ->orderByRaw('MIN(group_medicines.circular_order_number)')
            ->orderBy('group_medicines.circular_group')
            ->pluck('group_medicines.circular_group')
            ->values();
    }

    public function filterCounts(bool $includeSupplierPricing = false): array
    {
        $base = MedicineVariant::query()->whereHas('medicine');

        $counts = [
            'all' => (clone $base)->count(),
            'awarded' => (clone $base)->whereHas('medicine.drugBidAwards')->count(),
            'profile' => (clone $base)->whereHas('medicine.currentProfile')->count(),
        ];

        if ($includeSupplierPricing) {
            $counts['supplier-priced'] = (clone $base)
                ->whereHas('medicine.supplierTrackings', fn ($tracking) => $tracking->whereNotNull('import_price'))
                ->count();
        }

        return $counts;
    }

    public function overview(int $variantId, bool $includeSupplierPricing = false): ?array
    {
        $variant = MedicineVariant::query()
            ->with(['medicine.currentProfile', 'medicine.supplierTrackings.partner', 'medicine.drugBidAwards'])
            ->find($variantId);

        if (! $variant || ! $variant->medicine) {
            return null;
        }

        $medicine = $variant->medicine;
        $profile = $medicine->currentProfile;
        $awards = $medicine->drugBidAwards
            ->sortByDesc(fn ($award) => $award->decision_date?->format('Y-m-d') ?? $award->published_at?->format('Y-m-d H:i:s') ?? '')
            ->take(10)
            ->values();

        $suppliers = $medicine->supplierTrackings
            ->sortByDesc(fn ($tracking) => $tracking->working_date?->format('Y-m-d') ?? sprintf('%010d', $tracking->id))
            ->map(function ($tracking) use ($includeSupplierPricing): array {
                $row = [
                    'supplier_name' => $tracking->partner?->name ?: $tracking->supplier_name,
                    'working_date' => $tracking->working_date,
                    'start_date' => $tracking->start_date,
                    'end_date' => $tracking->end_date,
                    'status' => $tracking->status,
                ];

                if ($includeSupplierPricing) {
                    $row['import_price'] = $tracking->import_price;
                    $row['invoice_price'] = $tracking->invoice_price;
                    $row['cost_price'] = $tracking->cost_price;
                }

                return $row;
            })
            ->values();

        return [
            'product' => MedicineCatalogItem::fromVariant($variant),
            'medicine' => [
                'medicine_code' => $medicine->medicine_code,
                'catalog_status' => $medicine->catalog_status,
                'profile_status' => $medicine->profile_status,
                'circular_group' => $medicine->circular_group,
                'therapeutic_group' => $medicine->therapeutic_group,
                'registered_company' => $medicine->registered_company,
                'manufacturing_country' => $medicine->manufacturing_country,
                'shelf_life' => $medicine->shelf_life,
                'visa_validity_date' => $medicine->visa_validity_date,
                'is_special_control' => (bool) $medicine->is_special_control,
            ],
            'profile' => $profile ? [
                'version' => $profile->profile_version,
                'status' => $profile->profile_status,
                'link' => $profile->profile_link,
                'source' => $profile->source,
                'effective_from' => $profile->effective_from,
                'effective_to' => $profile->effective_to,
                'verified_at' => $profile->verified_at,
                'notes' => $profile->notes,
            ] : null,
            'awards' => $awards->map(fn ($award): array => [
                'bidding_notice_code' => $award->bidding_notice_code,
                'investor_name' => $award->investor_name,
                'decision_number' => $award->decision_number,
                'decision_date' => $award->decision_date,
                'quantity' => $award->quantity,
                'unit_price' => $award->winning_price ?: $award->unit_price,
                'winning_company_name' => $award->winning_company_name,
                'lot_no' => $award->lot_no,
                'contract_duration_months' => $award->contract_duration_months,
            ])->values(),
            'suppliers' => $suppliers,
            'supplier_pricing_visible' => $includeSupplierPricing,
        ];
    }

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
