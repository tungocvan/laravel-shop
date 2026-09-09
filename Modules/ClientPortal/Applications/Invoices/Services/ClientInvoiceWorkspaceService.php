<?php

namespace Modules\ClientPortal\Applications\Invoices\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Invoices\Jobs\ProcessGdtInvoicesJob;
use Modules\Invoices\Services\InvoicePartnerReportService;
use Modules\Invoices\Services\InvoicePdfService;
use Modules\Invoices\Services\InvoiceService;

final class ClientInvoiceWorkspaceService
{
    public function __construct(
        private readonly InvoiceService $invoices,
        private readonly InvoicePdfService $pdf,
        private readonly InvoicePartnerReportService $partnerReports,
    ) {}

    public function dashboardData(Request $request): array
    {
        $year = max(2000, min(2100, (int) $request->integer('year', (int) now()->format('Y'))));
        $requestedMonth = $request->query('month');
        $month = filter_var($requestedMonth, FILTER_VALIDATE_INT) !== false
            ? max(1, min(12, (int) $requestedMonth))
            : null;

        $periodDate = now()->setDate($year, $month ?? 1, 1);
        $periodFilters = $this->periodFilters($year, $month);
        $yearFilters = $this->periodFilters($year, null);
        $sameMonth = $month ?? (int) now()->format('m');
        $sameMonthFilters = $this->periodFilters($year, $sameMonth);

        $stats = $this->invoices->statistics($periodFilters);
        $yearStats = $month === null ? $stats : $this->invoices->statistics($yearFilters);
        $currentMonthStats = $this->invoices->statistics($sameMonthFilters);
        $pdfMissing = $this->invoices->statistics(array_merge($periodFilters, ['pdf_status' => 'missing']))['count'];
        $pdfErrors = $this->invoices->statistics(array_merge($periodFilters, ['pdf_status' => 'error']))['count'];
        $monthlyPerformance = $this->invoices->monthlyPerformance($year);
        $monthlyMax = max(1, (float) collect($monthlyPerformance)->max(fn (array $row) => max((float) $row['sold_total'], (float) $row['purchase_total'])));
        $dashboard = $this->invoices->dashboard();
        $yearlyPerformance = collect($dashboard['yearly'] ?? [])
            ->take(6)
            ->values()
            ->map(function (array $row, int $index) use ($dashboard): array {
                $next = $dashboard['yearly'][$index + 1] ?? null;
                $current = (float) ($row['sold_total'] ?? 0);
                $previous = (float) ($next['sold_total'] ?? 0);
                $growth = $previous > 0 ? (($current - $previous) / $previous) * 100 : null;

                return array_merge($row, ['sold_growth' => $growth]);
            })
            ->all();

        $years = collect($this->invoices->years())
            ->push($year)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        $previousYear = $year - 1;
        $previousYearStats = $this->invoices->statistics($this->periodFilters($previousYear, null));
        $previousMonthDate = now()->setDate($year, $sameMonth, 1)->subMonth();
        $previousMonthStats = $this->invoices->statistics($this->periodFilters((int) $previousMonthDate->format('Y'), (int) $previousMonthDate->format('m')));
        $previousYearMonthStats = $this->invoices->statistics($this->periodFilters($previousYear, $sameMonth));
        $growth = static function ($current, $previous): ?float {
            $previous = (float) $previous;

            return $previous > 0 ? (((float) $current - $previous) / $previous) * 100 : null;
        };
        $classifiedCount = (int) ($stats['sold_count'] ?? 0) + (int) ($stats['purchase_count'] ?? 0);

        return [
            'period' => $month === null ? 'Năm '.$year : sprintf('Tháng %02d/%d', $month, $year),
            'periodScope' => $month === null ? 'year' : 'month',
            'periodDate' => $periodDate,
            'selectedYear' => $year,
            'previousYear' => $previousYear,
            'selectedMonth' => $month,
            'years' => $years,
            'stats' => $stats,
            'yearStats' => $yearStats,
            'currentMonthStats' => $currentMonthStats,
            'currentMonthLabel' => sprintf('Tháng %02d/%d', $sameMonth, $year),
            'previousMonthLabel' => sprintf('Tháng %02d/%d', (int) $previousMonthDate->format('m'), (int) $previousMonthDate->format('Y')),
            'yearGrowth' => $growth($yearStats['sold_amount'] ?? 0, $previousYearStats['sold_amount'] ?? 0),
            'currentMonthGrowth' => $growth($currentMonthStats['sold_amount'] ?? 0, $previousMonthStats['sold_amount'] ?? 0),
            'currentMonthYearOverYearGrowth' => $growth($currentMonthStats['sold_amount'] ?? 0, $previousYearMonthStats['sold_amount'] ?? 0),
            'unclassifiedInvoiceCount' => max(0, (int) ($stats['count'] ?? 0) - $classifiedCount),
            'pdfMissing' => $pdfMissing,
            'pdfErrors' => $pdfErrors,
            'pdfComplete' => max(0, (int) ($stats['count'] ?? 0) - $pdfMissing - $pdfErrors),
            'monthlyPerformance' => $monthlyPerformance,
            'monthlyMax' => $monthlyMax,
            'yearlyPerformance' => $yearlyPerformance,
        ];
    }

