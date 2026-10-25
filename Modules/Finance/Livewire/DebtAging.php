<?php

namespace Modules\Finance\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

final class DebtAging extends Component
{
    use WithPagination;

    public string $search = '';
    public string $direction = '';
    public string $bucket = '';

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedDirection(): void { $this->resetPage(); }
    public function updatedBucket(): void { $this->resetPage(); }

    public function resetFilters(): void
    {
        $this->reset('search', 'direction', 'bucket');
        $this->resetPage();
    }

    public function render()
    {
        abort_unless(auth('admin')->user()?->can('finance.view'), 403);
        $filters = validator([
            'search' => $this->search,
            'direction' => $this->direction,
            'bucket' => $this->bucket,
        ], [
            'search' => 'nullable|string|max:100',
            'direction' => 'nullable|in:receivable,payable',
            'bucket' => 'nullable|in:upcoming,due_today,overdue_1_30,overdue_31_60,overdue_61_90,overdue_90_plus,undated',
        ])->validate();

        $today = now()->startOfDay();
        $todayDate = $today->toDateString();
        $day30 = $today->copy()->subDays(30)->toDateString();
        $day60 = $today->copy()->subDays(60)->toDateString();
        $day90 = $today->copy()->subDays(90)->toDateString();

        // Classification is a snapshot of confirmed principal, not unpaid balance.
        // The exact date boundaries are inclusive and do not overlap.
        $base = DB::table('finance_debt_obligations as d')
            ->select('d.id', 'd.reference_number', 'd.counterparty_name', 'd.counterparty_tax_code',
                'd.direction', 'd.due_on', 'd.original_amount')
            ->selectRaw('(d.original_amount + (SELECT COALESCE(SUM(e.signed_amount), 0) FROM finance_debt_events e WHERE e.obligation_id = d.id)) AS adjusted_principal')
            ->where('d.status', 'confirmed')
            ->when($filters['direction'] !== '', fn ($q) => $q->where('d.direction', $filters['direction']))
            ->when(trim($filters['search']) !== '', function ($q) use ($filters): void {
                $term = '%'.addcslashes(trim($filters['search']), '%_\\').'%';
                $q->where(fn ($nested) => $nested->where('d.counterparty_name', 'like', $term)
                    ->orWhere('d.counterparty_tax_code', 'like', $term)
                    ->orWhere('d.reference_number', 'like', $term));
            });

        $classified = DB::query()->fromSub($base, 'debts')
            ->select('debts.*')
            ->selectRaw("CASE
                WHEN due_on IS NULL THEN 'undated'
                WHEN due_on > ? THEN 'upcoming'
                WHEN due_on = ? THEN 'due_today'
                WHEN due_on >= ? THEN 'overdue_1_30'
                WHEN due_on >= ? THEN 'overdue_31_60'
                WHEN due_on >= ? THEN 'overdue_61_90'
                ELSE 'overdue_90_plus' END AS aging_bucket", [
                $todayDate, $todayDate, $day30, $day60, $day90,
            ]);

        $filtered = DB::query()->fromSub($classified, 'classified')
            ->when($filters['bucket'] !== '', fn ($q) => $q->where('aging_bucket', $filters['bucket']));

        $totals = DB::query()->fromSub((clone $filtered)->reorder(), 'aged')
            ->select('aging_bucket')
            ->selectRaw('COUNT(*) AS debt_count')
            ->selectRaw('COALESCE(SUM(adjusted_principal), 0) AS principal_total')
            ->groupBy('aging_bucket')->get()->keyBy('aging_bucket');

        return view('Finance::livewire.debt-aging', [
            'debts' => $filtered->orderByRaw('CASE WHEN due_on IS NULL THEN 1 ELSE 0 END')
                ->orderBy('due_on')->orderByDesc('id')->paginate(15),
            'totals' => $totals,
            'today' => $todayDate,
        ]);
    }
}
