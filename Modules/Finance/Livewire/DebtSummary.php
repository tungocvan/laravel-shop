<?php

namespace Modules\Finance\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

final class DebtSummary extends Component
{
    use WithPagination;

    public string $search = '';
    public string $direction = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedDirection(): void { $this->resetPage(); }
    public function updatedDateFrom(): void { $this->resetPage(); }
    public function updatedDateTo(): void { $this->resetPage(); }

    public function resetFilters(): void
    {
        $this->reset('search', 'direction', 'dateFrom', 'dateTo');
        $this->resetPage();
    }

    public function render()
    {
        abort_unless(auth('admin')->user()?->can('finance.view'), 403);

        $filters = validator([
            'search' => $this->search,
            'direction' => $this->direction,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
        ], [
            'search' => 'nullable|string|max:100',
            'direction' => 'nullable|in:receivable,payable',
            'dateFrom' => 'nullable|date_format:Y-m-d',
            'dateTo' => 'nullable|date_format:Y-m-d|after_or_equal:dateFrom',
        ])->validate();

        // Only confirmed obligations are reported. Dates select recognition cohorts,
        // NOT an accounting period movement or a beginning/ending balance.
        $base = DB::table('finance_debt_obligations as d')
            ->select('d.counterparty_name', 'd.counterparty_tax_code', 'd.direction', 'd.original_amount')
            ->selectRaw('(SELECT COALESCE(SUM(e.signed_amount), 0) FROM finance_debt_events e WHERE e.obligation_id = d.id) AS adjustment_total')
            ->where('d.status', 'confirmed')
            ->when($filters['direction'] !== '', fn ($q) => $q->where('d.direction', $filters['direction']))
            ->when($filters['dateFrom'] !== '', fn ($q) => $q->whereDate('d.recognized_on', '>=', $filters['dateFrom']))
            ->when($filters['dateTo'] !== '', fn ($q) => $q->whereDate('d.recognized_on', '<=', $filters['dateTo']))
            ->when(trim($filters['search']) !== '', function ($q) use ($filters): void {
                $term = '%'.addcslashes(trim($filters['search']), '%_\\').'%';
                $q->where(fn ($nested) => $nested->where('d.counterparty_name', 'like', $term)
                    ->orWhere('d.counterparty_tax_code', 'like', $term));
            });

        $groups = DB::query()->fromSub($base, 'debts')
            ->select('counterparty_name', 'counterparty_tax_code', 'direction')
            ->selectRaw('COUNT(*) AS debt_count')
            ->selectRaw('COALESCE(SUM(original_amount), 0) AS original_total')
            ->selectRaw('COALESCE(SUM(adjustment_total), 0) AS adjustment_total')
            ->selectRaw('COALESCE(SUM(original_amount + adjustment_total), 0) AS adjusted_total')
            ->groupBy('counterparty_name', 'counterparty_tax_code', 'direction');

        $totals = DB::query()->fromSub((clone $groups)->reorder(), 'groups')
            ->selectRaw('COALESCE(SUM(debt_count), 0) AS debt_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN direction = \'receivable\' THEN adjusted_total ELSE 0 END), 0) AS receivable')
            ->selectRaw('COALESCE(SUM(CASE WHEN direction = \'payable\' THEN adjusted_total ELSE 0 END), 0) AS payable')
            ->first();

        return view('Finance::livewire.debt-summary', [
            'groups' => $groups->orderBy('counterparty_name')->orderBy('direction')->paginate(15),
            'totals' => $totals,
        ]);
    }
}
