<?php

namespace Modules\Finance\Livewire;

use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Modules\Finance\Services\InvoiceDebtRecognitionService;
use Livewire\WithPagination;

final class InvoiceDebtExplorer extends Component
{
    use WithPagination;

    public string $search = '';
    public string $invoiceType = '';
    public string $year = '';
    public string $month = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $appliedFrom = '';
    public string $appliedTo = '';
    public ?int $reviewInvoiceId = null;
    public array $reviewIssues = [];
    public array $reviewDetails = [];
    public string $recognizedOn = '';
    public string $dueOn = '';
    public bool $reviewed = false;


    public function mount(): void
    {
        $today = CarbonImmutable::today();
        $this->year = $today->format('Y');
        $this->month = $today->format('m');
        $this->dateFrom = $today->startOfMonth()->toDateString();
        $this->dateTo = $today->toDateString();
        $this->appliedFrom = $this->dateFrom;
        $this->appliedTo = $this->dateTo;
    }

    public function updatedYear(): void { $this->syncMonthDates(); }
    public function updatedMonth(): void { $this->syncMonthDates(); }

    private function syncMonthDates(): void
    {
        if (! preg_match('/^\d{4}$/', $this->year) || ! preg_match('/^(0[1-9]|1[0-2])$/', $this->month)) {
            return;
        }
        $first = CarbonImmutable::createFromFormat('!Y-m-d', $this->year.'-'.$this->month.'-01');
        if (! $first) {
            return;
        }
        $today = CarbonImmutable::today();
        $this->dateFrom = $first->toDateString();
        $this->dateTo = $first->isSameMonth($today) ? $today->toDateString() : $first->endOfMonth()->toDateString();
    }

    public function applyDates(): void
    {
        $dates = validator(['from' => $this->dateFrom, 'to' => $this->dateTo], [
            'from' => 'required|date_format:Y-m-d',
            'to' => 'required|date_format:Y-m-d|after_or_equal:from',
        ])->validate();
        $this->appliedFrom = $dates['from'];
        $this->appliedTo = $dates['to'];
        $this->resetPage('invoicePage');
    }


    public function openReview(int $invoiceId, InvoiceDebtRecognitionService $service): void
    {
        $this->authorizeManage();
        $preview = $service->preview($invoiceId);
        $invoice = $preview['invoice'];
        $this->reviewInvoiceId = $invoiceId;
        $this->reviewIssues = $preview['issues'];
        $this->reviewDetails = [
            'reference' => (string) $invoice->symbol.'/'.(string) $invoice->invoice_number,
            'name' => (string) $invoice->name,
            'tax_code' => (string) $invoice->tax_code,
            'amount' => (string) $invoice->total_amount,
            'direction' => $invoice->invoice_type === 'sold' ? 'Phải thu' : 'Phải trả',
        ];
        $this->recognizedOn = now()->toDateString();
        $this->dueOn = '';
        $this->reviewed = false;
        $this->resetErrorBag();
    }

    public function closeReview(): void
    {
        $this->reset('reviewInvoiceId', 'reviewIssues', 'reviewDetails', 'recognizedOn', 'dueOn', 'reviewed');
        $this->resetErrorBag();
    }

    public function confirmReview(InvoiceDebtRecognitionService $service): void
    {
        $actorId = $this->authorizeManage();
        if ($this->reviewInvoiceId === null) {
            abort(404);
        }
        $debtId = $service->confirm($this->reviewInvoiceId, $actorId, $this->recognizedOn,
            $this->dueOn !== '' ? $this->dueOn : null, $this->reviewed);
        $this->closeReview();
        $this->resetPage('invoicePage');
        session()->flash('invoice_debt_notice', 'Đã xác nhận công nợ #'.$debtId.'. Chưa ghi nhận thanh toán.');
    }

    private function authorizeManage(): int
    {
        abort_unless(auth('admin')->user()?->can('finance.view')
            && auth('admin')->user()?->can('invoices-list')
            && auth('admin')->user()?->can('finance.debt.manage'), 403);
        abort_unless(auth('admin')->id() !== null, 403);
        return (int) auth('admin')->id();
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedInvoiceType(): void { $this->resetPage(); }

    public function resetFilters(): void
    {
        $this->reset('search', 'invoiceType');
        $this->mount();
        $this->resetPage('invoicePage');
    }

    public function render()
    {
        abort_unless(auth('admin')->user()?->can('finance.view')
            && auth('admin')->user()?->can('invoices-list'), 403);

        $filters = validator([
            'search' => $this->search,
            'invoiceType' => $this->invoiceType,
        ], [
            'search' => 'nullable|string|max:100',
            'invoiceType' => 'nullable|in:sold,purchase',
        ])->validate();

        $available = (bool) config('modules.registry.Invoices.enabled', false)
            && Schema::hasTable('invoices');

        $invoices = null;
        if ($available) {
            $query = DB::table('invoices as i')
                ->select('i.id', 'i.invoice_number', 'i.symbol', 'i.issued_date', 'i.invoice_type',
                    'i.name', 'i.tax_code', 'i.total_amount')
                ->selectRaw("(SELECT COUNT(*) FROM finance_debt_obligations d
                    WHERE d.source_module = 'Invoices' AND d.source_type = 'invoice'
                    AND d.source_document_id = i.id) AS linked_debt_count")
                ->whereDate('i.issued_date', '>=', $this->appliedFrom)
                ->whereDate('i.issued_date', '<=', $this->appliedTo)
                ->when($filters['invoiceType'] !== '', fn ($q) => $q->where('i.invoice_type', $filters['invoiceType']))
                ->when(trim($filters['search']) !== '', function ($q) use ($filters): void {
                    $term = '%'.addcslashes(trim($filters['search']), '%_\\').'%';
                    $q->where(fn ($nested) => $nested->where('i.invoice_number', 'like', $term)
                        ->orWhere('i.symbol', 'like', $term)
                        ->orWhere('i.name', 'like', $term)
                        ->orWhere('i.tax_code', 'like', $term));
                });

            $invoices = $query->orderByDesc('i.issued_date')->orderByDesc('i.id')
                ->paginate(10, ['*'], 'invoicePage');
        }

        return view('Finance::livewire.invoice-debt-explorer', [
            'available' => $available,
            'invoices' => $invoices,
            'canRecognize' => (bool) auth('admin')->user()?->can('finance.debt.manage'),
        ]);
    }
}
