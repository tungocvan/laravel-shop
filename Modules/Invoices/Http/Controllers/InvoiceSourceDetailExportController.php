<?php

namespace Modules\Invoices\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Invoices\Models\Invoices;
use Modules\Invoices\Models\InvoiceSourceRecord;
use Modules\Invoices\Services\InvoiceSourceDetailExportService;

final class InvoiceSourceDetailExportController
{
    public function __invoke(Request $request, InvoiceSourceDetailExportService $exporter)
    {
        $selectedIds = collect((array) $request->input('source_ids', []))
            ->map(fn ($id) => filter_var($id, FILTER_VALIDATE_INT))
            ->filter(fn ($id) => $id !== false && $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $query = InvoiceSourceRecord::query()
            ->where('provider', 'gdt')
            ->with('invoice');

        if ($selectedIds->isNotEmpty()) {
            $query->whereIn('id', $selectedIds);
        } else {
            $invoiceType = (string) $request->input('invoice_type', 'purchase');
            $year = (string) $request->input('year', 'all');
            $month = (string) $request->input('month', 'all');
            $partner = trim((string) $request->input('partner', ''));
            $search = trim((string) $request->input('search', ''));
            $detailStatus = (string) $request->input('detail_status', 'all');
            $businessClassification = (string) $request->input('business_classification', 'all');

            $query->whereHas('invoice', function ($invoiceQuery) use ($invoiceType, $year, $month, $partner, $search): void {
                if (in_array($invoiceType, ['purchase', 'sold'], true)) {
                    $invoiceQuery->where('invoice_type', $invoiceType);
                }
                if ($year !== 'all' && filter_var($year, FILTER_VALIDATE_INT) !== false) {
                    $invoiceQuery->whereYear('issued_date', (int) $year);
                }
                if ($month !== 'all' && filter_var($month, FILTER_VALIDATE_INT) !== false) {
                    $invoiceQuery->whereMonth('issued_date', (int) $month);
                }
                if ($partner !== '') {
                    $invoiceQuery->where('name', $partner);
                }
                if ($search !== '') {
                    $invoiceQuery->where(function ($searchQuery) use ($search): void {
                        $searchQuery->where('invoice_number', 'like', "%{$search}%")
                            ->orWhere('symbol', 'like', "%{$search}%")
                            ->orWhere('tax_code', 'like', "%{$search}%")
                            ->orWhere('lookup_code', 'like', "%{$search}%");
                    });
                }
            });

            if (in_array($detailStatus, ['READY', 'MISSING', 'ERROR', 'FETCHING'], true)) {
                $query->where('detail_status', $detailStatus);
            }
            if ($businessClassification !== 'all' && in_array($businessClassification, InvoiceSourceRecord::CLASSIFICATIONS, true)) {
                $query->where('business_classification', $businessClassification);
            }
        }

        $sources = $query
            ->orderBy(
                Invoices::query()->select('issued_date')->whereColumn('invoices.id', 'invoice_source_records.invoice_id')->limit(1),
            )
            ->orderBy('invoice_source_records.id')
            ->get();

        abort_if($sources->isEmpty(), 404, 'Không có hóa đơn phù hợp để xuất Excel.');

        $mode = $selectedIds->isNotEmpty() ? 'da-chon' : 'theo-filter';
        $period = ((string) $request->input('year', 'all')).'-'.((string) $request->input('month', 'all'));

        return $exporter->download($sources, "hoa-don-chi-tiet-{$mode}-{$period}.xlsx");
    }
}
