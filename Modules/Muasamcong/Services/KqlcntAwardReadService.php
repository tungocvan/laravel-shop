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
            ->when($filters['search'] ?? null, function (Builder $query, string $term): void {
                $query->where(function (Builder $q) use ($term): void {
                    $q->where('notify_no', 'like', '%'.$term.'%')
                        ->orWhere('contractor_name', 'like', '%'.$term.'%')
                        ->orWhere('investor_name', 'like', '%'.$term.'%');
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
                        ->whereRaw('matches.contractor_code <=> muasamcong_kqlcnt_award_items.contractor_code')
                        ->whereRaw('matches.contractor_name <=> muasamcong_kqlcnt_award_items.contractor_name');
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
