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
            $ids = $this->verifiedProvenanceProductIds($awardItemId);
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

        // Confirmation must never rewrite an existing provenance link.
        // Already-linked rows are routed read-only via scopeKey().
        foreach ($ids as $id) {
            $candidate = DrugBidAward::query()->find($id);
            if ($candidate === null || $candidate->source_id !== null || $candidate->sources()->exists()) {
                return [];
            }
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

    /**
     * Automatic legacy routing requires existing verified provenance on every
     * line. Unlinked imports require the explicit confirmation workflow.
     *
     * @return list<int>
     */
    private function verifiedProvenanceProductIds(int $awardItemId): array
    {
        $ids = $this->verifiedExistingProductIds($awardItemId);
        if ($ids === []) {
            return [];
        }

        foreach ($ids as $id) {
            $award = DrugBidAward::query()->find($id);
            if ($award === null || ($award->source_id === null && ! $award->sources()->exists())) {
                return [];
            }
        }

        return $ids;
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
                ->where(function ($query) use ($line): void {
                    // A direct canonical link or an exact active source-record
                    // reference is authoritative. Manual imports without a
                    // source link remain confirmation candidates only.
                    $query->where(function ($direct) use ($line): void {
                        $direct->where('source_type', DrugBidAward::SOURCE_MUASAMCONG)
                            ->where('source_id', $line->id);
                    })->orWhere(function ($provenance) use ($line): void {
                        $provenance->whereNull('source_id')
                            ->whereHas('sources', function ($source) use ($line): void {
                                $source->where('source_system', 'muasamcong')
                                    ->where('source_record_type', 'kqlcnt_award_item')
                                    ->where('source_reference', 'muasamcong_kqlcnt_award_items:'.$line->id)
                                    ->where('is_active', true);
                            });
                    })->orWhere(function ($unlinked): void {
                        $unlinked->whereNull('source_id')
                            ->whereDoesntHave('sources')
                            ->where(function ($types): void {
                                $types->whereNull('source_type')
                                    ->orWhere('source_type', DrugBidAward::SOURCE_MANUAL)
                                    ->orWhere('source_type', DrugBidAward::SOURCE_MUASAMCONG);
                            });
                    });
                })
                ->get();

            // Enforce exact numeric values in PHP to avoid driver-specific
            // decimal formatting differences, and reject any ambiguity.
            $matches = $candidates->filter(function (DrugBidAward $candidate) use ($line): bool {
                if (! $this->sameDecimal($candidate->quantity, $line->quantity)
                    || ! $this->sameDecimal($candidate->winning_price, $line->winning_price)) {
                    return false;
                }

                // Never accept a row whose existing provenance points at a
                // different canonical medicine, even if text/numbers match.
                if ($candidate->source_id !== null) {
                    return $candidate->source_type === DrugBidAward::SOURCE_MUASAMCONG
                        && (int) $candidate->source_id === (int) $line->id
                        && ! $candidate->sources()->where(function ($source) use ($line): void {
                            $source->where('source_system', 'muasamcong')
                                ->where('source_record_type', 'kqlcnt_award_item')
                                ->where('source_reference', '!=', 'muasamcong_kqlcnt_award_items:'.$line->id);
                        })->exists();
                }

                if ($candidate->sources()->exists()) {
                    return $candidate->sources()->count() === 1
                        && $candidate->sources()->where('source_system', 'muasamcong')
                            ->where('source_record_type', 'kqlcnt_award_item')
                            ->where('source_reference', 'muasamcong_kqlcnt_award_items:'.$line->id)
                            ->where('is_active', true)->exists();
                }

                return true;
            });

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
