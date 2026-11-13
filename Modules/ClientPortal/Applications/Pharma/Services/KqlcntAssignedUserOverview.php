<?php

namespace Modules\ClientPortal\Applications\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Modules\Muasamcong\Services\KqlcntAwardReadService;

/**
 * Read-only, conservative assignment lookup. Only fully verified canonical
 * KQLCNT/Pharma groups are considered assigned; never match on TBMT alone.
 */
final class KqlcntAssignedUserOverview
{
    public function __construct(private readonly KqlcntLegacyWorkflowBridge $bridge) {}

    /** @return array<int, list<string>> Keyed by canonical group's MIN(id). */
    public function assignedGroups(): array
    {
        $candidates = DB::table('muasamcong_kqlcnt_award_items as k')
            ->join('pharma_drug_bid_awards as p', function ($join): void {
                $join->on('p.source_id', '=', 'k.id')->where('p.source_type', '=', 'muasamcong');
            })
            ->join('pharma_drug_bid_award_management_assignments as a', function ($join): void {
                $join->on('a.drug_bid_award_id', '=', 'p.id')->where('a.status', '=', 'active');
            })
            ->whereNotNull('k.synced_from_catalog_at')
            ->select('k.notify_no', 'k.contractor_code', 'k.contractor_name')
            ->distinct()->get();

        $result = [];
        foreach ($candidates as $candidate) {
            $group = DB::table('muasamcong_kqlcnt_award_items')
                ->whereNotNull('synced_from_catalog_at')
                ->where('notify_no', $candidate->notify_no)
                ->where('contractor_code', $candidate->contractor_code)
                ->where('contractor_name', $candidate->contractor_name);
            $id = (int) $group->min('id');
            if ($id === 0 || $this->bridge->scopeKey($id) === null) {
                continue;
            }

            $names = DB::table('pharma_drug_bid_award_management_assignments as a')
                ->join('pharma_drug_bid_awards as p', 'p.id', '=', 'a.drug_bid_award_id')
                ->join('users as u', 'u.id', '=', 'a.user_id')
                ->where('a.status', 'active')
                ->where('p.source_type', 'muasamcong')
                ->whereIn('p.source_id', $group->pluck('id'))
                ->distinct()->orderBy('u.name')->pluck('u.name')->filter()->values()->all();

            if ($names !== []) {
                $result[$id] = $names;
            }
        }

        return $result;
    }
}
