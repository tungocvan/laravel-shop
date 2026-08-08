<?php

namespace Modules\Invoices\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Invoices\Models\Invoices;

class InvoiceService
{
    public function paginate(array $filters, string|int $perPage = 10): LengthAwarePaginator|Collection
    {
        $query = $this->filteredQuery($filters)->orderByDesc('issued_date');

        return $perPage === 'All' ? $query->get() : $query->paginate((int) $perPage);
    }

    public function filterOptions(array $filters): array
    {
        $query = $this->filteredQuery($filters, false);

        return [
            'names' => (clone $query)->whereNotNull('name')->distinct()->orderBy('name')->pluck('name')->all(),
            'tax_codes' => (clone $query)->whereNotNull('tax_code')->distinct()->orderBy('tax_code')->pluck('tax_code')->all(),
        ];
    }

    public function statistics(array $filters): array
    {
        $query = $this->filteredQuery($filters);
        $byRate = [];

        foreach ([5, 8, 10] as $rate) {
            $byRate[$rate] = (clone $query)->where('tax_rate', $rate)->sum('total_amount');
        }

        $byRate['other'] = (clone $query)
            ->whereNotNull('tax_rate')
            ->whereNotIn('tax_rate', [5, 8, 10])
            ->sum('total_amount');

        return [
            'count' => (clone $query)->count(),
            'total_amount' => (clone $query)->sum('total_amount'),
            'vat_amount' => (clone $query)->sum('vat_amount'),
            'by_tax_rate' => $byRate,
        ];
    }

    public function dashboard(): array
    {
        return [
            'sold_amount' => Invoices::query()->where('invoice_type', 'sold')->sum('total_amount'),
            'purchase_amount' => Invoices::query()->where('invoice_type', 'purchase')->sum('total_amount'),
            'sold_customers' => Invoices::query()->where('invoice_type', 'sold')->distinct()->count('name'),
            'purchase_customers' => Invoices::query()->where('invoice_type', 'purchase')->distinct()->count('name'),
            'yearly' => Invoices::query()->selectRaw(
                'YEAR(issued_date) as year,
                SUM(CASE WHEN invoice_type="sold" THEN total_amount ELSE 0 END) as sold_total,
                SUM(CASE WHEN invoice_type="purchase" THEN total_amount ELSE 0 END) as purchase_total'
            )->groupBy('year')->orderByDesc('year')->get()->toArray(),
        ];
    }

    public function selected(array $ids): Collection
    {
        return Invoices::query()->whereIn('id', $ids)->get();
    }

    public function filter(array $filters = [], bool $returnBuilder = false): Builder|Collection
    {
        $query = $this->filteredQuery($filters);

        return $returnBuilder ? $query : $query->get();
    }

    private function filteredQuery(array $filters, bool $includeTaxRate = true): Builder
    {
        $query = Invoices::query();

        foreach ([
            'lookup_code',
            'symbol',
            'invoice_number',
            'type',
            'tax_code',
            'name',
            'address',
            'email',
            'phone',
        ] as $field) {
            if (filled($filters[$field] ?? null)) {
                $query->where($field, 'like', '%'.$filters[$field].'%');
            }
        }

        if (filled($filters['invoice_type'] ?? null)) {
            $query->where('invoice_type', strtolower($filters['invoice_type']));
        }

        if (filled($filters['issued_date_from'] ?? null)) {
            $query->whereDate('issued_date', '>=', $filters['issued_date_from']);
        }

        if (filled($filters['issued_date_to'] ?? null)) {
            $query->whereDate('issued_date', '<=', $filters['issued_date_to']);
        }

        if ($includeTaxRate && filled($filters['tax_rate'] ?? null) && $filters['tax_rate'] !== 'all') {
            $filters['tax_rate'] === 'other'
                ? $query->whereNotNull('tax_rate')->whereNotIn('tax_rate', [5, 8, 10])
                : $query->where('tax_rate', $filters['tax_rate']);
        }

        return $query;
    }
}
