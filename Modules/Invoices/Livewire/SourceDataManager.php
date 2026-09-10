<?php

namespace Modules\Invoices\Livewire;

use Illuminate\Support\Arr;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Invoices\Models\Invoices;
use Modules\Invoices\Models\InvoiceExpenseCategory;
use Modules\Invoices\Models\InvoiceSourceRecord;

final class SourceDataManager extends Component
{
    use WithPagination;

    public string $search = '';
    public string $partner = '';
    public string $year = 'all';
    public string $month = 'all';
    public string $invoiceType = 'purchase';
    public string $detailStatus = 'all';
    public string $businessClassification = 'all';
    public string $sortBy = 'supplier_asc';
    public int $perPage = 25;
    public array $partnerList = [];
    public array $businessClassifications = [];
    public array $businessNotes = [];
    public array $expenseCategoryIds = [];
    public array $expenseNotes = [];
    public array $applySameTaxCode = [];
    public array $supplierBatchIds = [];
    public ?string $message = null;
    public bool $saveModalOpen = false;
    public array $saveModal = [];
    public bool $detailModalOpen = false;
    public array $detailModal = [];

    public function mount(?string $year = null, ?string $month = null, ?string $businessClassification = null): void
    {
        $this->year = $year ?? (string) now()->year;
        $this->month = $month ?? (string) now()->month;
        $this->businessClassification = $businessClassification ?? 'all';

        $this->normalizeYear();
        $this->normalizeMonth();
        if ($this->businessClassification !== 'all' && ! in_array($this->businessClassification, InvoiceSourceRecord::CLASSIFICATIONS, true)) {
            $this->businessClassification = 'all';
        }
    }

    public function updatedSearch(): void
    {
        $this->clearSupplierBatchSelection();
        $this->resetPage();
    }

    public function updatedPartner(): void
    {
        $this->clearSupplierBatchSelection();
        $this->resetPage();
    }

    public function updatedYear(): void
    {
        $this->normalizeYear();
        $this->clearSupplierBatchSelection();
        $this->resetPage();
    }

    public function updatedMonth(): void
    {
        $this->normalizeMonth();
        $this->clearSupplierBatchSelection();
        $this->resetPage();
    }

    public function updatedInvoiceType(): void
    {
        if (! in_array($this->invoiceType, ['all', 'purchase', 'sold'], true)) {
            $this->invoiceType = 'purchase';
        }

        $this->clearSupplierBatchSelection();
        $this->resetPage();
    }

    public function updatedDetailStatus(): void
    {
        if (! in_array($this->detailStatus, ['all', 'READY', 'MISSING', 'ERROR', 'FETCHING'], true)) {
            $this->detailStatus = 'all';
        }

        $this->clearSupplierBatchSelection();
        $this->resetPage();
    }

    public function updatedBusinessClassification(): void
    {
        if ($this->businessClassification !== 'all' && ! in_array($this->businessClassification, InvoiceSourceRecord::CLASSIFICATIONS, true)) {
            $this->businessClassification = 'all';
        }

        $this->businessClassifications = [];
        $this->businessNotes = [];
        $this->expenseCategoryIds = [];
        $this->expenseNotes = [];
        $this->clearSupplierBatchSelection();
        $this->resetPage();
    }

    public function updatedSortBy(): void
    {
        if (! in_array($this->sortBy, ['supplier_asc', 'supplier_desc', 'date_desc', 'date_asc'], true)) {
            $this->sortBy = 'supplier_asc';
        }

        $this->businessClassifications = [];
        $this->businessNotes = [];
        $this->expenseCategoryIds = [];
        $this->expenseNotes = [];
        $this->clearSupplierBatchSelection();
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        if (! in_array($this->perPage, [25, 50, 100], true)) {
            $this->perPage = 25;
        }

        $this->clearSupplierBatchSelection();
        $this->resetPage();
    }

