<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Models\DrugBidAwardAllocation;
use Modules\Pharma\Models\DrugBidAwardProductPolicy;

final class ClientBidAwardWorkflow
{
    public function __construct(
        private readonly DrugBidAwardResultGroupService $groups,
        private readonly DrugBidAwardDistributionScopeService $distributionScopes,
        private readonly DrugBidAwardAllocationService $allocations,
        private readonly DrugBidAwardCommercialPolicyService $commercialPolicies,
    ) {}

    public function contextAward(string $scopeKey): ?DrugBidAward
    {
        return DrugBidAward::query()->get()->first(
            fn (DrugBidAward $award): bool => sha1($this->groups->resultKey($award)) === $scopeKey
        );
    }

    public function hospitals(DrugBidAward $award): Collection
    {
        return $this->distributionScopes->findForAward($award)?->partners
            ?->where('status', 'active')->sortBy('name')->values() ?? collect();
    }

    public function products(DrugBidAward $award): Collection
    {
        return $this->groups->awardsQuery($award)
            ->orderBy('medicine_name')
            ->get(['id', 'medicine_name', 'active_ingredient', 'concentration', 'quantity', 'winning_price', 'unit_price']);
    }

    public function existingAllocations(DrugBidAward $award): Collection
    {
        return DrugBidAwardAllocation::query()
            ->whereIn('drug_bid_award_id', $this->groups->awardsQuery($award)->pluck('id'))
            ->where('status', DrugBidAwardAllocation::STATUS_ACTIVE)
            ->get()
            ->keyBy(fn (DrugBidAwardAllocation $allocation): string => $allocation->drug_bid_award_id.':'.$allocation->partner_id);
    }

    public function saveAllocations(DrugBidAward $award, array $selectedHospitalIds, array $quantities, ?int $actorId): int
    {
        $allowedHospitalIds = $this->hospitals($award)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $hospitalIds = array_values(array_intersect($allowedHospitalIds, array_unique(array_map('intval', $selectedHospitalIds))));
        if ($hospitalIds === []) {
            throw ValidationException::withMessages(['hospitals' => 'Hãy hoàn tất Bước 1 và chọn ít nhất một bệnh viện.']);
        }

        $productIds = $this->products($award)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $saved = 0;
        foreach ($quantities as $awardId => $hospitalRows) {
            $awardId = (int) $awardId;
            if (! in_array($awardId, $productIds, true) || ! is_array($hospitalRows)) continue;
            foreach ($hospitalRows as $partnerId => $quantity) {
                $partnerId = (int) $partnerId;
                if (! in_array($partnerId, $hospitalIds, true) || $quantity === '' || $quantity === null) continue;
                $this->allocations->save($awardId, null, [
                    'partner_id' => $partnerId,
                    'allocated_quantity' => $quantity,
                    'notes' => 'Phân bổ từ Pharma PWA',
                ], $actorId);
                $saved++;
            }
        }

        if ($saved === 0) {
            throw ValidationException::withMessages(['allocations' => 'Nhập số lượng cho ít nhất một sản phẩm/bệnh viện.']);
        }

        return $saved;
    }

    public function hasActiveAllocation(DrugBidAward $award): bool
    {
        return DrugBidAwardAllocation::query()
            ->whereIn('drug_bid_award_id', $this->groups->awardsQuery($award)->pluck('id'))
            ->where('status', DrugBidAwardAllocation::STATUS_ACTIVE)
            ->exists();
    }

    public function productPolicies(DrugBidAward $award): Collection
    {
        return DrugBidAwardProductPolicy::query()
            ->whereIn('drug_bid_award_id', $this->groups->awardsQuery($award)->pluck('id'))
            ->get()->keyBy('drug_bid_award_id');
    }

    public function saveProductPolicies(DrugBidAward $award, array $percentages, ?int $actorId): void
    {
        if (! $this->hasActiveAllocation($award)) {
            throw ValidationException::withMessages(['commercial_policy' => 'Cần hoàn tất phân bổ số lượng trước khi thiết lập chính sách kinh doanh.']);
        }
        $this->commercialPolicies->saveProductPolicies($award, $percentages, $actorId);
    }
}