    public function partnerReportData(Request $request): array
    {
        $year = max(2000, min(2100, (int) $request->integer('year', (int) now()->format('Y'))));
        $requestedMonth = $request->query('month');
        $month = filter_var($requestedMonth, FILTER_VALIDATE_INT) !== false
            ? max(1, min(12, (int) $requestedMonth))
            : null;
        $type = in_array($request->query('type'), ['sold', 'purchase'], true)
            ? $request->query('type')
            : null;
        $requestedSort = (string) $request->query('sort', '');
        $validSorts = [
            'sold_desc',
            'purchase_desc',
            'invoice_desc',
            'vat_desc',
            'net_desc',
            'partner_asc',
            'partner_desc',
        ];
        $sort = in_array($requestedSort, $validSorts, true)
            ? $requestedSort
            : ($type === 'purchase' ? 'purchase_desc' : 'sold_desc');
        $perPage = (int) $request->integer('per_page', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;
        $date = now()->setDate($year, $month ?? 1, 1);

        $filters = [
            'invoice_type' => $type,
            'partner' => trim((string) $request->query('partner', '')),
            'issued_date_from' => ($month === null ? $date->copy()->startOfYear() : $date->copy()->startOfMonth())->toDateString(),
            'issued_date_to' => ($month === null ? $date->copy()->endOfYear() : $date->copy()->endOfMonth())->toDateString(),
            'sort' => $sort,
        ];

        $partners = $this->partnerReports->paginate($filters, $perPage)->withQueryString();
        $summary = $this->partnerReports->summary($filters);
        $topSold = $this->partnerReports->paginate(array_merge($filters, ['sort' => 'sold_desc']), 10)
            ->getCollection()
            ->take(5)
            ->values();
        $topPurchase = $this->partnerReports->paginate(array_merge($filters, ['sort' => 'purchase_desc']), 10)
            ->getCollection()
            ->take(5)
            ->values();

        $detailName = trim((string) $request->query('detail_name', ''));
        $detailTaxCode = trim((string) $request->query('detail_tax_code', ''));
        $partnerDetail = null;
        if ($detailName !== '' && $detailTaxCode !== '') {
            $partnerDetail = $this->partnerReports->partnerDetail(
                array_merge($filters, ['partner' => null, 'sort' => null]),
                $detailName,
                $detailTaxCode,
            );
        }

        return [
            'partnerFilters' => [
                'year' => $year,
                'month' => $month,
                'type' => $type,
                'partner' => $filters['partner'],
                'sort' => $sort,
                'per_page' => $perPage,
            ],
            'partnerPeriod' => $month === null ? 'Năm '.$year : sprintf('Tháng %02d/%d', $month, $year),
            'partnerYears' => collect($this->invoices->years())->push($year)->unique()->sortDesc()->values()->all(),
            'partnerOptions' => $this->invoices->filterOptions([
                'invoice_type' => $type,
                'issued_date_from' => $filters['issued_date_from'],
                'issued_date_to' => $filters['issued_date_to'],
                'tax_rate' => 'all',
                'pdf_status' => 'all',
            ])['names'],
            'partners' => $partners,
            'partnerSummary' => $summary,
            'partnerCount' => $partners->total(),
            'partnerTopSold' => $topSold,
            'partnerTopPurchase' => $topPurchase,
            'partnerDetail' => $partnerDetail,
        ];
    }

    public function listData(Request $request): array
    {
        $filters = $this->filters($request);
        $perPage = (int) $request->integer('per_page', 25);
        $year = max(2000, min(2100, (int) $request->integer('year', (int) now()->format('Y'))));
        $requestedMonth = $request->has('month') ? $request->query('month') : (int) now()->format('m');
        $month = filter_var($requestedMonth, FILTER_VALIDATE_INT) !== false
            ? max(1, min(12, (int) $requestedMonth))
            : null;
        $paginator = $this->invoices->paginate($filters, $perPage)->withQueryString();

        return [
            'filters' => $filters,
            'selectedYear' => $year,
            'selectedMonth' => $month,
            'perPage' => $perPage,
            'invoices' => $paginator,
            'stats' => $this->invoices->statistics($filters),
            'partnerOptions' => $this->invoices->filterOptions(array_merge($filters, ['partner' => null]))['names'],
            'pdfStatuses' => collect($paginator->items())
                ->mapWithKeys(fn ($invoice) => [$invoice->getKey() => $this->pdf->statusForInvoice($invoice)])
                ->all(),
        ];
    }

    public function filters(Request $request): array
    {
        $year = max(2000, min(2100, (int) $request->integer('year', (int) now()->format('Y'))));
        $requestedMonth = $request->has('month') ? $request->query('month') : (int) now()->format('m');
        $month = filter_var($requestedMonth, FILTER_VALIDATE_INT) !== false
            ? max(1, min(12, (int) $requestedMonth))
            : null;
        $period = $this->periodFilters($year, $month);

        return [
            'search' => trim((string) $request->query('search', '')),
            'partner' => trim((string) $request->query('partner', '')),
            'invoice_type' => in_array($request->query('invoice_type'), ['sold', 'purchase'], true)
                ? $request->query('invoice_type')
                : null,
            'issued_date_from' => $period['issued_date_from'],
            'issued_date_to' => $period['issued_date_to'],
            'tax_rate' => 'all',
            'pdf_status' => 'all',
            'sort' => in_array((string) $request->query('sort', 'date_desc'), ['date_desc', 'date_asc', 'amount_desc', 'amount_asc', 'partner_asc', 'partner_desc'], true)
                ? (string) $request->query('sort', 'date_desc')
                : 'date_desc',
        ];
    }

    public function exportRecords(Request $request)
    {
        $selected = collect($request->input('selected', []))
            ->filter(fn ($id) => filter_var($id, FILTER_VALIDATE_INT) !== false && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return $selected === []
            ? $this->invoices->filter($this->filters($request))
            : $this->invoices->selected($selected);
    }

    public function dispatchSync(string $start, string $end, string $direction): string
    {
        $syncId = (string) Str::uuid();
        $key = 'invoices:gdt-sync:'.$syncId;

        Cache::put($key, [
            'state' => 'queued',
            'message' => 'Đã xếp hàng đồng bộ hóa đơn.',
            'logs' => ['['.now()->format('H:i:s').'] Đã xếp hàng đồng bộ hóa đơn.'],
            'start' => $start,
            'end' => $end,
            'direction' => $direction,
            'created_at' => now()->toIso8601String(),
        ], now()->addHours(24));

        ProcessGdtInvoicesJob::dispatch($start, $end, $direction === 'purchase', $syncId);

        return $syncId;
    }

    public function syncStatus(?string $syncId): ?array
    {
        if (! $syncId || ! Str::isUuid($syncId)) {
            return null;
        }

        $status = Cache::get('invoices:gdt-sync:'.$syncId);

        return is_array($status) ? $status : null;
    }

    private function periodFilters(int $year, ?int $month): array
    {
        $date = now()->setDate($year, $month ?? 1, 1);

        return [
            'issued_date_from' => ($month === null ? $date->copy()->startOfYear() : $date->copy()->startOfMonth())->toDateString(),
            'issued_date_to' => ($month === null ? $date->copy()->endOfYear() : $date->copy()->endOfMonth())->toDateString(),
            'tax_rate' => 'all',
            'pdf_status' => 'all',
        ];
    }
}
