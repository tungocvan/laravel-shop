<?php

namespace Modules\Pharma\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Pharma\Models\DrugBidAwardAllocation;
use Modules\Pharma\Models\DrugBidAwardManagementAssignment;

final class UserBidAwardWorkspace
{
    public function browseAssignedResults(int $userId, ?string $search = null, int $perPage = 20, int $page = 1): LengthAwarePaginator
    {
        $search = trim((string) $search);
        $perPage = in_array($perPage, [20, 25, 50, 100], true) ? $perPage : 20;

        $query = $this->assignedRows($userId)
            ->when($search !== '', fn ($query) => $query->where(function ($nested) use ($search): void {
                $nested->where('awards.bidding_notice_code', 'like', "%{$search}%")
                    ->orWhere('awards.decision_number', 'like', "%{$search}%")
                    ->orWhere('awards.investor_name', 'like', "%{$search}%")
                    ->orWhere('awards.medicine_name', 'like', "%{$search}%");
            }))
            ->selectRaw("CASE WHEN awards.bidding_notice_code IS NOT NULL AND awards.bidding_notice_code <> '' THEN CONCAT('tbmt:', awards.bidding_notice_code) WHEN awards.decision_number IS NOT NULL AND awards.decision_number <> '' THEN CONCAT('decision:', awards.decision_number) ELSE CONCAT('award:', awards.id) END as result_identity")
            ->selectRaw('MAX(awards.id) as id')
            ->selectRaw('MAX(awards.bidding_notice_code) as bidding_notice_code')
            ->selectRaw('MAX(awards.decision_number) as decision_number')
            ->selectRaw('MAX(awards.decision_date) as decision_date')
            ->selectRaw('MAX(awards.investor_name) as investor_name')
            ->selectRaw('MAX(awards.contract_duration_months) as contract_duration_months')
            ->selectRaw('MAX(awards.contract_period) as contract_period')
            ->selectRaw('MAX(awards.contract_period_unit) as contract_period_unit')
            ->selectRaw('MAX(awards.contract_period_text) as contract_period_text')
            ->selectRaw('COUNT(DISTINCT awards.id) as products_count')
            ->selectRaw('COUNT(DISTINCT workspace_assignments.partner_id) as hospitals_count')
            ->selectRaw('SUM(workspace_allocations.allocated_quantity) as allocated_quantity')
            ->selectRaw('SUM(workspace_allocations.allocated_quantity * COALESCE(awards.winning_price, awards.unit_price, 0)) as allocated_value')
            ->groupBy('result_identity')
            ->orderByDesc('decision_date')
            ->orderBy('result_identity');

        return $query->paginate($perPage, ['*'], 'page', max(1, $page))
            ->through(function ($row) {
                $row->scope_key = sha1($this->identityForRow($row));
                return $row;
            });
    }

    public function findAssignedResult(int $userId, string $scopeKey): ?object
    {
        return $this->resultScopes($userId)->firstWhere('scope_key', $scopeKey);
    }

    public function assignedProducts(int $userId, object $scope, ?string $search = null, int $perPage = 20, int $page = 1): LengthAwarePaginator
    {
        $search = trim((string) $search);
        $perPage = in_array($perPage, [20, 25, 50, 100], true) ? $perPage : 20;

        return $this->assignedRows($userId)
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
            ->selectRaw('SUM(workspace_allocations.allocated_quantity) as allocated_quantity')
            ->selectRaw('COUNT(DISTINCT workspace_assignments.partner_id) as hospitals_count')
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
        return $this->assignedRows($userId)
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

    private function assignedRows(int $userId)
    {
        return DB::table('pharma_drug_bid_award_management_assignments as workspace_assignments')
            ->join('pharma_drug_bid_award_allocations as workspace_allocations', function ($join): void {
                $join->on('workspace_allocations.drug_bid_award_id', '=', 'workspace_assignments.drug_bid_award_id')
                    ->on('workspace_allocations.partner_id', '=', 'workspace_assignments.partner_id');
            })
            ->join('pharma_drug_bid_awards as awards', 'awards.id', '=', 'workspace_assignments.drug_bid_award_id')
            ->where('workspace_assignments.user_id', $userId)
            ->where('workspace_assignments.status', DrugBidAwardManagementAssignment::STATUS_ACTIVE)
            ->where('workspace_allocations.status', DrugBidAwardAllocation::STATUS_ACTIVE);
    }

    private function identityForRow(object $award): string
    {
        return $award->bidding_notice_code
            ? 'tbmt:'.$award->bidding_notice_code
            : ($award->decision_number ? 'decision:'.$award->decision_number : 'award:'.$award->id);
    }

    private function applyScope($query, object $scope): void
    {
        match ($scope->type) {
            'tbmt' => $query->where('awards.bidding_notice_code', $scope->value),
            'decision' => $query->where('awards.decision_number', $scope->value),
            default => $query->where('awards.id', (int) $scope->value),
        };
    }
}
