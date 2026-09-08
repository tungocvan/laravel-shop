<?php

namespace Modules\Invoices\Services;

class InvoiceWorkspaceService
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly InvoicePdfService $pdfService,
        private readonly InvoiceFileManagerService $fileManager,
    ) {}

    public function viewData(array $filters, int $perPage, array $selected): array
    {
        $dashboard = $this->invoiceService->dashboard();
        $annualYear = (int) substr((string) ($filters['issued_date_from'] ?? ''), 0, 4);

        if ($annualYear < 2000 || $annualYear > 2100) {
            $annualYear = (int) now()->year;
        }

        $annualStats = $this->invoiceService->statistics([
            'issued_date_from' => sprintf('%04d-01-01', $annualYear),
            'issued_date_to' => sprintf('%04d-12-31', $annualYear),
            'tax_rate' => 'all',
            'pdf_status' => 'all',
        ]);
        $invoices = $this->invoiceService->paginate($filters, $perPage);
        $pageIds = collect($invoices->items())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $selectedIds = collect($selected)
            ->map(fn ($id) => (int) $id)
            ->all();

        return [
            'invoices' => $invoices,
            'pageSelected' => $pageIds !== [] && count(array_intersect($pageIds, $selectedIds)) === count($pageIds),
            'pdfStatuses' => collect($invoices->items())
                ->mapWithKeys(fn ($invoice) => [$invoice->id => $this->pdfService->statusForInvoice($invoice)])
                ->all(),
            'filterStats' => $this->invoiceService->statistics($filters),
            'annualStats' => $annualStats,
            'annualYear' => $annualYear,
            'fileSummary' => $this->fileManager->summary($filters),
            'pdfErrors' => $this->fileManager->errorDetails($filters),
            'storageBreakdown' => $this->fileManager->storageBreakdown($filters),
            'totalSoldAmount' => $dashboard['sold_amount'],
            'totalPurchaseAmount' => $dashboard['purchase_amount'],
            'totalSoldCustomers' => $dashboard['sold_customers'],
            'totalPurchaseCustomers' => $dashboard['purchase_customers'],
            'yearlyRevenue' => $dashboard['yearly'],
        ];
    }

    public function pageIds(array $filters, int $perPage): array
    {
        return $this->invoiceService->paginate($filters, $perPage)
            ->getCollection()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function allFilteredIds(array $filters): array
    {
        return $this->invoiceService->filteredBuilder($filters)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function exportRecords(array $filters, array $selected)
    {
        return $selected === []
            ? $this->invoiceService->filter($filters)
            : $this->invoiceService->selected($selected);
    }
}
