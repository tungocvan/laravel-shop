<?php

namespace Modules\Pharma\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Models\SupplierTracking;

class SupplierTrackingService
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->queryForFilters($filters)
            ->with('medicine')
            ->latest()
            ->paginate($perPage);
    }

    public function medicinesForSelect(): Collection
    {
        return Medicine::query()
            ->select('id', 'name', 'registration_number', 'packaging_specification', 'unit')
            ->orderBy('name')
            ->get();
    }

    public function find(int $id): SupplierTracking
    {
        return SupplierTracking::query()->with('medicine')->findOrFail($id);
    }

    public function create(array $data): SupplierTracking
    {
        return DB::transaction(fn () => SupplierTracking::query()->create($this->calculate($data)));
    }

    public function update(int $id, array $data): SupplierTracking
    {
        return DB::transaction(function () use ($id, $data) {
            $tracking = $this->find($id);
            $tracking->update($this->calculate($data));

            return $tracking;
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(fn () => $this->find($id)->delete());
    }

    public function deleteMany(array $ids): void
    {
        DB::transaction(fn () => SupplierTracking::query()->whereIn('id', $ids)->delete());
    }

    public function getFilteredIds(array $filters = []): Collection
    {
        return $this->queryForFilters($filters)->pluck('id');
    }

    public function previewCalculate(array $data): array
    {
        return $this->calculate($data);
    }

    private function queryForFilters(array $filters): Builder
    {
        return SupplierTracking::query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($nested) => $nested
                ->where('supplier_name', 'like', "%{$search}%")
                ->orWhere('supplier_representative', 'like', "%{$search}%")
                ->orWhere('area', 'like', "%{$search}%")
                ->orWhereHas('medicine', fn ($medicine) => $medicine
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('registration_number', 'like', "%{$search}%"))))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status));
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

    private function toFloat(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return (float) str_replace(',', '', (string) $value);
    }
}
