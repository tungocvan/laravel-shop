<?php

namespace Modules\Muasamcong\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Muasamcong\Models\KqlcntAwardItem;

final class KqlcntAwardReadService
{
    private function base(): Builder
    {
        return KqlcntAwardItem::query()->whereNotNull('synced_from_catalog_at');
    }

    /**
     * One result per TBMT and contractor, never one result per medicine line.
     * A missing contractor code is disambiguated by contractor name.
     */
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->base()
            ->when($filters['notify_no'] ?? null, fn (Builder $q, string $v) => $q->where('notify_no', $v))
            ->when($filters['investor_code'] ?? null, fn (Builder $q, string $v) => $q->where('investor_code', $v))
            ->when($filters['search'] ?? null, function (Builder $query, string $term): void {
                $query->where(function (Builder $q) use ($term): void {
                    $q->where('notify_no', 'like', '%'.$term.'%')
                        ->orWhere('contractor_name', 'like', '%'.$term.'%')
                        ->orWhere('investor_name', 'like', '%'.$term.'%');
                });
            })
            ->when($filters['assigned_group_ids'] ?? null, function (Builder $query, array $ids): void {
                // Filter complete groups, never individual medicine lines.
                $query->whereExists(function ($sub) use ($ids): void {
                    $sub->selectRaw('1')->from('muasamcong_kqlcnt_award_items as group_member')
                        ->whereColumn('group_member.notify_no', 'muasamcong_kqlcnt_award_items.notify_no')
                        ->whereRaw('(group_member.contractor_code = muasamcong_kqlcnt_award_items.contractor_code OR (group_member.contractor_code IS NULL AND muasamcong_kqlcnt_award_items.contractor_code IS NULL))')
                        ->whereRaw('(group_member.contractor_name = muasamcong_kqlcnt_award_items.contractor_name OR (group_member.contractor_name IS NULL AND muasamcong_kqlcnt_award_items.contractor_name IS NULL))')
                        ->whereIn('group_member.id', $ids);
                });
            })
            ->when($filters['unassigned_group_ids'] ?? null, function (Builder $query, array $ids): void {
                $query->whereNotExists(function ($sub) use ($ids): void {
                    $sub->selectRaw('1')->from('muasamcong_kqlcnt_award_items as group_member')
                        ->whereColumn('group_member.notify_no', 'muasamcong_kqlcnt_award_items.notify_no')
                        ->whereRaw('(group_member.contractor_code = muasamcong_kqlcnt_award_items.contractor_code OR (group_member.contractor_code IS NULL AND muasamcong_kqlcnt_award_items.contractor_code IS NULL))')
                        ->whereRaw('(group_member.contractor_name = muasamcong_kqlcnt_award_items.contractor_name OR (group_member.contractor_name IS NULL AND muasamcong_kqlcnt_award_items.contractor_name IS NULL))')
                        ->whereIn('group_member.id', $ids);
                });
            })
            ->when($filters['medicine'] ?? null, function (Builder $query, string $term): void {
                // Filter the entire TBMT/contractor group by matching medicine lines.
                // Do not exclude non-matching lines from the group's totals.
                $matches = $this->base()->select('notify_no', 'contractor_code', 'contractor_name')
                    ->where(function (Builder $q) use ($term): void {
                        $q->where('medicine_name', 'like', '%'.$term.'%')
                            ->orWhere('active_ingredient', 'like', '%'.$term.'%');
                    });
                $query->whereExists(function ($sub) use ($matches): void {
                    $sub->selectRaw('1')->fromSub($matches, 'matches')
                        ->whereColumn('matches.notify_no', 'muasamcong_kqlcnt_award_items.notify_no')
                        ->whereRaw('(matches.contractor_code = muasamcong_kqlcnt_award_items.contractor_code OR (matches.contractor_code IS NULL AND muasamcong_kqlcnt_award_items.contractor_code IS NULL))')
                        ->whereRaw('(matches.contractor_name = muasamcong_kqlcnt_award_items.contractor_name OR (matches.contractor_name IS NULL AND muasamcong_kqlcnt_award_items.contractor_name IS NULL))');
                });
            })
            ;

