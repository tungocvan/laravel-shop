<?php

namespace Modules\Finance\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

final class DebtPartners extends Component
{
    use WithPagination;

    public string $search = '';
    public string $direction = '';
    public string $status = 'confirmed';

    public function updatedSearch(): void { $this->resetPage('partnersPage'); }
    public function updatedDirection(): void { $this->resetPage('partnersPage'); }
    public function updatedStatus(): void { $this->resetPage('partnersPage'); }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->direction = '';
        $this->status = 'confirmed';
        $this->resetPage('partnersPage');
    }

    public function render()
    {
        abort_unless(auth('admin')->user()?->can('finance.view'), 403);
        $filters = validator([
            'search' => $this->search, 'direction' => $this->direction, 'status' => $this->status,
        ], [
            'search' => 'nullable|string|max:100',
            'direction' => 'nullable|in:receivable,payable',
            'status' => 'nullable|in:draft,confirmed',
        ])->validate();

        $base = DB::table('finance_debt_obligations as d')
            ->select('d.counterparty_name', 'd.counterparty_tax_code', 'd.direction', 'd.status', 'd.due_on')
            ->selectRaw('(d.original_amount + (SELECT COALESCE(SUM(e.signed_amount), 0) FROM finance_debt_events e WHERE e.obligation_id = d.id)) AS adjusted_principal')
            ->when($filters['direction'] !== '', fn ($q) => $q->where('d.direction', $filters['direction']))
            ->when($filters['status'] !== '', fn ($q) => $q->where('d.status', $filters['status']))
            ->when(trim($filters['search']) !== '', function ($q) use ($filters): void {
                $term = '%'.addcslashes(trim($filters['search']), '%_\\').'%';
                $q->where(fn ($nested) => $nested->where('d.counterparty_name', 'like', $term)
                    ->orWhere('d.counterparty_tax_code', 'like', $term));
            });

        $partners = DB::query()->fromSub($base, 'debts')
            ->select('counterparty_name', 'counterparty_tax_code', 'direction')
            ->selectRaw('COUNT(*) AS debt_count')
            ->selectRaw('COALESCE(SUM(adjusted_principal), 0) AS principal_total')
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'confirmed' AND due_on < CURRENT_DATE AND adjusted_principal > 0 THEN adjusted_principal ELSE 0 END), 0) AS overdue_total")
            ->groupBy('counterparty_name', 'counterparty_tax_code', 'direction')
            ->orderBy('counterparty_name')->orderBy('direction')->paginate(15, ['*'], 'partnersPage');

        return view('Finance::livewire.debt-partners', ['partners' => $partners]);
    }
}
