<?php

namespace Modules\Finance\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

final class DebtLedger extends Component
{
    use WithPagination;

    public string $search = '';
    public string $direction = '';
    public string $status = 'confirmed';
    public string $dateFrom = '';
    public string $dateTo = '';

    public function updatedSearch(): void { $this->resetPage('ledgerPage'); }
    public function updatedDirection(): void { $this->resetPage('ledgerPage'); }
    public function updatedStatus(): void { $this->resetPage('ledgerPage'); }
    public function updatedDateFrom(): void { $this->resetPage('ledgerPage'); }
    public function updatedDateTo(): void { $this->resetPage('ledgerPage'); }

    public function resetFilters(): void
    {
        $this->reset('search', 'direction', 'dateFrom', 'dateTo');
        $this->status = 'confirmed';
        $this->resetPage('ledgerPage');
    }

    public function render()
    {
        abort_unless(auth('admin')->user()?->can('finance.view'), 403);
        $filters = validator([
            'search' => $this->search,
            'direction' => $this->direction,
            'status' => $this->status,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
        ], [
            'search' => 'nullable|string|max:100',
            'direction' => 'nullable|in:receivable,payable',
            'status' => 'nullable|in:draft,confirmed',
            'dateFrom' => 'nullable|date_format:Y-m-d',
            'dateTo' => 'nullable|date_format:Y-m-d|after_or_equal:dateFrom',
        ])->validate();

        $debts = DB::table('finance_debt_obligations as d')
            ->select('d.id', 'd.reference_number', 'd.counterparty_name', 'd.counterparty_tax_code',
                'd.direction', 'd.status', 'd.original_amount', 'd.recognized_on', 'd.due_on')
            ->selectRaw('(SELECT COALESCE(SUM(e.signed_amount), 0) FROM finance_debt_events e WHERE e.obligation_id = d.id) AS adjustment_total')
            ->selectRaw('(d.original_amount + (SELECT COALESCE(SUM(e.signed_amount), 0) FROM finance_debt_events e WHERE e.obligation_id = d.id)) AS adjusted_principal')
            ->when($filters['direction'] !== '', fn ($q) => $q->where('d.direction', $filters['direction']))
            ->when($filters['status'] !== '', fn ($q) => $q->where('d.status', $filters['status']))
            ->when($filters['dateFrom'] !== '', fn ($q) => $q->whereDate('d.due_on', '>=', $filters['dateFrom']))
            ->when($filters['dateTo'] !== '', fn ($q) => $q->whereDate('d.due_on', '<=', $filters['dateTo']))
            ->when(trim($filters['search']) !== '', function ($q) use ($filters): void {
                $term = '%'.addcslashes(trim($filters['search']), '%_\\').'%';
                $q->where(fn ($nested) => $nested->where('d.counterparty_name', 'like', $term)
                    ->orWhere('d.counterparty_tax_code', 'like', $term)
                    ->orWhere('d.reference_number', 'like', $term));
            })
            ->orderByDesc('d.id')
            ->paginate(15, ['*'], 'ledgerPage');

        return view('Finance::livewire.debt-ledger', ['debts' => $debts]);
    }
}
