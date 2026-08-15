<?php

namespace Modules\Invoices\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class InvoicePartnerReportService
{
    public function paginate(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $query = $this->groupedQuery($filters);
        [$column, $direction] = match ($filters['sort'] ?? 'sold_desc') {
            'purchase_desc' => ['purchase_total', 'desc'],
            'invoice_desc' => ['invoice_count', 'desc'],
            'vat_desc' => ['vat_total', 'desc'],
            'net_desc' => ['net_total', 'desc'],
            'partner_asc' => ['partner_name', 'asc'],
            'partner_desc' => ['partner_name', 'desc'],
            default => ['sold_total', 'desc'],
        };

        return $query->orderBy($column, $direction)
            ->orderBy('partner_name')
            ->paginate(in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25);
    }

    public function summary(array $filters): array
    {
        $row = $this->summaryQuery($this->baseQuery($filters))->first();

        return $this->normalizeSummary($row);
    }

    public function partnerDetail(array $filters, string $name, string $taxCode): array
    {
        $query = $this->baseQuery($filters);
        $name = trim($name);
        $taxCode = trim($taxCode);

        $name === 'Không xác định'
            ? $query->where(fn (Builder $q) => $q->whereNull('name')->orWhere('name', ''))
            : $query->where('name', $name);

        if ($taxCode === '-') {
            $query->where(fn (Builder $q) => $q->whereNull('tax_code')->orWhere('tax_code', ''));
        } else {
            $query->where('tax_code', $taxCode);
        }

        $summary = $this->normalizeSummary($this->summaryQuery($query)->first());

        return array_merge($summary, [
            'partner_name' => $name,
            'partner_tax_code' => $taxCode,
            'total_difference' => (float) $summary['sold_total'] - (float) $summary['purchase_total'],
            'vat_difference' => (float) $summary['sold_vat'] - (float) $summary['purchase_vat'],
        ]);
    }

    public function exportRows(array $filters)
    {
        return $this->groupedQuery($filters)->orderByDesc('sold_total')->orderBy('partner_name')->get();
    }

    private function groupedQuery(array $filters): Builder
    {
        return $this->baseQuery($filters)
            ->selectRaw("COALESCE(NULLIF(name, ''), 'Không xác định') as partner_name")
            ->selectRaw("COALESCE(NULLIF(tax_code, ''), '-') as partner_tax_code")
            ->selectRaw('COUNT(*) as invoice_count')
            ->selectRaw("SUM(CASE WHEN invoice_type = 'sold' THEN 1 ELSE 0 END) as sold_count")
            ->selectRaw("SUM(CASE WHEN invoice_type = 'purchase' THEN 1 ELSE 0 END) as purchase_count")
            ->selectRaw("COALESCE(SUM(CASE WHEN invoice_type = 'sold' THEN total_amount ELSE 0 END), 0) as sold_total")
            ->selectRaw("COALESCE(SUM(CASE WHEN invoice_type = 'purchase' THEN total_amount ELSE 0 END), 0) as purchase_total")
            ->selectRaw("COALESCE(SUM(CASE WHEN invoice_type = 'sold' THEN vat_amount ELSE 0 END), 0) as sold_vat")
            ->selectRaw("COALESCE(SUM(CASE WHEN invoice_type = 'purchase' THEN vat_amount ELSE 0 END), 0) as purchase_vat")
            ->selectRaw('COALESCE(SUM(vat_amount), 0) as vat_total')
            ->selectRaw("COALESCE(SUM(CASE WHEN invoice_type = 'sold' THEN total_amount ELSE -total_amount END), 0) as net_total")
            ->groupBy('name', 'tax_code');
    }

    private function summaryQuery(Builder $query): Builder
    {
        return $query
            ->selectRaw('COUNT(*) as invoice_count')
            ->selectRaw("SUM(CASE WHEN invoice_type = 'sold' THEN 1 ELSE 0 END) as sold_count")
            ->selectRaw("SUM(CASE WHEN invoice_type = 'purchase' THEN 1 ELSE 0 END) as purchase_count")
            ->selectRaw("COALESCE(SUM(CASE WHEN invoice_type = 'sold' THEN total_amount ELSE 0 END), 0) as sold_total")
            ->selectRaw("COALESCE(SUM(CASE WHEN invoice_type = 'purchase' THEN total_amount ELSE 0 END), 0) as purchase_total")
            ->selectRaw("COALESCE(SUM(CASE WHEN invoice_type = 'sold' THEN vat_amount ELSE 0 END), 0) as sold_vat")
            ->selectRaw("COALESCE(SUM(CASE WHEN invoice_type = 'purchase' THEN vat_amount ELSE 0 END), 0) as purchase_vat");
    }

    private function normalizeSummary(?object $row): array
    {
        return [
            'invoice_count' => (int) ($row?->invoice_count ?? 0),
            'sold_count' => (int) ($row?->sold_count ?? 0),
            'purchase_count' => (int) ($row?->purchase_count ?? 0),
            'sold_total' => $row?->sold_total ?? 0,
            'purchase_total' => $row?->purchase_total ?? 0,
            'sold_vat' => $row?->sold_vat ?? 0,
            'purchase_vat' => $row?->purchase_vat ?? 0,
        ];
    }

    private function baseQuery(array $filters): Builder
    {
        $query = DB::table('invoices');

        if (filled($filters['invoice_type'] ?? null)) $query->where('invoice_type', $filters['invoice_type']);
        if (filled($filters['name'] ?? null)) $query->where('name', $filters['name']);
        if (filled($filters['tax_code'] ?? null)) $query->where('tax_code', $filters['tax_code']);
        if (filled($filters['issued_date_from'] ?? null)) $query->whereDate('issued_date', '>=', $filters['issued_date_from']);
        if (filled($filters['issued_date_to'] ?? null)) $query->whereDate('issued_date', '<=', $filters['issued_date_to']);

        return $query;
    }
}
