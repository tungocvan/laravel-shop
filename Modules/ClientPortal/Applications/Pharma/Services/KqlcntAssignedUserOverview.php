<?php

namespace Modules\ClientPortal\Applications\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Services\ClientBidAwardWorkflow;

/**
 * Read-only adapter to the existing Pharma manager-assignment workflow.
 * Never infer assignments from a shared TBMT alone.
 */
final class KqlcntAssignedUserOverview
{
    public function __construct(
        private readonly KqlcntLegacyWorkflowBridge $bridge,
        private readonly ClientBidAwardWorkflow $workflow,
    ) {}

    /** @return array<int, list<string>> Keyed by canonical group's MIN(id). */
    public function assignedGroups(): array
    {
        $groups = DB::table('muasamcong_kqlcnt_award_items')
            ->whereNotNull('synced_from_catalog_at')
            ->selectRaw('MIN(id) AS id')
            ->groupBy('notify_no', 'contractor_code', 'contractor_name')
            ->pluck('id');

        $result = [];
        foreach ($groups as $groupId) {
            $id = (int) $groupId;
            $scope = $this->bridge->scopeKey($id);

            // Historical, unlinked imports may have a unique, exact candidate
            // without a confirmed provenance link. Resolve read-only and do
            // not grant write access or mutate the canonical mapping.
            $award = $scope !== null ? $this->workflow->contextAward($scope) : null;
            if ($award === null) {
                $candidateIds = $this->bridge->verifiedMappingCandidateIds($id);
                if ($candidateIds === []) {
                    continue;
                }
                $award = DrugBidAward::query()->find($candidateIds[0]);
            }

            if ($award === null) {
                continue;
            }

            $names = $this->workflow->managementAssignmentSummary($award)
                ->pluck('user.name')->filter(fn ($name) => filled($name))
                ->unique()->sort()->values()->all();

            if ($names !== []) {
                $result[$id] = $names;
            }
        }

        return $result;
    }
}
