<?php

namespace Modules\Pharma\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Pharma\Models\DrugBidAwardAllocation;
use Modules\Pharma\Models\DrugBidAwardManagementAssignment;

final class UserBidAwardWorkspace
{
    public function browseResults(int $userId, ?string $search = null, int $perPage = 20, int $page = 1): LengthAwarePaginator
    {
        $search = trim((string) $search);
        $perPage = in_array($perPage, [20, 25, 50, 100], true) ? $perPage : 20;

        return $this->globalRowsWithUserContext($userId)
            ->when($search !== '', fn ($query) => $query->where(function ($nested) use ($search): void {
                $nested->where('awards.bidding_notice_code', 'like', "%{$search}%")
                    ->orWhere('awards.decision_number', 'like', "%{$search}%")
                    ->orWhere('awards.investor_name', 'like', "%{$search}%")
                    ->orWhere('awards.medicine_name', 'like', "%{$search}%");
            }))
            ->selectRaw($this->resultIdentitySql().' as result_identity')
            ->selectRaw('MAX(awards.id) as id')
            ->selectRaw('MAX(awards.bidding_notice_code) as bidding_notice_code')
            ->selectRaw('MAX(awards.decision_number) as decision_number')
            ->selectRaw('MAX(awards.decision_date) as decision_date')
            ->selectRaw('MAX(awards.published_at) as published_at')
            ->selectRaw('MAX(awards.investor_name) as investor_name')
            ->selectRaw('MAX(awards.contract_duration_months) as contract_duration_months')
            ->selectRaw('MAX(awards.contract_period) as contract_period')
            ->selectRaw('MAX(awards.contract_period_unit) as contract_period_unit')
            ->selectRaw('MAX(awards.contract_period_text) as contract_period_text')
            ->selectRaw('COUNT(DISTINCT awards.id) as products_count')
            ->selectRaw('SUM(COALESCE(awards.amount, COALESCE(awards.winning_price, awards.unit_price, 0) * COALESCE(awards.quantity, 0))) as total_value')
            ->selectRaw('SUM(CASE WHEN awards.my_hospitals_count > 0 THEN 1 ELSE 0 END) as my_products_count')
            ->selectRaw('SUM(awards.my_hospitals_count) as my_hospitals_count')
            ->selectRaw('SUM(awards.my_allocated_quantity) as my_allocated_quantity')
            ->selectRaw('SUM(awards.my_allocated_quantity * COALESCE(awards.winning_price, awards.unit_price, 0)) as my_allocated_value')
            ->groupBy('result_identity')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', max(1, $page))
            ->through(function ($row) {
                $row->scope_key = sha1($this->identityForRow($row));
                return $row;
            });
    }

    public function findResult(int $userId, string $scopeKey): ?object
    {
        return $this->resultScopes($userId)->firstWhere('scope_key', $scopeKey);
    }

    public function products(int $userId, object $scope, ?string $search = null, int $perPage = 20, int $page = 1): LengthAwarePaginator
    {
        $search = trim((string) $search);
        $perPage = in_array($perPage, [20, 25, 50, 100], true) ? $perPage : 20;

        return $this->globalRowsWithUserContext($userId)
            ->tap(fn ($query) => $this->applyScope($query, $scope))
            ->when($search !== '', fn ($query) => $query->where(function ($nested) use ($search): void {
                $nested->where('awards.medicine_name', 'like', "%{$search}%")
                    ->orWhere('awards.active_ingredient', 'like', "%{$search}%")
                    ->orWhere('awards.registration_or_import_license', 'like', "%{$search}%");
            }))
            ->select([
                'awards.id', 'awards.medicine_name', 'awards.active_ingredient', 'awards.concentration',
                'awards.packaging_specification', 'awards.registration_or_import_license',
                'awards.winning_price', 'awards.unit_price', 'awards.quantity as winning_quantity',
                'awards.bidding_notice_code', 'awards.decision_number', 'awards.decision_date',
            ])
            ->selectRaw('MAX(awards.my_allocated_quantity) as my_allocated_quantity')
            ->selectRaw('MAX(awards.my_hospitals_count) as my_hospitals_count')
            ->groupBy([
                'awards.id', 'awards.medicine_name', 'awards.active_ingredient', 'awards.concentration',
                'awards.packaging_specification', 'awards.registration_or_import_license',
                'awards.winning_price', 'awards.unit_price', 'awards.quantity',
                'awards.bidding_notice_code', 'awards.decision_number', 'awards.decision_date',
            ])
            ->orderBy('awards.medicine_name')
            ->paginate($perPage, ['*'], 'page', max(1, $page));
    }

