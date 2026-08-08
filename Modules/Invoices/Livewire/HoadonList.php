<?php

namespace Modules\Invoices\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Invoices\Exports\InvoicesSelectedExport;
use Modules\Invoices\Services\InvoiceService;
use Modules\Invoices\Services\MeInvoiceService;

class HoadonList extends Component
{
    use WithPagination;

    protected InvoiceService $invoiceService;

    protected MeInvoiceService $meInvoiceService;

    public ?string $downloadStatus = null;

    public ?string $type = null;

    public string $name = '';

    public string $tax_code = '';

    public string $from_date = '';

    public string $to_date = '';

    public string $taxRateFilter = 'all';

    public array $nameList = [];

    public array $taxCodeList = [];

    public array $selected = [];

    public string|int $perPage = 10;

    public array $perPageOptions = [10, 25, 50, 100, 'All'];

    protected $queryString = [
        'type', 'name', 'tax_code', 'from_date', 'to_date', 'taxRateFilter', 'perPage',
    ];

    public function boot(InvoiceService $invoiceService, MeInvoiceService $meInvoiceService): void
    {
        $this->invoiceService = $invoiceService;
        $this->meInvoiceService = $meInvoiceService;
    }

    public function mount(): void
    {
        $this->from_date = $this->from_date ?: Carbon::now()->startOfYear()->format('Y-m-d');
        $this->to_date = $this->to_date ?: Carbon::now()->format('Y-m-d');
        $this->refreshOptions();
    }

    public function updatedType(): void
    {
        $this->name = '';
        $this->tax_code = '';
        $this->taxRateFilter = 'all';
        $this->refreshOptions();
        $this->resetPage();
    }

    public function updatedName(): void
    {
        $this->tax_code = '';
        $this->refreshOptions();
        $this->resetPage();
    }

    public function updatedTaxCode(): void
    {
        $this->resetPage();
    }

    public function updatedFromDate(): void
    {
        $this->refreshOptions();
        $this->resetPage();
    }

    public function updatedToDate(): void
    {
        $this->refreshOptions();
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function resetTomSelect(string $refName): void
    {
        $refName === 'nameSelect' ? $this->tax_code = '' : $this->name = '';
        $this->refreshOptions();
    }

    public function resetFilters(): void
    {
        $this->name = '';
        $this->tax_code = '';
        $this->taxRateFilter = 'all';
        $this->refreshOptions();
        $this->resetPage();
    }

    public function getFilteredTotalAmountProperty(): mixed
    {
        return $this->statistics()['total_amount'];
    }

    public function getFilteredInvoiceCountProperty(): int
    {
        return $this->statistics()['count'];
    }

    public function getFilteredTotalByTaxRateProperty(): array
    {
        return $this->statistics()['by_tax_rate'];
    }

    public function getFilteredTotalVatProperty(): mixed
    {
        return $this->statistics()['vat_amount'];
    }

    public function exportSelected()
    {
        if ($this->selected === []) {
            $this->dispatch('alert', type: 'warning', message: 'Vui lòng chọn hóa đơn trước khi xuất.');

            return null;
        }

        return Excel::download(
            new InvoicesSelectedExport($this->invoiceService->selected($this->selected)),
            'hoadon_chon_'.now()->format('Ymd_His').'.xlsx'
        );
    }

    public function downloadSelected(): void
    {
        if ($this->selected === []) {
            $this->dispatch('alert', type: 'warning', message: 'Vui lòng chọn hóa đơn trước khi tải.');

            return;
        }

        $this->downloadStatus = 'processing';

        try {
            $count = $this->meInvoiceService->downloadSelected($this->selected);
            $this->downloadStatus = 'success';
            $this->dispatch('download-success', type: 'success', message: "Đã lưu {$count} hóa đơn PDF.");
        } catch (\RuntimeException $exception) {
            $this->downloadStatus = 'error';
            $this->dispatch('alert', type: 'error', message: $exception->getMessage());
        }
    }

    public function render()
    {
        $dashboard = $this->invoiceService->dashboard();

        return view('Invoices::livewire.hoadon-list', [
            'invoices' => $this->invoiceService->paginate($this->filters(), $this->perPage),
            'totalSoldAmount' => $dashboard['sold_amount'],
            'totalPurchaseAmount' => $dashboard['purchase_amount'],
            'totalSoldCustomers' => $dashboard['sold_customers'],
            'totalPurchaseCustomers' => $dashboard['purchase_customers'],
            'yearlyRevenue' => $dashboard['yearly'],
        ]);
    }

    private function refreshOptions(): void
    {
        $options = $this->invoiceService->filterOptions($this->filters());
        $this->nameList = $options['names'];
        $this->taxCodeList = $options['tax_codes'];
    }

    private function statistics(): array
    {
        return $this->invoiceService->statistics($this->filters());
    }

    private function filters(): array
    {
        return [
            'invoice_type' => $this->type,
            'name' => $this->name,
            'tax_code' => $this->tax_code,
            'issued_date_from' => $this->from_date,
            'issued_date_to' => $this->to_date,
            'tax_rate' => $this->taxRateFilter,
        ];
    }
}
