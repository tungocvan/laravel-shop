<?php

namespace Modules\Invoices\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Invoices\Exports\InvoicePartnerReportExport;
use Modules\Invoices\Services\InvoicePartnerReportService;
use Modules\Invoices\Services\InvoiceService;

class PartnerReport extends Component
{
    use WithPagination;

    protected InvoicePartnerReportService $reportService;
    protected InvoiceService $invoiceService;

    public ?string $type = null;
    public string $search = '';
    public string $year = '';
    public string $month = '';
    public string $from_date = '';
    public string $to_date = '';
    public string $sort = 'sold_desc';
    public int $perPage = 25;
    public array $yearOptions = [];

    protected $queryString = ['type', 'search', 'year', 'month', 'from_date', 'to_date', 'sort', 'perPage'];

    public function boot(InvoicePartnerReportService $reportService, InvoiceService $invoiceService): void
    {
        $this->reportService = $reportService;
        $this->invoiceService = $invoiceService;
    }

    public function mount(): void
    {
        $this->yearOptions = $this->invoiceService->years();
        if ($this->year === '' && $this->from_date === '' && $this->to_date === '') {
            $this->year = (string) now()->year;
            $this->from_date = Carbon::now()->startOfYear()->format('Y-m-d');
            $this->to_date = Carbon::now()->format('Y-m-d');
        }
    }

    public function updatedType(): void { $this->resetPage(); }
    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedSort(): void { $this->resetPage(); }
    public function updatedPerPage(): void { $this->perPage = in_array((int) $this->perPage, [10,25,50,100], true) ? (int) $this->perPage : 25; $this->resetPage(); }
    public function updatedYear(): void { $this->applyPeriod(); }
    public function updatedMonth(): void { $this->applyPeriod(); }
    public function updatedFromDate(): void { $this->year = ''; $this->month = ''; $this->resetPage(); }
    public function updatedToDate(): void { $this->year = ''; $this->month = ''; $this->resetPage(); }

    public function resetFilters(): void
    {
        $this->type = null;
        $this->search = '';
        $this->year = (string) now()->year;
        $this->month = '';
        $this->from_date = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->to_date = Carbon::now()->format('Y-m-d');
        $this->sort = 'sold_desc';
        $this->resetPage();
    }

    public function exportExcel()
    {
        abort_unless(auth('admin')->check() && auth('admin')->user()->can('invoices-export'), 403);
        return Excel::download(
            new InvoicePartnerReportExport($this->reportService->exportRows($this->filters())),
            'bao-cao-doi-tac_'.now()->format('Ymd_His').'.xlsx'
        );
    }

    public function render()
    {
        $filters = $this->filters();
        return view('Invoices::livewire.partner-report', [
            'partners' => $this->reportService->paginate($filters, $this->perPage),
            'summary' => $this->reportService->summary($filters),
        ]);
    }

    private function applyPeriod(): void
    {
        if ($this->year === '') {
            $this->month = '';
            $this->from_date = '';
            $this->to_date = '';
            $this->resetPage();
            return;
        }
        $year = (int) $this->year;
        if ($year < 2000 || $year > 2100) return;
        if ($this->month === '') {
            $this->from_date = Carbon::create($year, 1, 1)->format('Y-m-d');
            $this->to_date = Carbon::create($year, 12, 31)->format('Y-m-d');
        } else {
            $date = Carbon::create($year, (int) $this->month, 1);
            $this->from_date = $date->copy()->startOfMonth()->format('Y-m-d');
            $this->to_date = $date->copy()->endOfMonth()->format('Y-m-d');
        }
        $this->resetPage();
    }

    private function filters(): array
    {
        return ['invoice_type'=>$this->type,'search'=>$this->search,'issued_date_from'=>$this->from_date,'issued_date_to'=>$this->to_date,'sort'=>$this->sort];
    }
}
