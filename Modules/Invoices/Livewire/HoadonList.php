<?php

namespace Modules\Invoices\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Invoices\Exports\InvoicesSelectedExport;
use Modules\Invoices\Services\InvoicePdfService;
use Modules\Invoices\Services\InvoiceService;

class HoadonList extends Component
{
    use WithPagination;

    protected InvoiceService $invoiceService;
    protected InvoicePdfService $pdfService;

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
    public int $perPage = 10;
    public array $perPageOptions = [10, 25, 50, 100];

    protected $queryString = ['type', 'name', 'tax_code', 'from_date', 'to_date', 'taxRateFilter', 'perPage'];

    public function boot(InvoiceService $invoiceService, InvoicePdfService $pdfService): void
    {
        $this->invoiceService = $invoiceService;
        $this->pdfService = $pdfService;
    }

    public function mount(): void
    {
        $this->perPage = $this->normalizedPerPage($this->perPage);
        $this->from_date = $this->from_date ?: Carbon::now()->startOfYear()->format('Y-m-d');
        $this->to_date = $this->to_date ?: Carbon::now()->format('Y-m-d');
        $this->refreshOptions();
    }

    public function updatedType(): void { $this->name = ''; $this->tax_code = ''; $this->taxRateFilter = 'all'; $this->resetListState(true); }
    public function updatedName(): void { $this->tax_code = ''; $this->resetListState(true); }
    public function updatedTaxCode(): void { $this->resetListState(); }
    public function updatedFromDate(): void { $this->resetListState(true); }
    public function updatedToDate(): void { $this->resetListState(true); }
    public function updatedTaxRateFilter(): void { $this->resetListState(); }

    public function updatedPerPage(mixed $value): void
    {
        $this->perPage = $this->normalizedPerPage($value);
        $this->selected = [];
        $this->resetPage();
    }

    public function resetTomSelect(string $refName): void
    {
        $refName === 'nameSelect' ? $this->tax_code = '' : $this->name = '';
        $this->resetListState(true);
    }

    public function resetFilters(): void
    {
        $this->type = null;
        $this->name = '';
        $this->tax_code = '';
        $this->from_date = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->to_date = Carbon::now()->format('Y-m-d');
        $this->taxRateFilter = 'all';
        $this->selected = [];
        $this->refreshOptions();
        $this->resetPage();
    }

    public function getFilteredTotalAmountProperty(): mixed { return $this->statistics()['total_amount']; }
    public function getFilteredInvoiceCountProperty(): int { return $this->statistics()['count']; }
    public function getFilteredTotalByTaxRateProperty(): array { return $this->statistics()['by_tax_rate']; }
    public function getFilteredTotalVatProperty(): mixed { return $this->statistics()['vat_amount']; }

    public function exportSelected()
    {
        $this->authorizePermission('invoices-export');
        $records = $this->selected === [] ? $this->invoiceService->filter($this->filters()) : $this->invoiceService->selected($this->selected);

        return Excel::download(new InvoicesSelectedExport($records), 'hoadon_'.($this->selected === [] ? 'loc' : 'chon').'_'.now()->format('Ymd_His').'.xlsx');
    }

    public function downloadSelected(): void
    {
        $this->authorizePermission('invoices-download');
        if ($this->selected === []) {
            $this->dispatch('alert', type: 'warning', message: 'Vui lòng chọn hóa đơn trước khi tải PDF.');
            return;
        }

        $this->downloadStatus = 'processing';
        $result = $this->pdfService->downloadSelected($this->selected);
        $this->downloadStatus = $result['failed'] > 0 ? 'error' : 'success';
        $message = "PDF mới: {$result['downloaded']} · Đã có: {$result['existing']} · Lỗi: {$result['failed']}";
        $this->dispatch($result['failed'] > 0 ? 'alert' : 'download-success', type: $result['failed'] > 0 ? 'warning' : 'success', message: $message);
    }

    public function downloadPdf(int $invoiceId, bool $force = false): void
    {
        $this->authorizePermission('invoices-download');

        try {
            $this->pdfService->downloadInvoice($invoiceId, $force);
            $this->dispatch('download-success', type: 'success', message: $force ? 'Đã tải lại PDF hóa đơn.' : 'Đã tải PDF hóa đơn.');
        } catch (\Throwable $exception) {
            $this->dispatch('alert', type: 'error', message: $exception->getMessage());
        }
    }

    public function render()
    {
        $dashboard = $this->invoiceService->dashboard();
        $invoices = $this->invoiceService->paginate($this->filters(), $this->perPage);
        $pdfStatuses = collect($invoices->items())->mapWithKeys(fn ($invoice) => [
            $invoice->id => $this->pdfService->statusFor((string) $invoice->lookup_code),
        ])->all();

        return view('Invoices::livewire.hoadon-list', [
            'invoices' => $invoices,
            'pdfStatuses' => $pdfStatuses,
            'totalSoldAmount' => $dashboard['sold_amount'],
            'totalPurchaseAmount' => $dashboard['purchase_amount'],
            'totalSoldCustomers' => $dashboard['sold_customers'],
            'totalPurchaseCustomers' => $dashboard['purchase_customers'],
            'yearlyRevenue' => $dashboard['yearly'],
        ]);
    }

    private function resetListState(bool $refreshOptions = false): void
    {
        $this->selected = [];
        if ($refreshOptions) { $this->refreshOptions(); }
        $this->resetPage();
    }

    private function refreshOptions(): void
    {
        $options = $this->invoiceService->filterOptions($this->filters());
        $this->nameList = $options['names'];
        $this->taxCodeList = $options['tax_codes'];
    }

    private function statistics(): array { return $this->invoiceService->statistics($this->filters()); }

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

    private function normalizedPerPage(mixed $value): int
    {
        $value = (int) $value;
        return in_array($value, $this->perPageOptions, true) ? $value : 10;
    }

    private function authorizePermission(string $permission): void
    {
        abort_unless(auth('admin')->check() && auth('admin')->user()->can($permission), 403);
    }
}