        return $query->selectRaw('MIN(id) AS id, notify_no, contractor_code, contractor_name')
            ->selectRaw('MAX(investor_name) AS investor_name, COUNT(*) AS products_count')
            ->selectRaw('SUM(COALESCE(amount, quantity * winning_price, 0)) AS winning_amount')
            ->selectRaw('MAX(contract_period_text) AS contract_period_text, MAX(contract_period) AS contract_period, MAX(contract_period_unit) AS contract_period_unit')
            ->selectRaw('MIN(is_active) AS all_active, MAX(is_active) AS any_active')
            ->groupBy('notify_no', 'contractor_code', 'contractor_name')
            ->when(isset($filters['active']) && $filters['active'] !== '', function (Builder $q) use ($filters): void {
                // Filter entire records; do not shrink the medicine counts or amounts.
                $q->havingRaw($filters['active'] === '1' ? 'MIN(is_active) = 1' : 'MAX(is_active) = 0');
            })
            ->orderByDesc('id')
            ->paginate($perPage)->withQueryString();
    }


    public function filterOptions(): array
    {
        $base = $this->base();

        return [
            'notices' => (clone $base)->whereNotNull('notify_no')->distinct()->orderBy('notify_no')->pluck('notify_no'),
            'medicines' => (clone $base)->whereNotNull('medicine_name')->where('medicine_name', '<>', '')->distinct()->orderBy('medicine_name')->pluck('medicine_name'),
            'investors' => (clone $base)->whereNotNull('investor_code')
                ->select('investor_code', 'investor_name')->distinct()->orderBy('investor_name')->get(),
        ];
    }

    /** Dashboard for an explicit allowlist of complete TBMT/contractor groups. */
    public function dashboardForGroupIds(array $ids): array
    {
        if ($ids === []) {
            return ['total' => 0, 'active' => 0, 'amount' => 0, 'active_amount' => 0];
        }

        $groups = $this->base()->whereExists(function ($sub) use ($ids): void {
            $sub->selectRaw('1')->from('muasamcong_kqlcnt_award_items as permitted')
                ->whereColumn('permitted.notify_no', 'muasamcong_kqlcnt_award_items.notify_no')
                ->whereRaw('(permitted.contractor_code = muasamcong_kqlcnt_award_items.contractor_code OR (permitted.contractor_code IS NULL AND muasamcong_kqlcnt_award_items.contractor_code IS NULL))')
                ->whereRaw('(permitted.contractor_name = muasamcong_kqlcnt_award_items.contractor_name OR (permitted.contractor_name IS NULL AND muasamcong_kqlcnt_award_items.contractor_name IS NULL))')
                ->whereIn('permitted.id', $ids);
        })->selectRaw('notify_no, contractor_code, contractor_name')
            ->selectRaw('SUM(COALESCE(amount, quantity * winning_price, 0)) AS winning_amount')
            ->selectRaw('MIN(is_active) AS all_active')
            ->groupBy('notify_no', 'contractor_code', 'contractor_name')->get();

        return [
            'total' => $groups->count(),
            'active' => $groups->where('all_active', 1)->count(),
            'amount' => $groups->sum('winning_amount'),
            'active_amount' => $groups->where('all_active', 1)->sum('winning_amount'),
        ];
    }

    public function dashboard(): array
    {
        $groups = $this->base()->selectRaw('notify_no, contractor_code, contractor_name')
            ->selectRaw('SUM(COALESCE(amount, quantity * winning_price, 0)) AS winning_amount')
            ->selectRaw('MIN(is_active) AS all_active')
            ->groupBy('notify_no', 'contractor_code', 'contractor_name')
            ->get();

        return [
            'total' => $groups->count(),
            'active' => $groups->where('all_active', 1)->count(),
            'amount' => $groups->sum('winning_amount'),
            'active_amount' => $groups->where('all_active', 1)->sum('winning_amount'),
        ];
    }

    public function find(int $id): KqlcntAwardItem
    {
        return $this->base()->findOrFail($id);
    }

    public function medicines(int $id): Collection
    {
        $item = $this->find($id);

        return $this->base()->where('notify_no', $item->notify_no)
            ->where('contractor_code', $item->contractor_code)
            ->where('contractor_name', $item->contractor_name)
            ->orderBy('medicine_name')->orderBy('id')->get();
    }
}
