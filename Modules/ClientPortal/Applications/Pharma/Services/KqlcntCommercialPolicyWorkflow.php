<?php

namespace Modules\ClientPortal\Applications\Pharma\Services;

use Illuminate\Validation\ValidationException;
use Modules\Pharma\Models\DrugBidAwardAllocation;
use Modules\Pharma\Services\DrugBidAwardCommercialPolicyService;

/**
 * Canonical KQLCNT commercial policy adapter. Route/controller must enforce
 * feature and policy permissions before invoking any mutation.
 */
final class KqlcntCommercialPolicyWorkflow
{
    public function __construct(
        private readonly KqlcntWorkflowReadiness $readiness,
        private readonly DrugBidAwardCommercialPolicyService $policies,
    ) {}

    /**
     * @param array<int|string, mixed> $percentages
     */
    public function save(int $awardItemId, array $percentages, ?int $actorId): void
    {
        $ids = $this->readiness->scopedProductIds($awardItemId);

        if (! DrugBidAwardAllocation::query()
            ->whereIn('drug_bid_award_id', $ids)
            ->where('status', DrugBidAwardAllocation::STATUS_ACTIVE)
            ->exists()) {
            throw ValidationException::withMessages(['productPolicies' => 'Cần phân bổ số lượng trước khi thiết lập chính sách kinh doanh.']);
        }

        $this->policies->saveProductPoliciesForIds($ids, $percentages, $actorId);
    }
}
