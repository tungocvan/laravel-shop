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

    public string $name = '';

    public string $tax_code = '';

    public string $year = '';

    public string $month = '';

    public string $from_date = '';

    public string $to_date = '';

    public string $sort = 'sold_desc';

    public int $perPage = 25;

    public array $yearOptions = [];

    public array $nameList = [];

    public array $taxCodeList = [];

    public ?array $partnerDetail = null;

    public array $selected = [];

    public bool $selectPage = false;

    public bool $selectAllFiltered = false;

    protected $queryString = ['type', 'name', 'tax_code', 'year', 'month', 'from_date', 'to_date', 'sort', 'perPage'];

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
        $this->refreshOptions();
    }

    public function updatedType(): void
    {
        $this->resetReportState(true);
    }

    public function updatedName(): void
    {
        $this->resetReportState(true);
    }

    public function updatedTaxCode(): void
    {
        $this->resetReportState(true);
    }

    public function updatedSort(): void
    {
        $this->clearSelection();
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->perPage = in_array((int) $this->perPage, [10, 25, 50, 100], true) ? (int) $this->perPage : 25;
        $this->clearSelection();
        $this->resetPage();
    }

    public function updatedYear(): void
    {
        $this->applyPeriod();
    }

    public function updatedMonth(): void
    {
        $this->applyPeriod();
    }

    public function updatedFromDate(): void
    {
        $this->year = '';
        $this->month = '';
        $this->resetReportState(true);
    }

    public function updatedToDate(): void
    {
        $this->year = '';
        $this->month = '';
        $this->resetReportState(true);
    }

    public function updatedSelected(): void
    {
        $this->selectAllFiltered = false;
    }

    public function togglePageSelection(): void
    {
        $partners = $this->reportService->paginate($this->filters(), $this->perPage);
        $pageKeys = collect($partners->items())
            ->map(fn ($partner): string => $this->partnerKey((string) $partner->partner_name, (string) $partner->partner_tax_code))
            ->all();

        if ($this->selectPage) {
            $this->selected = collect($this->selected)->merge($pageKeys)->unique()->values()->all();
        } else {
            $this->selected = array_values(array_diff($this->selected, $pageKeys));
        }

        $this->selectAllFiltered = false;
    }

    public function selectAllFilteredResults(): void
    {
        $this->selected = $this->reportService->exportRows($this->filters())
            ->map(fn ($partner): string => $this->partnerKey((string) $partner->partner_name, (string) $partner->partner_tax_code))
            ->unique()
            ->values()
            ->all();
        $this->selectPage = true;
        $this->selectAllFiltered = true;
    }

    public function clearSelection(): void
    {
        $this->selected = [];
        $this->selectPage = false;
        $this->selectAllFiltered = false;
    }

    public function showPartnerDetail(string $key): void
    {
        $decoded = base64_decode($key, true);
        $payload = $decoded !== false ? json_decode($decoded, true) : null;

        if (! is_array($payload) || ! isset($payload['name'], $payload['tax_code'])) {
            $this->partnerDetail = null;

            return;
        }

        $this->partnerDetail = $this->reportService->partnerDetail(
            $this->filters(),
            (string) $payload['name'],
            (string) $payload['tax_code']
        );
    }

    public function closePartnerDetail(): void
    {
        $this->partnerDetail = null;
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
        $this->sort = 'sold_desc';
        $this->partnerDetail = null;
        $this->clearSelection();
        $this->refreshOptions();
        $this->resetPage();
        $this->dispatch('filters-reset');
    }

    public function exportExcel()
    {
        abort_unless(auth('admin')->check() && auth('admin')->user()->can('invoices-export'), 403);

        $rows = $this->reportService->exportRows($this->filters());
        if ($this->selected !== []) {
            $selected = array_flip($this->selected);
            $rows = $rows->filter(fn ($partner): bool => isset($selected[$this->partnerKey(
                (string) $partner->partner_name,
                (string) $partner->partner_tax_code
            )]))->values();
        }

        return Excel::download(
            new InvoicePartnerReportExport($rows),
            'bao-cao-doi-tac_'.now()->format('Ymd_His').'.xlsx'
        );
    }

    public function render()
    {
        $filters = $this->filters();
        $partners = $this->reportService->paginate($filters, $this->perPage);
        $pageKeys = collect($partners->items())
            ->map(fn ($partner): string => $this->partnerKey((string) $partner->partner_name, (string) $partner->partner_tax_code))
            ->all();
        $this->selectPage = $pageKeys !== [] && collect($pageKeys)->every(fn (string $key): bool => in_array($key, $this->selected, true));

        return view('Invoices::livewire.partner-report', [
            'partners' => $partners,
            'summary' => $this->reportService->summary($filters),
        ]);
    }

    private function applyPeriod(): void
    {
        if ($this->year === '') {
            $this->month = '';
            $this->from_date = '';
            $this->to_date = '';
            $this->resetReportState(true);

            return;
        }

        $year = (int) $this->year;
        if ($year < 2000 || $year > 2100) {
            return;
        }

        if ($this->month === '') {
            $this->from_date = Carbon::create($year, 1, 1)->format('Y-m-d');
            $this->to_date = Carbon::create($year, 12, 31)->format('Y-m-d');
        } else {
            $date = Carbon::create($year, (int) $this->month, 1);
            $this->from_date = $date->copy()->startOfMonth()->format('Y-m-d');
            $this->to_date = $date->copy()->endOfMonth()->format('Y-m-d');
        }
        $this->resetReportState(true);
    }

    private function refreshOptions(): void
    {
        $filters = [
            'invoice_type' => $this->type,
            'issued_date_from' => $this->from_date,
            'issued_date_to' => $this->to_date,
            'tax_rate' => 'all',
            'pdf_status' => 'all',
        ];
        $options = $this->invoiceService->filterOptions($filters);
        $this->nameList = $options['names'];
        $this->taxCodeList = $options['tax_codes'];
    }

    private function resetReportState(bool $refreshOptions = false): void
    {
        $this->partnerDetail = null;
        $this->clearSelection();
        if ($refreshOptions) {
            $this->refreshOptions();
        }
        $this->resetPage();
    }

    private function filters(): array
    {
        return [
            'invoice_type' => $this->type,
            'name' => $this->name,
            'tax_code' => $this->tax_code,
            'issued_date_from' => $this->from_date,
            'issued_date_to' => $this->to_date,
            'sort' => $this->sort,
        ];
    }

    private function partnerKey(string $name, string $taxCode): string
    {
        return base64_encode(json_encode([
            'name' => $name,
            'tax_code' => $taxCode,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