    public function updatedApplySameTaxCode(mixed $value, string|int $sourceId): void
    {
        $sourceId = (int) $sourceId;
        if ($sourceId <= 0) {
            return;
        }

        if ((bool) $value) {
            $this->supplierBatchIds[] = $sourceId;
            $this->supplierBatchIds = array_values(array_unique(array_map('intval', $this->supplierBatchIds)));
        } else {
            $this->supplierBatchIds = array_values(array_filter(
                array_map('intval', $this->supplierBatchIds),
                fn (int $id) => $id !== $sourceId,
            ));
        }

        $this->rebuildApplySameTaxCodeState(array_keys($this->applySameTaxCode));
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->partner = '';
        $this->year = (string) now()->year;
        $this->month = (string) now()->month;
        $this->invoiceType = 'purchase';
        $this->detailStatus = 'all';
        $this->businessClassification = 'all';
        $this->sortBy = 'supplier_asc';
        $this->perPage = 25;
        $this->businessClassifications = [];
        $this->businessNotes = [];
        $this->expenseCategoryIds = [];
        $this->expenseNotes = [];
        $this->clearSupplierBatchSelection();
        $this->dispatch('filters-reset');
        $this->resetPage();
    }

    public function clearSupplierBatchSelection(): void
    {
        $this->supplierBatchIds = [];
        $this->applySameTaxCode = array_fill_keys(array_keys($this->applySameTaxCode), false);
    }

    public function closeSaveModal(): void
    {
        $this->saveModalOpen = false;
        $this->saveModal = [];
    }

    public function closeDetailModal(): void
    {
        $this->detailModalOpen = false;
        $this->detailModal = [];
    }

    public function applySoldMonthAsGoods(): void
    {
        abort_unless((bool) auth('admin')->user()?->can('invoices-create'), 403);

        $this->normalizeYear();
        $this->normalizeMonth();
        if ($this->invoiceType !== 'sold' || $this->year === 'all' || $this->month === 'all') {
            $this->message = 'Hãy chọn một năm, một tháng cụ thể và loại hóa đơn Bán ra trước khi áp dụng hàng loạt.';
            return;
        }

        $now = now();
        $affected = InvoiceSourceRecord::query()
            ->where('provider', 'gdt')
            ->whereHas('invoice', fn ($query) => $query
                ->where('invoice_type', 'sold')
                ->whereYear('issued_date', (int) $this->year)
                ->whereMonth('issued_date', (int) $this->month))
            ->update([
                'business_classification' => 'GOODS',
                'classification_scope' => 'INVOICE',
                'classified_by' => (int) auth('admin')->id(),
                'classified_at' => $now,
                'expense_category_id' => null,
                'expense_note' => null,
                'expense_classified_by' => null,
                'expense_classified_at' => null,
                'updated_at' => $now,
            ]);

        $this->businessClassifications = [];
        $this->expenseCategoryIds = [];
        $this->expenseNotes = [];
        $this->clearSupplierBatchSelection();
        $this->message = sprintf(
            'Đã áp dụng phân loại Hàng hóa cho %d hóa đơn bán ra của tháng %02d/%d. Bạn vẫn có thể đổi lại từng hóa đơn khi cần.',
            $affected,
            (int) $this->month,
            (int) $this->year,
        );
        $this->resetPage();
    }

    public function openDetailModal(int $sourceId): void
    {
        $source = InvoiceSourceRecord::query()->with('invoice')->findOrFail($sourceId);
        $invoice = $source->invoice;
        $items = collect($source->detail_payload['hdhhdvu'] ?? [])
            ->filter(fn ($item) => is_array($item))
            ->values()
            ->map(fn (array $item, int $index) => $this->normalizeDetailItem($item, $index + 1))
            ->all();

        $this->detailModal = [
            'invoice_number' => $invoice?->invoice_number ?: '—',
            'symbol' => $invoice?->symbol ?: '—',
            'issued_date' => $invoice?->issued_date?->format('d/m/Y') ?: '—',
            'partner' => $invoice?->name ?: 'Không rõ nhà cung cấp',
            'tax_code' => $invoice?->tax_code ?: '—',
            'invoice_type' => $invoice?->invoice_type === 'sold' ? 'Bán ra' : 'Mua vào',
            'detail_status' => $source->detail_status,
            'detail_fetched_at' => $source->detail_fetched_at?->format('d/m/Y H:i') ?: '—',
            'last_error' => $source->last_error,
            'items' => $items,
            'item_count' => count($items),
        ];
        $this->detailModalOpen = true;
    }

