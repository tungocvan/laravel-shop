<?php

namespace Modules\Finance\Livewire;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;

final class InvoiceDebtExplorer extends Component
{
    use WithPagination;

    public string $search = '';
    public string $invoiceType = '';

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedInvoiceType(): void { $this->resetPage(); }

    public function resetFilters(): void
    {
        $this->reset('search', 'invoiceType');
        $this->resetPage();
    }

    public function render()
    {
        abort_unless(auth('admin')->user()?->can('finance.view'), 403);

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
        ]);
    }
}
