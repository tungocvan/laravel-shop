<?php

namespace Modules\ClientPortal\Applications\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Pharma\Services\DrugBidAwardResultGroupService;
use Modules\Pharma\Models\DrugBidAward;

/**
 * Reuses the legacy TBMT-wide workflow ONLY when its exact product set is
 * identical to the canonical KQLCNT group. Otherwise legacy writes could
 * affect a different contractor and must remain unavailable.
 */
final class KqlcntLegacyWorkflowBridge
{
    public function __construct(
        private readonly KqlcntWorkflowReadiness $readiness,
        private readonly DrugBidAwardResultGroupService $groups,
    ) {}

    public function scopeKey(int $awardItemId): ?string
    {
        try {
            $ids = $this->readiness->scopedProductIds($awardItemId);
        } catch (ValidationException $exception) {
            return null;
        }

        if ($ids === []) {
            return null;
        }

        $award = DrugBidAward::query()->find($ids[0]);
        if ($award === null) {
            return null;
        }

        $legacyIds = $this->groups->awardsQuery($award)
            ->pluck('id')->map(fn ($id) => (int) $id)->all();

        sort($ids);
        sort($legacyIds);

        if ($ids !== $legacyIds) {
            return null;
        }

        return sha1($this->groups->resultKey($award));
    }
}
