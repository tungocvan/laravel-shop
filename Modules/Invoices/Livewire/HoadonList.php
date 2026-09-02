<?php

namespace Modules\Invoices\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Invoices\Exports\InvoicesSelectedExport;
use Modules\Invoices\Services\InvoiceFileManagerService;
use Modules\Invoices\Services\InvoicePdfService;
use Modules\Invoices\Services\InvoiceService;
use Modules\Invoices\Services\InvoiceWorkspaceService;

class HoadonList extends Component
{
    use WithPagination;

    protected InvoiceService $invoiceService;

    protected InvoicePdfService $pdfService;

    protected InvoiceFileManagerService $fileManager;

    protected InvoiceWorkspaceService $workspace;

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

    public string $pdfStatusFilter = 'all';

    public string $year = '';

    public string $month = '';

    public string $sort = 'date_desc';

    public array $yearOptions = [];

    public array $nameList = [];

    public array $taxCodeList = [];

    public array $selected = [];

    public bool $selectPage = false;

    public bool $selectAllFiltered = false;

    public int $perPage = 10;

    public array $perPageOptions = [10, 25, 50, 100];

    protected $queryString = [
        'type',
        'name',
        'tax_code',
        'from_date',
        'to_date',
        'taxRateFilter',
        'pdfStatusFilter',
        'year',
        'month',
        'sort',
        'perPage',
    ];

    public function boot(
        InvoiceService $invoiceService,
        InvoicePdfService $pdfService,
        InvoiceFileManagerService $fileManager,
        InvoiceWorkspaceService $workspace,
    ): void {
        $this->invoiceService = $invoiceService;
        $this->pdfService = $pdfService;
        $this->fileManager = $fileManager;
        $this->workspace = $workspace;
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
        $this->reconcileActivePdfFilter();
    }

    public function updatedType(): void
    {
        $this->resetListState(true);
    }

    public function updatedName(): void
    {
        $this->resetListState(true);
    }

    public function updatedTaxCode(): void
    {
        $this->resetListState(true);
    }

    public function updatedTaxRateFilter(): void
    {
        $this->resetListState();
    }

    public function updatedPdfStatusFilter(): void
    {
        $this->resetListState();
    }

