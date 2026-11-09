<?php

namespace Modules\ClientPortal\Applications\Pharma\Services;

use Illuminate\Validation\ValidationException;
use Modules\Muasamcong\Services\KqlcntAwardReadService;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Services\DrugBidAwardResultGroupService;

/**
 * Redirect to the tested legacy workflow only if the entire legacy TBMT group
 * is provably identical to one canonical TBMT/contractor group.
 *
 * Read-only: never changes source provenance, allocations or product records.
 */
final class KqlcntLegacyWorkflowBridge
{
    public function __construct(
        private readonly KqlcntWorkflowReadiness $readiness,
        private readonly DrugBidAwardResultGroupService $groups,
        private readonly KqlcntAwardReadService $canonical,
    ) {}

    public function scopeKey(int $awardItemId): ?string
    {
        try {
            $ids = $this->readiness->scopedProductIds($awardItemId);
        } catch (ValidationException $exception) {
            // Historical Pharma rows can predate direct KQLCNT source links.
            // In that case attempt a strictly unique, read-only reconciliation.
            $ids = $this->verifiedExistingProductIds($awardItemId);
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

    /**
     * Strict fallback for existing manual/historical Pharma records.
     * A medicine name alone, or a single apparent candidate, is insufficient.
     *
     * @return list<int>
     */
    /**
     * Returns an exact, unique legacy product set suitable for an explicit
     * human-reviewed mapping action. Never returns candidates by name alone.
     *
     * @return list<int>
     */
    public function verifiedMappingCandidateIds(int $awardItemId): array
    {
        $ids = $this->verifiedExistingProductIds($awardItemId);
        if ($ids === []) {
            return [];
        }

        $award = DrugBidAward::query()->find($ids[0]);
        if ($award === null) {
            return [];
        }

        $legacyIds = $this->groups->awardsQuery($award)
            ->pluck('id')->map(fn ($id) => (int) $id)->all();

        sort($ids);
        sort($legacyIds);

        return $ids === $legacyIds ? $ids : [];
    }

    private function verifiedExistingProductIds(int $awardItemId): array
    {
        $lines = $this->canonical->medicines($awardItemId);
        if ($lines->isEmpty()) {
            return [];
        }

        $resolved = [];

        foreach ($lines as $line) {
            $notice = trim((string) $line->notify_no);
            $contractorName = trim((string) $line->contractor_name);
            $medicineName = trim((string) $line->medicine_name);

            if ($notice === '' || $contractorName === '' || $medicineName === ''
                || $line->quantity === null || $line->winning_price === null) {
                return [];
            }

            $candidates = DrugBidAward::query()
                ->where('bidding_notice_code', $notice)
                ->where('contractor_code', $line->contractor_code)
                ->where('winning_company_name', $contractorName)
                ->where('medicine_name', $medicineName)
                ->whereNull('source_id')
                ->whereDoesntHave('sources')
                ->where(function ($query): void {
                    $query->whereNull('source_type')
                        ->orWhere('source_type', DrugBidAward::SOURCE_MANUAL)
                        ->orWhere('source_type', DrugBidAward::SOURCE_MUASAMCONG);
                })
                ->get();

            // Enforce exact numeric values in PHP to avoid driver-specific
            // decimal formatting differences, and reject any ambiguity.
            $matches = $candidates->filter(fn (DrugBidAward $candidate): bool =>
                $this->sameDecimal($candidate->quantity, $line->quantity)
                && $this->sameDecimal($candidate->winning_price, $line->winning_price)
            );

            if ($matches->count() !== 1) {
                return [];
            }

            $id = (int) $matches->first()->id;
            if (in_array($id, $resolved, true)) {
                return [];
            }
            $resolved[] = $id;
        }

        return $resolved;
    }

    private function sameDecimal(mixed $a, mixed $b): bool
    {
        return $a !== null && $b !== null
            && number_format((float) $a, 4, '.', '') === number_format((float) $b, 4, '.', '');
    }
}
