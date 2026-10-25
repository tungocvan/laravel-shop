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

    public array $bulkSelected = [];
    public array $bulkPreview = [];
    public array $bulkResults = [];
    public bool $bulkReviewed = false;
    public string $bulkRecognizedOn = '';
    public string $bulkDueOn = '';
    public array $bulkDueDates = [];
    public bool $bulkResultsExpanded = true;



    public function mount(): void
    {
        $today = CarbonImmutable::today();
        $this->year = $today->format('Y');
        $this->month = $today->format('m');
        $this->dateFrom = $today->startOfMonth()->toDateString();
        $this->dateTo = $today->toDateString();
        $this->appliedFrom = $this->dateFrom;
        $this->appliedTo = $this->dateTo;
        $this->bulkRecognizedOn = $today->toDateString();
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
        $this->clearBulk();
        $this->resetPage('invoicePage');
    }



    private function selectablePageIds(): array
    {
        $filters = validator(['search' => $this->search, 'type' => $this->invoiceType], [
            'search' => 'nullable|string|max:100', 'type' => 'nullable|in:sold,purchase',
        ])->validate();
        $term = '%'.addcslashes(trim($filters['search']), '%_\\').'%';
        $page = max(1, (int) $this->getPage('invoicePage'));
        $rows = DB::table('invoices as i')
            ->whereBetween('i.issued_date', [$this->appliedFrom, $this->appliedTo])
            ->when($filters['type'] !== '', fn ($q) => $q->where('i.invoice_type', $filters['type']))
            ->when(trim($filters['search']) !== '', fn ($q) => $q->where(fn ($sub) =>
                $sub->where('i.invoice_number', 'like', $term)->orWhere('i.symbol', 'like', $term)
                    ->orWhere('i.name', 'like', $term)->orWhere('i.tax_code', 'like', $term)))
            ->orderByDesc('i.issued_date')->orderByDesc('i.id')
            ->forPage($page, 10)->pluck('i.id')->map(fn ($id) => (int) $id)->all();
        $linked = DB::table('finance_debt_obligations')->where('source_module', 'Invoices')
            ->where('source_type', 'invoice')->whereIn('source_document_id', array_map('strval', $rows))
            ->pluck('source_document_id')->map(fn ($id) => (int) $id)->all();
        return array_values(array_diff($rows, $linked));
    }

    private function clearBulk(): void
    {
        $this->bulkSelected = [];
        $this->bulkPreview = [];
        $this->bulkResults = [];
        $this->bulkReviewed = false;
        $this->bulkDueDates = [];
    }

    public function toggleBulk(int $id): void
    {
        $this->authorizeManage();
        $this->bulkPreview = [];
        $this->bulkResults = [];
        $this->bulkReviewed = false;
        if (! in_array($id, $this->selectablePageIds(), true)) {
            return;
        }
        $this->bulkSelected = in_array($id, $this->bulkSelected, true)
            ? array_values(array_diff($this->bulkSelected, [$id]))
            : array_values(array_unique([...$this->bulkSelected, $id]));
    }

    public function toggleBulkPage(): void
    {
        $this->authorizeManage();
        $ids = $this->selectablePageIds();
        $all = $ids !== [] && count(array_intersect($ids, $this->bulkSelected)) === count($ids);
        $this->bulkSelected = $all ? array_values(array_diff($this->bulkSelected, $ids))
            : array_values(array_unique([...$this->bulkSelected, ...$ids]));
        $this->bulkPreview = [];
        $this->bulkResults = [];
        $this->bulkReviewed = false;
    }

    public function prepareBulk(InvoiceDebtRecognitionService $service): void
    {
        $this->authorizeManage();
        $ids = array_values(array_unique(array_map('intval', $this->bulkSelected)));
        if ($ids === [] || count($ids) > 10 || count(array_intersect($ids, $this->selectablePageIds())) !== count($ids)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['bulk' => 'Chỉ được chọn tối đa 10 hóa đơn chưa liên kết trên trang hiện tại.']);
        }
        $this->bulkPreview = [];
        $this->bulkResults = [];
        $this->bulkReviewed = false;
        $this->bulkDueDates = [];
        foreach ($ids as $id) {
            $data = $service->preview($id);
            $invoice = $data['invoice'];
            $this->bulkDueDates[$id] = $service->defaultDueOn((string) $invoice->issued_date);
            $this->bulkPreview[] = [
                'id' => $id,
                'issued_on' => (string) $invoice->issued_date,
                'reference' => (string) $invoice->symbol.'/'.(string) $invoice->invoice_number,
                'partner' => (string) $invoice->name,
                'amount' => (string) $invoice->total_amount,
                'issues' => $data['issues'],
            ];
        }
    }

    public function confirmBulk(InvoiceDebtRecognitionService $service): void
    {
        $actorId = $this->authorizeManage();
        $ids = array_column($this->bulkPreview, 'id');
        if (! $this->bulkReviewed || $ids === [] || count($ids) > 10
            || count($ids) !== count(array_unique($ids))
            || $ids !== array_values($this->bulkSelected)
            || count(array_intersect($ids, $this->selectablePageIds())) !== count($ids)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['bulk' => 'Danh sách thay đổi hoặc chưa xác nhận kiểm tra. Vui lòng xem trước lại.']);
        }
        $this->bulkResults = [];
        foreach ($this->bulkPreview as $row) {
            $id = (int) $row['id'];
            if ($row['issues'] !== []) {
                $this->bulkResults[] = ['id' => $id, 'reference' => $row['reference'], 'status' => 'skipped', 'reason' => implode(' ', $row['issues'])];
                continue;
            }
            try {
                $debtId = $service->confirm($id, $actorId, $this->bulkRecognizedOn,
                    $this->bulkDueDates[$id] ?? null, true);
                $this->bulkResults[] = ['id' => $id, 'reference' => $row['reference'], 'status' => 'success', 'reason' => 'Đã ghi nhận công nợ #'.$debtId];
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->bulkResults[] = ['id' => $id, 'reference' => $row['reference'], 'status' => 'skipped', 'reason' => implode(' ', $e->validator->errors()->all())];
            } catch (\Throwable $e) {
                report($e);
                $this->bulkResults[] = ['id' => $id, 'reference' => $row['reference'], 'status' => 'failed', 'reason' => 'Lỗi xử lý, kiểm tra nhật ký hệ thống.'];
            }
        }
        $this->bulkSelected = [];
        $this->bulkPreview = [];
        $this->bulkDueDates = [];
        $this->bulkReviewed = false;
        $this->bulkResultsExpanded = true;
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
            'issued_on' => (string) $invoice->issued_date,
        ];
        $this->recognizedOn = now()->toDateString();
        $this->dueOn = $service->defaultDueOn((string) $invoice->issued_date);
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

    public function toggleBulkResults(): void
    {
        $this->bulkResultsExpanded = ! $this->bulkResultsExpanded;
    }

    public function updatedSearch(): void { $this->clearBulk(); $this->resetPage('invoicePage'); }
    public function updatedInvoiceType(): void { $this->clearBulk(); $this->resetPage('invoicePage'); }

    public function resetFilters(): void
    {
        $this->reset('search', 'invoiceType');
        $this->mount();
        $this->clearBulk();
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
            'canRecognize' => Schema::hasTable('permissions')
                && (bool) auth('admin')->user()?->can('finance.debt.manage'),
        ]);
    }
}