    public function saveAnnotation(int $sourceId): void
    {
        abort_unless((bool) auth('admin')->user()?->can('invoices-create'), 403);

        $source = InvoiceSourceRecord::query()->with(['invoice', 'expenseCategory'])->findOrFail($sourceId);
        $classification = strtoupper(trim((string) ($this->businessClassifications[$sourceId] ?? 'UNCLASSIFIED')));
        if (! in_array($classification, InvoiceSourceRecord::CLASSIFICATIONS, true)) {
            $this->addError("businessClassifications.{$sourceId}", 'Phân loại nghiệp vụ không hợp lệ.');
            return;
        }

        $note = trim((string) ($this->businessNotes[$sourceId] ?? ''));
        $expenseCategoryId = $this->validatedExpenseCategoryId($sourceId, $classification);
        if ($classification === 'SERVICE_EXPENSE' && $expenseCategoryId === false) {
            return;
        }
        $expenseNote = trim((string) ($this->expenseNotes[$sourceId] ?? ''));
        $applySupplierWide = in_array($sourceId, array_map('intval', $this->supplierBatchIds), true);
        $attributes = $this->annotationAttributes($classification, $note, $applySupplierWide, $expenseCategoryId, $expenseNote);
        $taxCode = trim((string) ($source->invoice?->tax_code ?? ''));

        if ($applySupplierWide && $taxCode !== '') {
            $updated = $this->applySupplierRule($source, $attributes);
            $this->message = "Đã lưu quy tắc nhà cung cấp {$this->classificationLabel($classification)} và áp dụng cho {$updated} hóa đơn cùng MST {$taxCode}. Hóa đơn mới cùng MST sẽ kế thừa quy tắc này.";
            $this->removeSupplierBatchSelection($sourceId);
            $source->forceFill($attributes);
            $this->showSaveModal($source, $classification, $note, true, $updated, $expenseCategoryId);
            return;
        }

        $source->forceFill($attributes)->save();
        $this->message = 'Đã cập nhật phân loại riêng cho hóa đơn #'.$source->invoice_id.'.';
        $this->showSaveModal($source, $classification, $note, false, 1, $expenseCategoryId);
    }

