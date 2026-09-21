<?php

namespace Modules\Pharma\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Pharma\Exceptions\DuplicateSupplierTrackingException;
use Modules\Partner\Models\Partner;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Models\OfficialSourceFacility;
use Modules\Pharma\Models\SupplierTracking;
use Modules\Pharma\Services\OfficialFacilityImport\BhxhProvinceCatalog;

class SupplierTrackingService
{
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public function paginate(array $filters = [], int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        $perPage = $this->normalizePerPage($perPage);

        return $this->queryForFilters($filters)
            ->with(['medicine', 'partner', 'facilities'])
            ->latest('id')
            ->paginate($perPage, ['*'], 'page', max(1, $page));
    }

    public function medicineCandidates(string $search = '', ?int $selectedId = null, int $limit = 25): Collection
    {
        $limit = max(1, min(25, $limit));
        $search = trim($search);

        $query = Medicine::query()
            ->select('id', 'name', 'registration_number', 'packaging_specification', 'unit', 'active_ingredients')
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $nested) use ($search): void {
                $nested->where('name', 'like', "%{$search}%")
                    ->orWhere('registration_number', 'like', "%{$search}%")
                    ->orWhere('active_ingredients', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->limit($limit);

        $candidates = $query->get();

        if ($selectedId !== null && ! $candidates->contains('id', $selectedId)) {
            $selected = Medicine::query()
                ->select('id', 'name', 'registration_number', 'packaging_specification', 'unit', 'active_ingredients')
                ->find($selectedId);

            if ($selected) {
                $candidates->prepend($selected);
            }
        }

        return $candidates->unique('id')->values();
    }

    public function supplierCandidates(string $search = '', ?int $selectedId = null, int $limit = 25): Collection
    {
        $limit = max(1, min(25, $limit));
        $search = trim($search);

        $query = Partner::query()
            ->where('status', 'active')
            ->withPartnerType('supplier')
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $nested) use ($search): void {
                $nested->where('name', 'like', "%{$search}%")
                    ->orWhere('tax_code', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->limit($limit);

        $candidates = $query->get(['id', 'name', 'tax_code', 'contact_person']);

        if ($selectedId && ! $candidates->contains('id', $selectedId)) {
            $selected = Partner::query()->find($selectedId, ['id', 'name', 'tax_code', 'contact_person']);
            if ($selected) {
                $candidates->prepend($selected);
            }
        }

        return $candidates->unique('id')->values();
    }

    public function supplierFilterCandidates(string $search = '', ?int $selectedId = null, int $limit = 25): Collection
    {
        $search = trim($search);
        $ids = SupplierTracking::query()->whereNotNull('partner_id')->distinct()->pluck('partner_id');

        $items = Partner::query()
            ->whereIn('id', $ids)
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $nested) use ($search): void {
                $nested->where('name', 'like', "%{$search}%")
                    ->orWhere('tax_code', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->limit(max(1, min(25, $limit)))
            ->get(['id', 'name', 'tax_code']);

        if ($selectedId && ! $items->contains('id', $selectedId)) {
            $selected = Partner::query()->find($selectedId, ['id', 'name', 'tax_code']);
            if ($selected) {
                $items->prepend($selected);
            }
        }

        return $items->unique('id')->values();
    }

    public function medicineFilterCandidates(string $search = '', ?int $selectedId = null, int $limit = 25): Collection
    {
        $search = trim($search);
        $ids = SupplierTracking::query()->whereNotNull('medicine_id')->distinct()->pluck('medicine_id');

        $items = Medicine::query()
            ->whereIn('id', $ids)
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $nested) use ($search): void {
                $nested->where('name', 'like', "%{$search}%")
                    ->orWhere('registration_number', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->limit(max(1, min(25, $limit)))
            ->get(['id', 'name', 'registration_number']);

        if ($selectedId && ! $items->contains('id', $selectedId)) {
            $selected = Medicine::query()->find($selectedId, ['id', 'name', 'registration_number']);
            if ($selected) {
                $items->prepend($selected);
            }
        }

        return $items->unique('id')->values();
    }

    public function facilityCandidates(string $search = '', array $selectedIds = [], int $limit = 25): Collection
    {
        $search = trim($search);
        $query = OfficialSourceFacility::query()->where('is_active', true)
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $nested) use ($search): void {
                $nested->where('facility_name', 'like', "%{$search}%")
                    ->orWhere('external_id', 'like', "%{$search}%")
                    ->orWhere('province_name', 'like', "%{$search}%");
            }))
            ->orderBy('facility_name')->limit(max(1, min(25, $limit)));

        $items = $query->get(['id', 'facility_name', 'external_id', 'province_name']);
        $selected = OfficialSourceFacility::query()->whereIn('id', $selectedIds)
            ->get(['id', 'facility_name', 'external_id', 'province_name']);

        return $selected->concat($items)->unique('id')->values();
    }

    public function distributionRegions(): array
    {
        return app(BhxhProvinceCatalog::class)->regions();
    }

    public function distributionProvincesByRegion(): array
    {
        $catalog = app(BhxhProvinceCatalog::class);
        $grouped = $catalog->provincesByRegion();
        $result = [];

        foreach ($catalog->regions() as $regionCode => $regionLabel) {
            $result[$regionCode] = $grouped[$regionLabel] ?? [];
        }

        return $result;
    }

    public function find(int $id): SupplierTracking
    {
        return SupplierTracking::query()->with(['medicine', 'partner', 'facilities'])->findOrFail($id);
    }

    public function create(array $data): SupplierTracking
    {
        return DB::transaction(function () use ($data): SupplierTracking {
            $prepared = $this->prepare($data);
            $this->guardBusinessKey($prepared);

            $facilityIds = $prepared['facility_ids'] ?? [];
            unset($prepared['facility_ids']);
            $tracking = SupplierTracking::query()->create($prepared);
            $tracking->facilities()->sync($facilityIds);

            return $tracking->load(['medicine', 'partner', 'facilities']);
        });
    }

    public function update(int $id, array $data): SupplierTracking
    {
        return DB::transaction(function () use ($id, $data): SupplierTracking {
            $tracking = $this->find($id);
            $prepared = $this->prepare($data);
            $this->guardBusinessKey($prepared, $tracking->id);
            $facilityIds = $prepared['facility_ids'] ?? [];
            unset($prepared['facility_ids']);
            $tracking->update($prepared);
            $tracking->facilities()->sync($facilityIds);

            return $tracking->refresh()->load(['medicine', 'partner', 'facilities']);
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(fn () => $this->find($id)->delete());
    }

    public function deleteMany(array $ids): void
    {
        $ids = collect($ids)->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();

        if ($ids === []) {
            return;
        }

        DB::transaction(fn () => SupplierTracking::query()->whereIn('id', $ids)->delete());
    }

    public function previewCalculate(array $data): array
    {
        return $this->calculate($data);
    }

    public function auditDuplicateBusinessKeys(): Collection
    {
        return SupplierTracking::query()
            ->select('medicine_id', 'supplier_name_normalized', 'working_date', DB::raw('COUNT(*) as duplicate_count'))
            ->whereNotNull('working_date')
            ->whereNotNull('supplier_name_normalized')
            ->groupBy('medicine_id', 'supplier_name_normalized', 'working_date')
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('duplicate_count')
            ->get();
    }

    private function queryForFilters(array $filters): Builder
    {
        return SupplierTracking::query()
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(function (Builder $nested) use ($search): void {
                $nested->where('supplier_name', 'like', "%{$search}%")
                    ->orWhere('supplier_representative', 'like', "%{$search}%")
                    ->orWhere('area', 'like', "%{$search}%")
                    ->orWhereHas('medicine', fn (Builder $medicine) => $medicine
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('registration_number', 'like', "%{$search}%"));
            }))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['partner_id'] ?? null, fn (Builder $query, $partnerId) => $query->where('partner_id', (int) $partnerId))
            ->when($filters['medicine_id'] ?? null, fn (Builder $query, $medicineId) => $query->where('medicine_id', (int) $medicineId));
    }

    private function prepare(array $data): array
    {
        if (! empty($data['partner_id'])) {
            $partner = Partner::query()->findOrFail((int) $data['partner_id']);
            $data['supplier_name'] = $partner->name;
            $data['supplier_representative'] = $partner->contact_person;
        }

        $scope = $data['distribution_scope'] ?? 'all';
        $data['distribution_regions'] = $scope === 'regions' ? array_values($data['distribution_regions'] ?? []) : null;
        $data['distribution_provinces'] = $scope === 'regions' ? array_values($data['distribution_provinces'] ?? []) : null;
        $data['facility_ids'] = $scope === 'facilities'
            ? OfficialSourceFacility::query()->where('is_active', true)->whereIn('id', $data['facility_ids'] ?? [])->pluck('id')->all()
            : [];

        // A commercial condition is valid with Supplier + supplier cost only.
        // Everything else is enrichment and receives safe persistence defaults.
        $data['working_date'] = $data['working_date'] ?: null;
        $data['invoice_price'] = $data['invoice_price'] === '' || $data['invoice_price'] === null ? 0 : $data['invoice_price'];
        $data['committed_quantity'] = $data['committed_quantity'] === '' ? null : ($data['committed_quantity'] ?? null);
        $data['deposit_amount'] = $data['deposit_amount'] === '' ? null : ($data['deposit_amount'] ?? null);
        $data['start_date'] = $data['start_date'] ?: null;
        $data['end_date'] = $data['end_date'] ?: null;
        $data['status'] = $data['status'] ?: 'active';

        $data['supplier_name'] = Str::of((string) ($data['supplier_name'] ?? ''))->trim()->squish()->toString();
        $data['supplier_name_normalized'] = $this->normalizeSupplierName($data['supplier_name']);

        return $this->calculate($data);
    }

    private function guardBusinessKey(array $data, ?int $ignoreId = null): void
    {
        if (empty($data['working_date']) || empty($data['medicine_id'])) {
            return;
        }

        $query = SupplierTracking::query()
            ->where('medicine_id', (int) $data['medicine_id'])
            ->whereDate('working_date', $data['working_date']);

        if (! empty($data['partner_id'])) {
            $query->where('partner_id', (int) $data['partner_id']);
        } elseif (! empty($data['supplier_name_normalized'])) {
            $query->whereNull('partner_id')->where('supplier_name_normalized', $data['supplier_name_normalized']);
        } else {
            return;
        }

        if ($ignoreId !== null) {
            $query->where((new SupplierTracking)->getKeyName(), '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw new DuplicateSupplierTrackingException(
                'A Supplier Tracking record already exists for this Medicine, Supplier and Working Date.'
            );
        }
    }

    private function calculate(array $data): array
    {
        $importPrice = $this->toFloat($data['import_price'] ?? 0);
        $sellingPrice = $this->toFloat($data['selling_price'] ?? 0);
        $invoicePrice = $this->toFloat($data['invoice_price'] ?? 0);
        $differencePercent = $this->toFloat($data['invoice_difference_percent'] ?? 0);
        $differenceAmount = $invoicePrice - $importPrice;
        $differenceFee = $differenceAmount * $differencePercent / 100;
        $costPrice = $importPrice + $differenceFee;

        $data['invoice_difference_amount'] = round($differenceAmount, 2);
        $data['invoice_difference_fee'] = round($differenceFee, 2);
        $data['cost_price'] = round($costPrice, 2);
        $data['gross_profit_percent'] = round(
            $sellingPrice > 0 ? (($sellingPrice - $costPrice) / $sellingPrice) * 100 : 0,
            2
        );

        return $data;
    }

    private function normalizeSupplierName(?string $name): ?string
    {
        $normalized = Str::of((string) $name)->trim()->squish()->lower()->toString();

        return $normalized === '' ? null : $normalized;
    }

    private function normalizePerPage(int $value): int
    {
        return in_array($value, self::PER_PAGE_OPTIONS, true) ? $value : 10;
    }

    private function toFloat(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return (float) str_replace(',', '', (string) $value);
    }
}
