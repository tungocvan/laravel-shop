<?php

namespace Modules\Pharma\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Partner\Models\Partner;
use Modules\Pharma\Models\DrugBidAwardAllocation;
use Modules\Pharma\Models\DrugBidAwardManagementAssignment;

final class UserCommercialHospitalWorkspace
{
    public function browseHospitals(
        int $userId,
        ?string $search = null,
        int $perPage = 25,
        int $page = 1,
    ): LengthAwarePaginator {
        $search = trim((string) $search);
        $perPage = in_array($perPage, [25, 50, 100], true) ? $perPage : 25;

        return $this->hospitalQuery($userId)
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $nested) use ($search): void {
                $nested->where('partners.name', 'like', "%{$search}%")
                    ->orWhere('partners.address', 'like', "%{$search}%")
                    ->orWhere('partners.province_code', 'like', "%{$search}%");
            }))
            ->select('partners.*')
            ->selectSub($this->assignedProductCountQuery($userId), 'assigned_products_count')
            ->orderBy('partners.name')
            ->paginate($perPage, ['*'], 'page', max(1, $page));
    }

    /** @return array{hospitals:int,products:int} */
    public function summary(int $userId): array
    {
        return [
            'hospitals' => $this->hospitalQuery($userId)->count(),
            'products' => DrugBidAwardManagementAssignment::query()
                ->where('user_id', $userId)
                ->where('status', DrugBidAwardManagementAssignment::STATUS_ACTIVE)
                ->whereExists(fn ($query) => $query
                    ->selectRaw('1')
                    ->from('pharma_drug_bid_award_allocations as workspace_allocations')
                    ->whereColumn('workspace_allocations.drug_bid_award_id', 'pharma_drug_bid_award_management_assignments.drug_bid_award_id')
                    ->whereColumn('workspace_allocations.partner_id', 'pharma_drug_bid_award_management_assignments.partner_id')
                    ->where('workspace_allocations.status', DrugBidAwardAllocation::STATUS_ACTIVE))
                ->distinct()
                ->count('drug_bid_award_id'),
        ];
    }

    public function findHospital(int $userId, int $partnerId): ?Partner
    {
        return $this->hospitalQuery($userId)
            ->select('partners.*')
            ->selectSub($this->assignedProductCountQuery($userId), 'assigned_products_count')
            ->find($partnerId);
    }

    private function hospitalQuery(int $userId): Builder
    {
        return Partner::query()
            ->whereExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('pharma_drug_bid_award_management_assignments as workspace_assignments')
                ->join('pharma_drug_bid_award_allocations as workspace_allocations', function ($join): void {
                    $join->on('workspace_allocations.drug_bid_award_id', '=', 'workspace_assignments.drug_bid_award_id')
                        ->on('workspace_allocations.partner_id', '=', 'workspace_assignments.partner_id');
                })
                ->whereColumn('workspace_assignments.partner_id', 'partners.id')
                ->where('workspace_assignments.user_id', $userId)
                ->where('workspace_assignments.status', DrugBidAwardManagementAssignment::STATUS_ACTIVE)
                ->where('workspace_allocations.status', DrugBidAwardAllocation::STATUS_ACTIVE));
    }

    private function assignedProductCountQuery(int $userId)
    {
        return DB::table('pharma_drug_bid_award_management_assignments as product_assignments')
            ->join('pharma_drug_bid_award_allocations as product_allocations', function ($join): void {
                $join->on('product_allocations.drug_bid_award_id', '=', 'product_assignments.drug_bid_award_id')
                    ->on('product_allocations.partner_id', '=', 'product_assignments.partner_id');
            })
            ->whereColumn('product_assignments.partner_id', 'partners.id')
            ->where('product_assignments.user_id', $userId)
            ->where('product_assignments.status', DrugBidAwardManagementAssignment::STATUS_ACTIVE)
            ->where('product_allocations.status', DrugBidAwardAllocation::STATUS_ACTIVE)
            ->selectRaw('COUNT(DISTINCT product_assignments.drug_bid_award_id)');
    }
}
