<?php

namespace Modules\ClientPortal\Applications\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Modules\Muasamcong\Services\KqlcntAwardReadService;

/**
 * Read-only reconciliation evidence. Never links records based on matching
 * medicine text or TBMT alone; a candidate is not an authorized mapping.
 */
final class KqlcntMappingDiagnostics
{
    public function __construct(private readonly KqlcntAwardReadService $awards) {}

    /**
     * @return array{canonical_count:int,linked_count:int,missing_count:int,ambiguous_count:int,candidates_count:int,lines:list<array<string,mixed>>}
     */
    public function inspect(int $awardItemId): array
    {
        $canonical = $this->awards->medicines($awardItemId);
        $ids = $canonical->pluck('id')->map(fn ($id) => (int) $id)->all();
        $direct = $ids === [] ? collect() : DB::table('pharma_drug_bid_awards')
            ->where('source_type', 'muasamcong')
            ->whereIn('source_id', $ids)
            ->get(['id', 'source_id', 'bidding_notice_code', 'contractor_code', 'winning_company_name']);

        $bySource = $direct->groupBy(fn ($row) => (int) $row->source_id);
        $lines = [];
        $linked = 0;
        $ambiguous = 0;
        $candidateCount = 0;

        foreach ($canonical as $line) {
            $matches = $bySource->get((int) $line->id, collect());
            $valid = $matches->filter(fn ($row) => $this->sameIdentity($line, $row));
            $status = 'missing';

            if ($matches->count() > 1 || ($matches->count() === 1 && $valid->count() === 0)) {
                $status = 'conflict';
                $ambiguous++;
            } elseif ($valid->count() === 1) {
                $status = 'linked';
                $linked++;
            }

            // Diagnostic only: same TBMT + contractor, never a write target.
            // Exclude already linked source records; names are not proof of identity.
            if ($status === 'missing') {
                $candidates = DB::table('pharma_drug_bid_awards')
                    ->where('bidding_notice_code', $line->notify_no)
                    ->where('contractor_code', $line->contractor_code)
                    ->where('medicine_name', $line->medicine_name)
                    ->where(function ($query) {
                        $query->whereNull('source_id')->orWhere('source_type', 'manual');
                    })
                    ->count();
                $candidateCount += $candidates;
            } else {
                $candidates = 0;
            }

            $lines[] = [
                'canonical_id' => (int) $line->id,
                'medicine_name' => (string) $line->medicine_name,
                'status' => $status,
                'linked_pharma_id' => $status === 'linked' ? (int) $valid->first()->id : null,
                'candidate_count' => $candidates,
            ];
        }

        return [
            'canonical_count' => $canonical->count(),
            'linked_count' => $linked,
            'missing_count' => $canonical->count() - $linked - $ambiguous,
            'ambiguous_count' => $ambiguous,
            'candidates_count' => $candidateCount,
            'lines' => $lines,
        ];
    }

    private function sameIdentity(object $canonical, object $pharma): bool
    {
        if (trim((string) $canonical->notify_no) !== trim((string) $pharma->bidding_notice_code)
            || trim((string) $canonical->contractor_code) !== trim((string) $pharma->contractor_code)) {
            return false;
        }

        // Contractor name disambiguates groups without a contractor code.
        return trim((string) $canonical->contractor_code) !== ''
            || trim((string) $canonical->contractor_name) === trim((string) $pharma->winning_company_name);
    }
}
