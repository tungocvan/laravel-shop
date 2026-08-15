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
    public ?string $pdfNotice = null;
    public ?string $pdfError = null;
    public ?int $pdfProcessingId = null;
    public ?string $type = null;
    public string $name = '';
    public string $tax_code = '';
    public string $from_date = '';
    public string $to_date = '';
    public string $taxRateFilter = 'all';
    public string $year = '';
    public string $month = '';
    public string $sort = 'date_desc';
    public array $yearOptions = [];
    public array $nameList = [];
    public array $taxCodeList = [];
    public array $selected = [];
    public int $perPage = 10;
    public array $perPageOptions = [10, 25, 50, 100];

    protected $queryString = [
        'type', 'name', 'tax_code', 'from_date', 'to_date', 'taxRateFilter',
        'year', 'month', 'sort', 'perPage',
    ];

    public function boot(InvoiceService $invoiceService, InvoicePdfService $pdfService): void
    {
        $this->invoiceService = $invoiceService;
        $this->pdfService = $pdfService;
    }

    public function mount(): void
    {
        $this->perPage = $this->normalizedPerPage($this->perPage);
        $this->yearOptions = $this->invoiceService->years();

        if ($this->year !== '') {
            $this->applyPeriodSelection(false);
        } elseif ($this->from_date === '' && $this->to_date === '') {
            $this->year = (string) now()->year;
            $this->from_date = Carbon::now()->startOfYear()->format('Y-m-d');
            $this->to_date = Carbon::now()->format('Y-m-d');
        }

        $this->refreshOptions();
    }

    public function updatedType(): void { $this->resetListState(true); }
    public function updatedName(): void { $this->resetListState(true); }
    public function updatedTaxCode(): void { $this->resetListState(true); }
    public function updatedTaxRateFilter(): void { $this->resetListState(); }
    public function updatedSort(): void { $this->resetListState(); }

    public function updatedYear(): void
    {
        if ($this->year === '') {
            $this->month = '';
            $this->from_date = '';
            $this->to_date = '';
            $this->resetListState(true);
            return;
        }

        $this->applyPeriodSelection();
    }

    public function updatedMonth(): void
    {
        if ($this->month !== '' && $this->year === '') {
            $this->year = (string) now()->year;
        }

        $this->applyPeriodSelection();
    }

    public function updatedFromDate(): void
    {
        $this->clearPeriodPreset();
        $this->resetListState(true);
    }

    public function updatedToDate(): void
    {
        $this->clearPeriodPreset();
        $this->resetListState(true);
    }

    public function updatedPerPage(mixed $value): void
    {
        $this->perPage = $this->normalizedPerPage($value);
        $this->selected = [];
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->type = null;
        $this->name = '';
        $this->tax_code = '';
        $this->year = (string) now()->year;
        $this->month = '';
        $this->from_date = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->to_date = Carbon::now()->format('Y-m-d');
        $this->taxRateFilter = 'all';
        $this->sort = 'date_desc';
        $this->selected = [];
        $this->pdfNotice = null;
        $this->pdfError = null;
        $this->refreshOptions();
        $this->resetPage();
        $this->dispatch('filters-reset');
    }

    public function exportSelected()
    {
        $this->authorizePermission('invoices-export');
        $records = $this->selected === []
            ? $this->invoiceService->filter($this->filters())
            : $this->invoiceService->selected($this->selected);

        return Excel::download(
            new InvoicesSelectedExport($records),
            'hoadon_'.($this->selected === [] ? 'loc' : 'chon').'_'.now()->format('Ymd_His').'.xlsx'
        );
    }

    public function downloadSelected(): void
    {
        $this->authorizePermission('invoices-download');
        $this->pdfNotice = null;
        $this->pdfError = null;

        if ($this->selected === []) {
            $this->pdfError = 'Vui lòng chọn hóa đơn trước khi tải PDF.';
            return;
        }

        $this->downloadStatus = 'processing';
        $result = $this->pdfService->downloadSelected($this->selected);
        $this->downloadStatus = $result['failed'] > 0 ? 'error' : 'success';
        $message = "PDF mới: {$result['downloaded']} · Đã có: {$result['existing']} · Lỗi: {$result['failed']}";

        if ($result['failed'] > 0) {
            $detail = $result['errors'][0] ?? null;
            $this->pdfError = $detail ? $message.' · '.$detail : $message;
        } else {
            $this->pdfNotice = $message;
        }
    }

    public function downloadPdf(int $invoiceId, bool $force = false): void
    {
        $this->authorizePermission('invoices-download');
        $this->pdfNotice = null;
        $this->pdfError = null;
        $this->pdfProcessingId = $invoiceId;

        try {
            $path = $this->pdfService->downloadInvoice($invoiceId, $force);
            $filename = basename($path);
            $this->pdfNotice = ($force ? 'Đã tải lại PDF hóa đơn: ' : 'Đã tải PDF hóa đơn: ').$filename;
        } catch (\Throwable $exception) {
            $this->pdfError = $exception->getMessage();
        } finally {
            $this->pdfProcessingId = null;
        }
    }

    public function render()
    {
        $filters = $this->filters();
        $dashboard = $this->invoiceService->dashboard();
        $filterStats = $this->invoiceService->statistics($filters);
        $invoices = $this->invoiceService->paginate($filters, $this->perPage);
        $pdfStatuses = collect($invoices->items())->mapWithKeys(fn ($invoice) => [
            $invoice->id => $this->pdfService->statusForInvoice($invoice),
        ])->all();

        return view('Invoices::livewire.hoadon-list', [
            'invoices' => $invoices,
            'pdfStatuses' => $pdfStatuses,
            'filterStats' => $filterStats,
            'totalSoldAmount' => $dashboard['sold_amount'],
            'totalPurchaseAmount' => $dashboard['purchase_amount'],
            'totalSoldCustomers' => $dashboard['sold_customers'],
            'totalPurchaseCustomers' => $dashboard['purchase_customers'],
            'yearlyRevenue' => $dashboard['yearly'],
        ]);
    }

    private function applyPeriodSelection(bool $reset = true): void
    {
        $year = (int) $this->year;
        if ($year < 2000 || $year > 2100) {
            $this->year = '';
            $this->month = '';
            return;
        }

        if ($this->month !== '') {
            $month = (int) $this->month;
            if ($month < 1 || $month > 12) {
                $this->month = '';
            }
        }

        if ($this->month === '') {
            $start = Carbon::create($year, 1, 1)->startOfDay();
            $end = Carbon::create($year, 12, 31)->endOfDay();
        } else {
            $start = Carbon::create($year, (int) $this->month, 1)->startOfMonth();
            $end = $start->copy()->endOfMonth();
        }

        $this->from_date = $start->format('Y-m-d');
        $this->to_date = $end->format('Y-m-d');

        if ($reset) {
            $this->resetListState(true);
        }
    }

    private function clearPeriodPreset(): void
    {
        $this->year = '';
        $this->month = '';
    }

    private function resetListState(bool $refreshOptions = false): void
    {
        $this->selected = [];
        if ($refreshOptions) {
            $this->refreshOptions();
        }
        $this->resetPage();
    }

    private function refreshOptions(): void
    {
        $options = $this->invoiceService->filterOptions($this->filters());
        $this->nameList = $options['names'];
        $this->taxCodeList = $options['tax_codes'];
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
            'sort' => $this->sort,
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
