<?php

namespace Modules\Invoices\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Invoices\Models\InvoiceSourceRecord;

final class SourceDataManager extends Component
{
    use WithPagination;

    public string $search = '';

    public string $invoiceType = 'purchase';

    public string $detailStatus = 'all';

    public string $businessClassification = 'all';

    public array $businessClassifications = [];

    public array $businessNotes = [];

    public array $applySameTaxCode = [];

    public ?string $message = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedInvoiceType(): void
    {
        $this->resetPage();
    }

    public function updatedDetailStatus(): void
    {
        $this->resetPage();
    }

    public function updatedBusinessClassification(): void
    {
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
            $this->message = "Đã lưu quy tắc nhà cung cấp {$classification} và áp dụng cho {$updated} hóa đơn cùng MST {$taxCode}. Hóa đơn mới cùng MST sẽ kế thừa quy tắc này.";

            return;
        }

        $source->forceFill($attributes)->save();
        $this->message = 'Đã cập nhật phân loại riêng cho hóa đơn #'.$source->invoice_id.'.';
    }

    public function render()
    {
        $query = InvoiceSourceRecord::query()
            ->with('invoice:id,lookup_code,symbol,invoice_number,issued_date,tax_code,name,invoice_type')
            ->whereHas('invoice', function ($query): void {
                if (in_array($this->invoiceType, ['purchase', 'sold'], true)) {
                    $query->where('invoice_type', $this->invoiceType);
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

        $records = $query->paginate(25);

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

        return view('Invoices::livewire.source-data-manager', [
            'records' => $records,
            'stats' => $stats,
            'classificationOptions' => InvoiceSourceRecord::CLASSIFICATIONS,
        ]);
    }
}
