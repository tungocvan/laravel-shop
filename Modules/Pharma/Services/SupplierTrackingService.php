<?php

namespace Modules\Pharma\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Pharma\Exceptions\DuplicateSupplierTrackingException;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Models\SupplierTracking;

class SupplierTrackingService
{
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public function paginate(array $filters = [], int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        $perPage = $this->normalizePerPage($perPage);

        return $this->queryForFilters($filters)
            ->with('medicine')
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

    public function find(int $id): SupplierTracking
    {
        return SupplierTracking::query()->with('medicine')->findOrFail($id);
    }

    public function create(array $data): SupplierTracking
    {
        return DB::transaction(function () use ($data): SupplierTracking {
            $prepared = $this->prepare($data);
            $this->guardBusinessKey($prepared);

            return SupplierTracking::query()->create($prepared);
        });
    }

    public function update(int $id, array $data): SupplierTracking
    {
        return DB::transaction(function () use ($id, $data): SupplierTracking {
            $tracking = $this->find($id);
            $prepared = $this->prepare($data);
            $this->guardBusinessKey($prepared, $tracking->id);
            $tracking->update($prepared);

            return $tracking->refresh()->load('medicine');
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
            ->when($filters['working_date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('working_date', '>=', $date))
            ->when($filters['working_date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('working_date', '<=', $date));
    }

    private function prepare(array $data): array
    {
        $data['supplier_name'] = Str::of((string) ($data['supplier_name'] ?? ''))->trim()->squish()->toString();
        $data['supplier_name_normalized'] = $this->normalizeSupplierName($data['supplier_name']);

        return $this->calculate($data);
    }

    private function guardBusinessKey(array $data, ?int $ignoreId = null): void
    {
        if (empty($data['working_date']) || empty($data['supplier_name_normalized']) || empty($data['medicine_id'])) {
            return;
        }

        $exists = SupplierTracking::query()
            ->where('medicine_id', (int) $data['medicine_id'])
            ->where('supplier_name_normalized', $data['supplier_name_normalized'])
            ->whereDate('working_date', $data['working_date'])
            ->when($ignoreId !== null, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->exists();

        if ($exists) {
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
