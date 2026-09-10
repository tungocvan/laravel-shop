<?php

namespace Modules\Invoices\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Invoices\Models\Invoices;
use Modules\Invoices\Models\InvoiceSourceRecord;

final class SourceDataManager extends Component
{
    use WithPagination;

    public string $search = '';

    public string $year = 'all';

    public string $month = 'all';

    public string $invoiceType = 'purchase';

    public string $detailStatus = 'all';

    public string $businessClassification = 'all';

    public int $perPage = 25;

    public array $businessClassifications = [];

    public array $businessNotes = [];

    public array $applySameTaxCode = [];

    public ?string $message = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedYear(): void
    {
        $this->normalizeYear();
        $this->resetPage();
    }

    public function updatedMonth(): void
    {
        $this->normalizeMonth();
        $this->resetPage();
    }

    public function updatedInvoiceType(): void
    {
        if (! in_array($this->invoiceType, ['all', 'purchase', 'sold'], true)) {
            $this->invoiceType = 'purchase';
        }

        $this->resetPage();
    }

    public function updatedDetailStatus(): void
    {
        if (! in_array($this->detailStatus, ['all', 'READY', 'MISSING', 'ERROR', 'FETCHING'], true)) {
            $this->detailStatus = 'all';
        }

        $this->resetPage();
    }

    public function updatedBusinessClassification(): void
    {
        if ($this->businessClassification !== 'all' && ! in_array($this->businessClassification, InvoiceSourceRecord::CLASSIFICATIONS, true)) {
            $this->businessClassification = 'all';
        }

        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        if (! in_array($this->perPage, [25, 50, 100], true)) {
            $this->perPage = 25;
        }

        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->year = 'all';
        $this->month = 'all';
        $this->invoiceType = 'purchase';
        $this->detailStatus = 'all';
        $this->businessClassification = 'all';
        $this->perPage = 25;
        $this->resetPage();
    }

    public function saveAnnotation(int $sourceId): void
    {
        abort_unless((bool) auth('admin')->user()?->can('invoices-create'), 403);

        $source = InvoiceSourceRecord::query()->with('invoice')->findOrFail($sourceId);
        $classification = strtoupper(trim((string) ($this->businessClassifications[$sourceId] ?? 'UNCLASSIFIED')));

        if (! in_array($classification, InvoiceSourceRecord::CLASSIFICATIONS, true)) {
            $this->addError("businessClassifications.{$sourceId}", 'Phân loại nghiệp vụ không hợp lệ.');

            return;
        }

        $note = trim((string) ($this->businessNotes[$sourceId] ?? ''));
        $applySupplierWide = (bool) ($this->applySameTaxCode[$sourceId] ?? false);
        $attributes = [
            'business_classification' => $classification,
            'classification_scope' => $applySupplierWide ? 'SUPPLIER' : 'INVOICE',
            'business_note' => $note !== '' ? $note : null,
            'classified_by' => (int) auth('admin')->id(),
            'classified_at' => now(),
            'updated_at' => now(),
        ];

        $taxCode = trim((string) ($source->invoice?->tax_code ?? ''));

        if ($applySupplierWide && $taxCode !== '') {
            $invoiceType = $source->invoice?->invoice_type;
            $query = InvoiceSourceRecord::query()
                ->where('provider', 'gdt')
                ->whereHas('invoice', fn ($query) => $query
                    ->where('tax_code', $taxCode)
                    ->when($invoiceType, fn ($query) => $query->where('invoice_type', $invoiceType)));

            $updated = $query->update($attributes);
            $this->message = "Đã lưu quy tắc nhà cung cấp {$this->classificationLabel($classification)} và áp dụng cho {$updated} hóa đơn cùng MST {$taxCode}. Hóa đơn mới cùng MST sẽ kế thừa quy tắc này.";

            return;
        }

        $source->forceFill($attributes)->save();
        $this->message = 'Đã cập nhật phân loại riêng cho hóa đơn #'.$source->invoice_id.'.';
    }

    public function render()
    {
        $this->normalizeYear();
        $this->normalizeMonth();

        if (! in_array($this->perPage, [25, 50, 100], true)) {
            $this->perPage = 25;
        }

        $query = InvoiceSourceRecord::query()
            ->with('invoice:id,lookup_code,symbol,invoice_number,issued_date,tax_code,name,invoice_type')
            ->whereHas('invoice', function ($query): void {
                if (in_array($this->invoiceType, ['purchase', 'sold'], true)) {
                    $query->where('invoice_type', $this->invoiceType);
                }

                if ($this->year !== 'all') {
                    $query->whereYear('issued_date', (int) $this->year);
                }

                if ($this->month !== 'all') {
                    $query->whereMonth('issued_date', (int) $this->month);
                }

                $search = trim($this->search);
                if ($search !== '') {
                    $query->where(function ($query) use ($search): void {
                        $query->where('invoice_number', 'like', "%{$search}%")
                            ->orWhere('symbol', 'like', "%{$search}%")
                            ->orWhere('tax_code', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%")
                            ->orWhere('lookup_code', 'like', "%{$search}%");
                    });
                }
            })
            ->when($this->detailStatus !== 'all', fn ($query) => $query->where('detail_status', $this->detailStatus))
            ->when($this->businessClassification !== 'all', fn ($query) => $query->where('business_classification', $this->businessClassification))
            ->latest('id');

        $records = $query->paginate($this->perPage);

        foreach ($records as $record) {
            $this->businessClassifications[$record->id] ??= $record->business_classification;
            $this->businessNotes[$record->id] ??= (string) ($record->business_note ?? '');
            $this->applySameTaxCode[$record->id] ??= $record->classification_scope === 'SUPPLIER';
        }

        $stats = [
            'total' => InvoiceSourceRecord::query()->count(),
            'detail_ready' => InvoiceSourceRecord::query()->where('detail_status', 'READY')->count(),
            'detail_missing' => InvoiceSourceRecord::query()->whereIn('detail_status', ['MISSING', 'ERROR'])->count(),
            'unclassified' => InvoiceSourceRecord::query()->where('business_classification', 'UNCLASSIFIED')->count(),
        ];

        $availableYears = Invoices::query()
            ->whereHas('sourceRecord')
            ->whereNotNull('issued_date')
            ->selectRaw('YEAR(issued_date) as invoice_year')
            ->distinct()
            ->orderByDesc('invoice_year')
            ->pluck('invoice_year')
            ->map(fn ($year) => (int) $year)
            ->values()
            ->all();

        return view('Invoices::livewire.source-data-manager', [
            'records' => $records,
            'stats' => $stats,
            'availableYears' => $availableYears,
            'classificationOptions' => InvoiceSourceRecord::CLASSIFICATIONS,
        ]);
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