    public function saveSupplierBatch(): void
    {
        abort_unless((bool) auth('admin')->user()?->can('invoices-create'), 403);

        $selectedIds = collect($this->supplierBatchIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($selectedIds->isEmpty()) {
            $this->message = 'Chưa chọn nhà cung cấp nào để lưu hàng loạt.';
            return;
        }

        $sources = InvoiceSourceRecord::query()->with(['invoice', 'expenseCategory'])->whereIn('id', $selectedIds)->get();
        $processedSuppliers = [];
        $results = [];
        $affectedTotal = 0;
        $skipped = 0;

        foreach ($sources as $source) {
            $classification = strtoupper(trim((string) ($this->businessClassifications[$source->id] ?? 'UNCLASSIFIED')));
            $taxCode = trim((string) ($source->invoice?->tax_code ?? ''));
            $invoiceType = (string) ($source->invoice?->invoice_type ?? '');
            $supplierKey = $taxCode.'|'.$invoiceType;

            if ($taxCode === '' || ! in_array($classification, InvoiceSourceRecord::CLASSIFICATIONS, true) || $classification === 'UNCLASSIFIED') {
                $skipped++;
                continue;
            }
            if (isset($processedSuppliers[$supplierKey])) {
                continue;
            }

            $expenseCategoryId = $this->validatedExpenseCategoryId($source->id, $classification, false);
            if ($classification === 'SERVICE_EXPENSE' && $expenseCategoryId === false) {
                $skipped++;
                continue;
            }

            $note = trim((string) ($this->businessNotes[$source->id] ?? ''));
            $expenseNote = trim((string) ($this->expenseNotes[$source->id] ?? ''));
            $attributes = $this->annotationAttributes($classification, $note, true, $expenseCategoryId, $expenseNote);
            $affected = $this->applySupplierRule($source, $attributes);
            $processedSuppliers[$supplierKey] = true;
            $affectedTotal += $affected;
            $results[] = [
                'partner' => $source->invoice?->name ?: 'Không rõ nhà cung cấp',
                'tax_code' => $taxCode,
                'classification' => $this->classificationLabel($classification),
                'expense_category' => $this->expenseCategoryName($expenseCategoryId),
                'affected' => $affected,
            ];
        }

        if ($results === []) {
            $this->message = 'Chưa có nhà cung cấp hợp lệ để lưu. Hãy kiểm tra phân loại nghiệp vụ và phân loại chi phí cấp 2.';
            return;
        }

        $this->clearSupplierBatchSelection();
        $this->message = 'Đã lưu hàng loạt '.count($results).' nhà cung cấp, ảnh hưởng '.number_format($affectedTotal).' hóa đơn.';
        $this->saveModal = [
            'mode' => 'batch',
            'supplier_count' => count($results),
            'affected' => $affectedTotal,
            'skipped' => $skipped,
            'suppliers' => $results,
        ];
        $this->saveModalOpen = true;
    }

    public function render()
    {
        $this->normalizeYear();
        $this->normalizeMonth();
        if (! in_array($this->sortBy, ['supplier_asc', 'supplier_desc', 'date_desc', 'date_asc'], true)) {
            $this->sortBy = 'supplier_asc';
        }
        if (! in_array($this->perPage, [25, 50, 100], true)) {
            $this->perPage = 25;
        }

        $scopeQuery = InvoiceSourceRecord::query()->whereHas('invoice', function ($query): void {
            if (in_array($this->invoiceType, ['purchase', 'sold'], true)) {
                $query->where('invoice_type', $this->invoiceType);
            }
            if ($this->year !== 'all') {
                $query->whereYear('issued_date', (int) $this->year);
            }
            if ($this->month !== 'all') {
                $query->whereMonth('issued_date', (int) $this->month);
            }
            $partner = trim($this->partner);
            if ($partner !== '') {
                $query->where('name', $partner);
            }
            $search = trim($this->search);
            if ($search !== '') {
                $query->where(function ($query) use ($search): void {
                    $query->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('symbol', 'like', "%{$search}%")
                        ->orWhere('tax_code', 'like', "%{$search}%")
                        ->orWhere('lookup_code', 'like', "%{$search}%");
                });
            }
        });

        $recordsQuery = (clone $scopeQuery)
            ->with(['invoice:id,lookup_code,symbol,invoice_number,issued_date,tax_code,name,invoice_type', 'expenseCategory:id,code,name,parent_id'])
            ->when($this->detailStatus !== 'all', fn ($query) => $query->where('detail_status', $this->detailStatus))
            ->when($this->businessClassification !== 'all', fn ($query) => $query->where('business_classification', $this->businessClassification));

        $this->applySort($recordsQuery);
        $records = $recordsQuery->paginate($this->perPage);

        foreach ($records as $record) {
            $this->businessClassifications[$record->id] ??= $record->business_classification;
            $this->businessNotes[$record->id] ??= (string) ($record->business_note ?? '');
            $this->expenseCategoryIds[$record->id] ??= $record->expense_category_id ? (string) $record->expense_category_id : '';
            $this->expenseNotes[$record->id] ??= (string) ($record->expense_note ?? '');
        }
        $this->rebuildApplySameTaxCodeState($records->pluck('id')->all());

        $stats = [
            'total' => (clone $scopeQuery)->count(),
            'detail_ready' => (clone $scopeQuery)->where('detail_status', 'READY')->count(),
            'detail_missing' => (clone $scopeQuery)->whereIn('detail_status', ['MISSING', 'ERROR'])->count(),
            'unclassified' => (clone $scopeQuery)->where('business_classification', 'UNCLASSIFIED')->count(),
        ];

        $availableYears = Invoices::query()->whereHas('sourceRecord')->whereNotNull('issued_date')
            ->selectRaw('YEAR(issued_date) as invoice_year')->distinct()->orderByDesc('invoice_year')
            ->pluck('invoice_year')->map(fn ($year) => (int) $year)->values()->all();
        $currentYear = (int) now()->year;
        if (! in_array($currentYear, $availableYears, true)) {
            array_unshift($availableYears, $currentYear);
        }

        $this->partnerList = Invoices::query()->whereHas('sourceRecord')->whereNotNull('name')->where('name', '!=', '')
            ->when(in_array($this->invoiceType, ['purchase', 'sold'], true), fn ($query) => $query->where('invoice_type', $this->invoiceType))
            ->when($this->year !== 'all', fn ($query) => $query->whereYear('issued_date', (int) $this->year))
            ->when($this->month !== 'all', fn ($query) => $query->whereMonth('issued_date', (int) $this->month))
            ->distinct()->orderBy('name')->pluck('name')->values()->all();

        $expenseCategories = InvoiceExpenseCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'parent_id', 'code', 'name']);

