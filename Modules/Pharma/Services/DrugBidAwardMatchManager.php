<?php

namespace Modules\Pharma\Services;

use Modules\Pharma\Data\DrugBidMatchResult;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Models\DrugBidAwardMatch;

class DrugBidAwardMatchManager
{
    public function __construct(private readonly DrugBidAwardMatcher $matcher) {}

    public function refresh(DrugBidAward $award): DrugBidAwardMatch
    {
        $existing = $award->canonicalMatch()->first();
        $sourceHash = $this->matcher->sourceIdentityHash($award);

        if ($existing?->isManualConfirmed()) {
            if ($existing->source_identity_hash !== null && $existing->source_identity_hash !== $sourceHash) {
                $existing->update([
                    'review_status' => DrugBidAwardMatch::REVIEW_STALE,
                    'review_reason' => 'source_identity_changed_after_manual_confirmation',
                    'source_identity_hash' => $sourceHash,
                ]);
            }

            return $existing->refresh();
        }

        return $this->persist($award, $this->matcher->match($award), $sourceHash);
    }

    public function confirm(DrugBidAward $award, DrugBidMatchResult $result, ?int $userId = null): DrugBidAwardMatch
    {
        $match = $this->persist($award, $result, $this->matcher->sourceIdentityHash($award));
        $match->update([
            'is_manual' => true,
            'review_status' => DrugBidAwardMatch::REVIEW_CONFIRMED,
            'matched_by' => $userId,
            'matched_at' => now(),
            'review_reason' => null,
        ]);

        return $match->refresh();
    }

    private function persist(DrugBidAward $award, DrugBidMatchResult $result, string $sourceHash): DrugBidAwardMatch
    {
        $matchedHash = hash('sha256', implode('|', [
            $result->medicine?->id ?? '-',
            $result->variant?->id ?? '-',
            $result->package?->id ?? '-',
        ]));

        $reviewStatus = in_array($result->status, [DrugBidAwardMatch::STATUS_REVIEW_REQUIRED, DrugBidAwardMatch::STATUS_UNMATCHED], true)
            ? DrugBidAwardMatch::REVIEW_PENDING
            : DrugBidAwardMatch::REVIEW_AUTO;

        return DrugBidAwardMatch::query()->updateOrCreate(
            ['drug_bid_award_id' => $award->id],
            [
                'medicine_id' => $result->medicine?->id,
                'medicine_variant_id' => $result->variant?->id,
                'medicine_package_id' => $result->package?->id,
                'match_status' => $result->status,
                'match_method' => $result->method,
                'confidence' => $result->confidence,
                'resolution_level' => $result->resolutionLevel,
                'review_status' => $reviewStatus,
                'is_manual' => false,
                'matched_by' => null,
                'matched_at' => null,
                'source_identity_hash' => $sourceHash,
                'matched_identity_hash' => $matchedHash,
                'review_reason' => $result->reviewReason,
            ],
        );
    }
}
