<?php

namespace Modules\ClientPortal\Applications\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Pharma\Models\DrugBidAwardAllocation;
use Modules\Pharma\Services\DrugBidAwardAllocationService;

/**
 * Canonical KQLCNT allocation adapter. Callers must authorize the actor before
 * invoking mutations. All product IDs are resolved from canonical source lines.
 */
final class KqlcntAllocationWorkflow
{
    public function __construct(
        private readonly KqlcntWorkflowReadiness $readiness,
        private readonly DrugBidAwardAllocationService $allocations,
    ) {}

    public function products(int $awardItemId): array
    {
        $ids = $this->readiness->scopedProductIds($awardItemId);

        return DB::table('pharma_drug_bid_awards')
            ->whereIn('id', $ids)
            ->orderBy('medicine_name')
            ->get(['id', 'medicine_name', 'quantity', 'winning_price'])
            ->all();
    }

    public function hasActiveAllocation(int $awardItemId): bool
    {
        return DrugBidAwardAllocation::query()
            ->whereIn('drug_bid_award_id', $this->readiness->scopedProductIds($awardItemId))
            ->where('status', DrugBidAwardAllocation::STATUS_ACTIVE)
            ->exists();
    }

    /**
     * Save a single hospital/product allocation using Pharma's existing
     * transaction, hospital/scope, quantity, and committed-contract guards.
     */
    public function save(int $awardItemId, int $productId, int $partnerId, mixed $quantity, ?int $actorId): void
    {
        $allowed = $this->readiness->scopedProductIds($awardItemId);
        if (! in_array($productId, $allowed, true)) {
            throw ValidationException::withMessages(['product' => 'Sản phẩm không thuộc hồ sơ KQLCNT này.']);
        }

        $this->allocations->save($productId, null, [
            'partner_id' => $partnerId,
            'allocated_quantity' => $quantity,
            'notes' => 'Phân bổ từ KQLCNT canonical',
        ], $actorId);
    }
}