        return view('Invoices::livewire.source-data-manager-shell', [
            'records' => $records,
            'stats' => $stats,
            'statsScopeLabel' => $this->statsScopeLabel(),
            'availableYears' => $availableYears,
            'partnerList' => $this->partnerList,
            'classificationOptions' => InvoiceSourceRecord::CLASSIFICATIONS,
            'expenseCategories' => $expenseCategories,
            'supplierBatchCount' => count($this->supplierBatchIds),
        ]);
    }

    private function applySort($query): void
    {
        if (in_array($this->sortBy, ['supplier_asc', 'supplier_desc'], true)) {
            $direction = $this->sortBy === 'supplier_desc' ? 'desc' : 'asc';
            $query->orderBy(
                Invoices::query()->select('name')->whereColumn('invoices.id', 'invoice_source_records.invoice_id')->limit(1),
                $direction,
            )->orderBy(
                Invoices::query()->select('tax_code')->whereColumn('invoices.id', 'invoice_source_records.invoice_id')->limit(1),
                $direction,
            )->orderByDesc(
                Invoices::query()->select('issued_date')->whereColumn('invoices.id', 'invoice_source_records.invoice_id')->limit(1),
            )->orderByDesc('invoice_source_records.id');
            return;
        }

        $direction = $this->sortBy === 'date_asc' ? 'asc' : 'desc';
        $query->orderBy(
            Invoices::query()->select('issued_date')->whereColumn('invoices.id', 'invoice_source_records.invoice_id')->limit(1),
            $direction,
        )->orderBy('invoice_source_records.id', $direction);
    }

    private function rebuildApplySameTaxCodeState(array $visibleIds): void
    {
        $selected = array_fill_keys(array_map('intval', $this->supplierBatchIds), true);
        $state = [];
        foreach ($visibleIds as $visibleId) {
            $visibleId = (int) $visibleId;
            if ($visibleId > 0) {
                $state[$visibleId] = isset($selected[$visibleId]);
            }
        }
        $this->applySameTaxCode = $state;
    }

    private function removeSupplierBatchSelection(int $sourceId): void
    {
        $this->supplierBatchIds = array_values(array_filter(
            array_map('intval', $this->supplierBatchIds),
            fn (int $id) => $id !== $sourceId,
        ));
        $this->rebuildApplySameTaxCodeState(array_keys($this->applySameTaxCode));
    }

    private function annotationAttributes(string $classification, string $note, bool $supplierWide, int|false|null $expenseCategoryId, string $expenseNote): array
    {
        $isExpense = $classification === 'SERVICE_EXPENSE';
        $now = now();

        return [
            'business_classification' => $classification,
            'classification_scope' => $supplierWide ? 'SUPPLIER' : 'INVOICE',
            'business_note' => $note !== '' ? $note : null,
            'classified_by' => (int) auth('admin')->id(),
            'classified_at' => $now,
            'expense_category_id' => $isExpense && is_int($expenseCategoryId) ? $expenseCategoryId : null,
            'expense_note' => $isExpense && $expenseNote !== '' ? $expenseNote : null,
            'expense_classified_by' => $isExpense && is_int($expenseCategoryId) ? (int) auth('admin')->id() : null,
            'expense_classified_at' => $isExpense && is_int($expenseCategoryId) ? $now : null,
            'updated_at' => $now,
        ];
    }

    private function validatedExpenseCategoryId(int $sourceId, string $classification, bool $reportError = true): int|false|null
    {
        if ($classification !== 'SERVICE_EXPENSE') {
            return null;
        }

        $raw = trim((string) ($this->expenseCategoryIds[$sourceId] ?? ''));
        if ($raw === '') {
            return null;
        }

        $categoryId = filter_var($raw, FILTER_VALIDATE_INT);
        $exists = $categoryId !== false && InvoiceExpenseCategory::query()->whereKey((int) $categoryId)->where('is_active', true)->exists();
        if (! $exists) {
            if ($reportError) {
                $this->addError("expenseCategoryIds.{$sourceId}", 'Phân loại chi phí cấp 2 không hợp lệ hoặc đã ngừng sử dụng.');
            }
            return false;
        }

        return (int) $categoryId;
    }

    private function expenseCategoryName(int|false|null $categoryId): string
    {
        if (! is_int($categoryId)) {
            return 'Chưa phân loại chi phí';
        }

        return InvoiceExpenseCategory::query()->whereKey($categoryId)->value('name') ?: 'Chưa phân loại chi phí';
    }

    private function applySupplierRule(InvoiceSourceRecord $source, array $attributes): int
    {
        $taxCode = trim((string) ($source->invoice?->tax_code ?? ''));
        $invoiceType = $source->invoice?->invoice_type;
        return InvoiceSourceRecord::query()
            ->where('provider', 'gdt')
            ->whereHas('invoice', fn ($query) => $query->where('tax_code', $taxCode)->when($invoiceType, fn ($query) => $query->where('invoice_type', $invoiceType)))
            ->update($attributes);
    }

    private function normalizeDetailItem(array $item, int $index): array
    {
        return [
            'index' => $index,
            'name' => $this->firstItemValue($item, ['ten', 'ten_hhdv', 'thhdvu', 'name', 'description']) ?: '—',
            'unit' => $this->firstItemValue($item, ['dvtinh', 'don_vi_tinh', 'unit']) ?: '—',
            'quantity' => $this->firstItemValue($item, ['sluong', 'so_luong', 'quantity']),
            'unit_price' => $this->firstItemValue($item, ['dgia', 'don_gia', 'unit_price']),
            'amount' => $this->firstItemValue($item, ['thtien', 'thanh_tien', 'amount']),
            'tax_rate' => $this->firstItemValue($item, ['tsuat', 'thue_suat', 'tax_rate']) ?: '—',
        ];
    }

    private function firstItemValue(array $item, array $keys): mixed
    {
        foreach ($keys as $key) {
            $value = Arr::get($item, $key);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }
        return null;
    }

    private function normalizeYear(): void
    {
        if ($this->year === 'all') {
            return;
        }
        $year = filter_var($this->year, FILTER_VALIDATE_INT);
        $currentYear = (int) now()->year;
        if ($year === false || $year < 2000 || $year > $currentYear + 1) {
            $this->year = 'all';
        }
    }

    private function normalizeMonth(): void
    {
        if ($this->month === 'all') {
            return;
        }
        $month = filter_var($this->month, FILTER_VALIDATE_INT);
        if ($month === false || $month < 1 || $month > 12) {
            $this->month = 'all';
        }
    }

    private function statsScopeLabel(): string
    {
        $period = match (true) {
            $this->month !== 'all' && $this->year !== 'all' => 'Tháng '.str_pad($this->month, 2, '0', STR_PAD_LEFT).'/'.$this->year,
            $this->month !== 'all' => 'Tháng '.str_pad($this->month, 2, '0', STR_PAD_LEFT).' · tất cả các năm',
            $this->year !== 'all' => 'Năm '.$this->year,
            default => 'Tất cả kỳ dữ liệu',
        };
        $type = match ($this->invoiceType) {
            'purchase' => 'Mua vào',
            'sold' => 'Bán ra',
            default => 'Tất cả loại',
        };
        return $this->partner !== '' ? $period.' · '.$type.' · '.$this->partner : $period.' · '.$type;
    }

    private function showSaveModal(InvoiceSourceRecord $source, string $classification, string $note, bool $supplierWide, int $affected, int|false|null $expenseCategoryId = null): void
    {
        $invoice = $source->invoice;
        $this->saveModal = [
            'mode' => 'single',
            'invoice_number' => $invoice?->invoice_number ?: '—',
            'symbol' => $invoice?->symbol ?: '—',
            'issued_date' => $invoice?->issued_date?->format('d/m/Y') ?: '—',
            'partner' => $invoice?->name ?: 'Không rõ nhà cung cấp',
            'tax_code' => $invoice?->tax_code ?: '—',
            'invoice_type' => $invoice?->invoice_type === 'sold' ? 'Bán ra' : 'Mua vào',
            'classification' => $this->classificationLabel($classification),
            'expense_category' => $classification === 'SERVICE_EXPENSE' ? $this->expenseCategoryName($expenseCategoryId) : null,
            'scope' => $supplierWide ? 'Toàn bộ nhà cung cấp cùng MST và cùng loại hóa đơn' : 'Chỉ hóa đơn này',
            'affected' => $affected,
            'note' => $note !== '' ? $note : 'Không có ghi chú',
        ];
        $this->saveModalOpen = true;
    }

    private function classificationLabel(string $classification): string
    {
        return match ($classification) {
            'GOODS' => 'Hàng hóa',
            'SERVICE_EXPENSE' => 'Dịch vụ / Chi phí',
            'MIXED' => 'Hỗn hợp',
            default => 'Chưa phân loại',
        };
    }
}