    public function updatedSort(): void
    {
        $this->clearSelection();
        $this->resetPage();
    }

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
        $this->clearSelection();
        $this->resetPage();
    }

    public function updatedSelected(): void
    {
        $this->selectAllFiltered = false;
    }

    public function togglePageSelection(): void
    {
        $ids = $this->workspace->pageIds($this->filters(), $this->perPage);

        if ($this->selectPage) {
            $this->selected = collect($this->selected)
                ->merge($ids)
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        } else {
            $this->selected = array_values(array_diff(array_map('intval', $this->selected), $ids));
        }

        $this->selectAllFiltered = false;
    }

    public function selectAllFilteredResults(): void
    {
        $this->selected = $this->workspace->allFilteredIds($this->filters());
        $this->selectPage = true;
        $this->selectAllFiltered = true;
    }

    public function clearSelection(): void
    {
        $this->selected = [];
        $this->selectPage = false;
        $this->selectAllFiltered = false;
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
        $this->pdfStatusFilter = 'all';
        $this->sort = 'date_desc';
        $this->clearSelection();
        $this->pdfNotice = null;
        $this->pdfError = null;
        $this->refreshOptions();
        $this->resetPage();
        $this->dispatch('filters-reset');
    }

    public function exportSelected()
    {
        $this->authorizePermission('invoices-export');
        $records = $this->workspace->exportRecords($this->filters(), $this->selected);

        return Excel::download(
            new InvoicesSelectedExport($records),
            'hoadon_'.($this->selected === [] ? 'loc' : 'chon').'_'.now()->format('Ymd_His').'.xlsx',
        );
    }

    public function downloadSelected(): void
    {
        $this->authorizePermission('invoices-download');
        $this->clearPdfMessages();

        if ($this->selected === []) {
            $this->pdfError = 'Vui lòng chọn hóa đơn trước khi tải PDF.';

            return;
        }

        $this->downloadStatus = 'processing';
        $result = $this->pdfService->downloadSelected($this->selected);
        $this->downloadStatus = $result['failed'] > 0 ? 'error' : 'success';
        $this->setBatchMessage($result, 'PDF mới');
    }

    public function downloadPdf(int $invoiceId, bool $force = false): void
    {
        $this->authorizePermission('invoices-download');
        $this->clearPdfMessages();
        $this->pdfProcessingId = $invoiceId;

        try {
            $path = $this->pdfService->downloadInvoice($invoiceId, $force);
            $this->pdfNotice = ($force ? 'Đã tải lại PDF hóa đơn: ' : 'Đã tải PDF hóa đơn: ').basename($path);
        } catch (\Throwable $e) {
            $this->pdfError = $e->getMessage();
        } finally {
            $this->pdfProcessingId = null;
        }
    }

    public function reconcilePdfMetadata(): void
    {
        $this->authorizePermission('invoices-download');
        $this->clearPdfMessages();

        try {
            $result = $this->fileManager->reconcile($this->filters());
            $this->pdfNotice = "Đã quét {$result['scanned']} hóa đơn · Có PDF: {$result['available']} · Chưa có: {$result['missing']}.";
        } catch (\Throwable $e) {
            $this->pdfError = $e->getMessage();
        }
    }

    public function downloadMissingPdfs(): void
    {
        $this->authorizePermission('invoices-download');
        $this->clearPdfMessages();

        try {
            $this->fileManager->reconcile($this->filters());
            $ids = $this->fileManager->missingInvoiceIds($this->filters(), 25);

            if ($ids === []) {
                $this->pdfNotice = 'Không còn PDF nào thiếu trong bộ lọc hiện tại.';

                return;
            }

            $result = $this->pdfService->downloadSelected($ids);
            $remaining = $this->fileManager->summary($this->filters())['missing'];
            $this->applyResultMessage(
                $result,
                "Batch PDF: mới {$result['downloaded']} · đã có {$result['existing']} · lỗi {$result['failed']} · còn thiếu {$remaining}.",
            );
        } catch (\Throwable $e) {
            $this->pdfError = $e->getMessage();
        }
    }

    public function retryPdfErrors(): void
    {
        $this->authorizePermission('invoices-download');
        $this->clearPdfMessages();

        try {
            $ids = $this->fileManager->errorInvoiceIds($this->filters(), 25);

            if ($ids === []) {
                $this->pdfNotice = 'Không có PDF lỗi cần thử lại trong bộ lọc hiện tại.';

                return;
            }

            $result = $this->pdfService->downloadSelected($ids, true);
            $this->applyResultMessage(
                $result,
                "Retry PDF: thành công {$result['downloaded']} · lỗi {$result['failed']}.",
            );
        } catch (\Throwable $e) {
            $this->pdfError = $e->getMessage();
        }
    }

    public function deleteSelectedPdfs(): void
    {
        $this->authorizePermission('invoices-download');
        $this->clearPdfMessages();

        if ($this->selected === []) {
            $this->pdfError = 'Vui lòng checkbox chọn hóa đơn cần xóa PDF.';

            return;
        }

        try {
            $result = $this->fileManager->deleteFilesByIds($this->selected);
            $this->clearSelection();
            $this->pdfNotice = "Đã xóa {$result['deleted']} PDF đã chọn"
                .($result['skipped'] ? " · {$result['skipped']} hóa đơn không có PDF" : '')
                .($result['failed'] ? " · {$result['failed']} file không xóa được." : '.');
        } catch (\Throwable $e) {
            $this->pdfError = $e->getMessage();
        }
    }

    public function downloadPdfZip()
    {
        $this->authorizePermission('invoices-download');
        $this->clearPdfMessages();

        try {
            $this->fileManager->reconcile($this->filters());
            $archive = $this->fileManager->createZip($this->filters());
            $this->pdfNotice = "Đã đóng gói {$archive['count']} PDF.";

            return response()->download($archive['path'], $archive['filename']);
        } catch (\Throwable $e) {
            $this->pdfError = $e->getMessage();

            return null;
        }
    }

    public function render()
    {
        $data = $this->workspace->viewData($this->filters(), $this->perPage, $this->selected);
        $this->selectPage = $data['pageSelected'];
        unset($data['pageSelected']);

        return view('Invoices::livewire.hoadon-list', $data);
    }

    private function applyPeriodSelection(bool $reset = true): void
    {
        $year = (int) $this->year;

        if ($year < 2000 || $year > 2100) {
            $this->year = '';
            $this->month = '';

            return;
        }

        if ($this->month !== '' && ((int) $this->month < 1 || (int) $this->month > 12)) {
            $this->month = '';
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
        $this->clearSelection();

        if ($refreshOptions) {
            $this->refreshOptions();
        }

        $this->reconcileActivePdfFilter();
        $this->resetPage();
    }

    private function refreshOptions(): void
    {
        $options = $this->invoiceService->filterOptions($this->filters());
        $this->nameList = $options['names'];
        $this->taxCodeList = $options['tax_codes'];
    }

    private function reconcileActivePdfFilter(): void
    {
        if ($this->pdfStatusFilter === 'all') {
            return;
        }

        $admin = auth('admin')->user();
        if (! $admin || ! $admin->can('invoices-download')) {
            return;
        }

        $this->fileManager->reconcile($this->filters());
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
            'pdf_status' => $this->pdfStatusFilter,
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

    private function clearPdfMessages(): void
    {
        $this->pdfNotice = null;
        $this->pdfError = null;
    }

    private function setBatchMessage(array $result, string $prefix): void
    {
        $this->applyResultMessage(
            $result,
            "{$prefix}: {$result['downloaded']} · Đã có: {$result['existing']} · Lỗi: {$result['failed']}",
        );
    }

    private function applyResultMessage(array $result, string $message): void
    {
        if (($result['failed'] ?? 0) > 0) {
            $detail = $result['errors'][0] ?? null;
            $this->pdfError = $detail ? $message.' · '.$detail : $message;
        } else {
            $this->pdfNotice = $message;
        }
    }
}