    private function resultScopes(int $userId)
    {
        return $this->globalRowsWithUserContext($userId)
            ->select('awards.id', 'awards.bidding_notice_code', 'awards.decision_number', 'awards.investor_name', 'awards.decision_date')
            ->distinct()
            ->get()
            ->groupBy(fn ($award) => $this->identityForRow($award))
            ->map(function ($awards, string $identity) {
                $award = $awards->first();
                [$type, $value] = explode(':', $identity, 2);

                return (object) [
                    'scope_key' => sha1($identity),
                    'type' => $type,
                    'value' => $value,
                    'investor_name' => $award->investor_name,
                    'bidding_notice_code' => $award->bidding_notice_code,
                    'decision_number' => $award->decision_number,
                    'decision_date' => $award->decision_date,
                    'products_count' => $awards->count(),
                ];
            })->values();
    }

    private function globalRowsWithUserContext(int $userId)
    {
        $context = DB::table('pharma_drug_bid_awards as source_awards')
            ->select('source_awards.*')
            ->selectSub(function ($query) use ($userId): void {
                $query->from('pharma_drug_bid_award_management_assignments as scoped_assignments')
                    ->join('pharma_drug_bid_award_allocations as scoped_allocations', function ($join): void {
                        $join->on('scoped_allocations.drug_bid_award_id', '=', 'scoped_assignments.drug_bid_award_id')
                            ->on('scoped_allocations.partner_id', '=', 'scoped_assignments.partner_id')
                            ->where('scoped_allocations.status', DrugBidAwardAllocation::STATUS_ACTIVE);
                    })
                    ->whereColumn('scoped_assignments.drug_bid_award_id', 'source_awards.id')
                    ->where('scoped_assignments.user_id', $userId)
                    ->where('scoped_assignments.status', DrugBidAwardManagementAssignment::STATUS_ACTIVE)
                    ->selectRaw('COALESCE(SUM(scoped_allocations.allocated_quantity), 0)');
            }, 'my_allocated_quantity')
            ->selectSub(function ($query) use ($userId): void {
                $query->from('pharma_drug_bid_award_management_assignments as scoped_assignments')
                    ->join('pharma_drug_bid_award_allocations as scoped_allocations', function ($join): void {
                        $join->on('scoped_allocations.drug_bid_award_id', '=', 'scoped_assignments.drug_bid_award_id')
                            ->on('scoped_allocations.partner_id', '=', 'scoped_assignments.partner_id')
                            ->where('scoped_allocations.status', DrugBidAwardAllocation::STATUS_ACTIVE);
                    })
                    ->whereColumn('scoped_assignments.drug_bid_award_id', 'source_awards.id')
                    ->where('scoped_assignments.user_id', $userId)
                    ->where('scoped_assignments.status', DrugBidAwardManagementAssignment::STATUS_ACTIVE)
                    ->selectRaw('COUNT(DISTINCT scoped_assignments.partner_id)');
            }, 'my_hospitals_count');

        return DB::query()->fromSub($context, 'awards');
    }

    private function resultIdentitySql(): string
    {
        return "CASE WHEN awards.bidding_notice_code IS NOT NULL AND awards.bidding_notice_code <> '' THEN CONCAT('tbmt:', awards.bidding_notice_code) ELSE CONCAT('award:', awards.id) END";
    }

    private function identityForRow(object $award): string
    {
        return $award->bidding_notice_code ? 'tbmt:'.$award->bidding_notice_code : 'award:'.$award->id;
    }

    private function applyScope($query, object $scope): void
    {
        match ($scope->type) {
            'tbmt' => $query->where('awards.bidding_notice_code', $scope->value),
            default => $query->where('awards.id', (int) $scope->value),
        };
    }
}
