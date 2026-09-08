<?php

namespace Modules\ClientPortal\Applications\Invoices\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Invoices\Jobs\ProcessGdtInvoicesJob;
use Modules\Invoices\Services\InvoicePdfService;
use Modules\Invoices\Services\InvoiceService;

final class ClientInvoiceWorkspaceService
{
    public function __construct(
        private readonly InvoiceService $invoices,
        private readonly InvoicePdfService $pdf,
    ) {}

    public function dashboardData(Request $request): array
    {
        $year = max(2000, min(2100, (int) $request->integer('year', (int) now()->format('Y'))));
        $requestedMonth = $request->query('month');
        $month = filter_var($requestedMonth, FILTER_VALIDATE_INT) !== false
            ? max(1, min(12, (int) $requestedMonth))
            : null;

        $from = now()->setDate($year, $month ?? 1, 1);
        $filters = [
            'issued_date_from' => ($month === null ? $from->copy()->startOfYear() : $from->copy()->startOfMonth())->toDateString(),
            'issued_date_to' => ($month === null ? $from->copy()->endOfYear() : $from->copy()->endOfMonth())->toDateString(),
            'tax_rate' => 'all',
            'pdf_status' => 'all',
        ];

        $years = collect($this->invoices->years())
            ->push($year)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        return [
            'period' => $month === null ? 'Năm '.$year : sprintf('Tháng %02d/%d', $month, $year),
            'periodScope' => $month === null ? 'year' : 'month',
            'selectedYear' => $year,
            'selectedMonth' => $month,
            'years' => $years,
            'stats' => $this->invoices->statistics($filters),
            'pdfMissing' => $this->invoices->statistics(array_merge($filters, ['pdf_status' => 'missing']))['count'],
            'pdfErrors' => $this->invoices->statistics(array_merge($filters, ['pdf_status' => 'error']))['count'],
        ];
    }

    public function listData(Request $request): array
    {
        $filters = $this->filters($request);
        $perPage = (int) $request->integer('per_page', 25);
        $paginator = $this->invoices->paginate($filters, $perPage)->withQueryString();

        return [
            'filters' => $filters,
            'perPage' => $perPage,
            'invoices' => $paginator,
            'stats' => $this->invoices->statistics($filters),
            'pdfStatuses' => collect($paginator->items())
                ->mapWithKeys(fn ($invoice) => [$invoice->getKey() => $this->pdf->statusForInvoice($invoice)])
                ->all(),
        ];
    }

    public function filters(Request $request): array
    {
        $month = (int) $request->integer('month', (int) now()->format('m'));
        $year = (int) $request->integer('year', (int) now()->format('Y'));
        $month = max(1, min(12, $month));
        $year = max(2000, min(2100, $year));

        $from = now()->setDate($year, $month, 1)->startOfMonth();

        return [
            'search' => trim((string) $request->query('search', '')),
            'invoice_type' => in_array($request->query('invoice_type'), ['sold', 'purchase'], true)
                ? $request->query('invoice_type')
                : null,
            'issued_date_from' => $from->toDateString(),
            'issued_date_to' => $from->copy()->endOfMonth()->toDateString(),
            'tax_rate' => in_array((string) $request->query('tax_rate', 'all'), ['all', '5', '8', '10', 'other'], true)
                ? (string) $request->query('tax_rate', 'all')
                : 'all',
            'pdf_status' => in_array((string) $request->query('pdf_status', 'all'), ['all', 'available', 'missing', 'error'], true)
                ? (string) $request->query('pdf_status', 'all')
                : 'all',
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
}
