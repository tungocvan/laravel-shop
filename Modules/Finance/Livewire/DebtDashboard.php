<?php

namespace Modules\Finance\Livewire;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Finance\Livewire\DebtWorkspace;

/**
 * Demo-only read-only principal dashboard. Never represents paid/unpaid balances.
 */
final class DebtDashboard extends Component
{
    use WithPagination;

    public string $direction = '';
    public string $status = 'confirmed';
    public string $search = '';
    public string $dueFrom = '';
    public string $dueTo = '';

    public function mount(): void
    {
        $search = request()->query('search');
        $direction = request()->query('direction');
        if (is_string($search)) {
            $this->search = mb_substr(trim($search), 0, 100);
        }
        if (in_array($direction, ['receivable', 'payable'], true)) {
            $this->direction = $direction;
        }
    }

    public function updatedDirection(): void { $this->resetPage(); }
    public function updatedStatus(): void { $this->resetPage(); }
    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedDueFrom(): void { $this->resetPage(); }
    public function updatedDueTo(): void { $this->resetPage(); }

    public function resetFilters(): void
    {
        $this->reset('direction', 'search', 'dueFrom', 'dueTo');
        $this->status = 'confirmed';
        $this->resetPage();
    }

    public function render()
    {
        abort_unless(auth('admin')->user()?->can('finance.view'), 403);
        $filters = validator([
            'direction' => $this->direction,
            'status' => $this->status,
            'search' => $this->search,
            'dueFrom' => $this->dueFrom,
            'dueTo' => $this->dueTo,
        ], [
            'direction' => 'nullable|in:receivable,payable',
            'status' => 'nullable|in:draft,confirmed',
            'search' => 'nullable|string|max:100',
            'dueFrom' => 'nullable|date_format:Y-m-d',
            'dueTo' => 'nullable|date_format:Y-m-d|after_or_equal:dueFrom',
        ])->validate();

        $query = DB::table('finance_debt_obligations as d')
            ->select('d.*')
            ->selectRaw('(d.original_amount + (SELECT COALESCE(SUM(e.signed_amount), 0) FROM finance_debt_events e WHERE e.obligation_id = d.id)) AS adjusted_principal')
            ->when($filters['direction'] !== '', fn ($q) => $q->where('d.direction', $filters['direction']))
            ->when($filters['status'] !== '', fn ($q) => $q->where('d.status', $filters['status']))
            ->when($filters['dueFrom'] !== '', fn ($q) => $q->whereDate('d.due_on', '>=', $filters['dueFrom']))
            ->when($filters['dueTo'] !== '', fn ($q) => $q->whereDate('d.due_on', '<=', $filters['dueTo']))
            ->when(trim($filters['search']) !== '', function ($q) use ($filters): void {
                $term = '%'.addcslashes(trim($filters['search']), '%_\\').'%';
                $q->where(fn ($nested) => $nested->where('d.counterparty_name', 'like', $term)
                    ->orWhere('d.reference_number', 'like', $term));
            });

        // Summarize the exact same filtered dataset used by the list.
        // The SQL sums DECIMAL columns without converting amounts to PHP floats.
        $totals = DB::query()->fromSub((clone $query)->reorder(), 'debts')
            ->selectRaw('COUNT(*) AS debt_count')
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'receivable' THEN adjusted_principal ELSE 0 END), 0) AS receivable")
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'payable' THEN adjusted_principal ELSE 0 END), 0) AS payable")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'confirmed' AND due_on < CURRENT_DATE AND adjusted_principal > 0 THEN adjusted_principal ELSE 0 END), 0) AS overdue")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'confirmed' AND due_on = CURRENT_DATE AND adjusted_principal > 0 THEN adjusted_principal ELSE 0 END), 0) AS due_today")
            ->first();

        // Operational inbox is separate from debt principal totals and independent of dashboard filters.
        // Only count invoices if the source module is enabled and both source/Finance tables exist.
        $invoiceInbox = null;
        if (config('modules.registry.Invoices.enabled', false)
            && Schema::hasTable('invoices')
            && Schema::hasTable('finance_debt_obligations')
            && Schema::hasTable('permissions')
            && auth('admin')->user()?->can('invoices-list')) {
            $invoiceInbox = DB::table('invoices as i')
                ->whereNotExists(fn ($q) => $q->selectRaw('1')
                    ->from('finance_debt_obligations as d')
                    ->where('d.source_module', 'Invoices')
                    ->where('d.source_type', 'invoice')
                    ->whereColumn('d.source_document_id', 'i.id'))
                ->selectRaw('COUNT(*) AS unrecorded_count')
                ->selectRaw("COALESCE(SUM(CASE WHEN i.invoice_type = 'sold' THEN 1 ELSE 0 END), 0) AS sold_count")
                ->selectRaw("COALESCE(SUM(CASE WHEN i.invoice_type = 'purchase' THEN 1 ELSE 0 END), 0) AS purchase_count")
                ->first();
        }
        $draftCount = DB::table('finance_debt_obligations')->where('status', 'draft')->count();

        return view('Finance::livewire.debt-dashboard', [
            'debts' => $query->orderByRaw('CASE WHEN d.due_on IS NULL THEN 1 ELSE 0 END')
                ->orderBy('d.due_on')->orderByDesc('d.id')->paginate(15),
            'totals' => $totals,
            'today' => now()->toDateString(),
            'invoiceInbox' => $invoiceInbox,
            'draftCount' => $draftCount,
        ]);
    }
}
