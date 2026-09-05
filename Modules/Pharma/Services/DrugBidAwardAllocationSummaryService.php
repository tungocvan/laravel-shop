<?php

namespace Modules\Pharma\Services;

use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Models\DrugBidAwardAllocation;

class DrugBidAwardAllocationSummaryService
{
    public const UNALLOCATED = 'UNALLOCATED';
    public const PARTIALLY_ALLOCATED = 'PARTIALLY_ALLOCATED';
    public const FULLY_ALLOCATED = 'FULLY_ALLOCATED';
    public const OVER_ALLOCATED = 'OVER_ALLOCATED';

    public function forAward(DrugBidAward $award): array
    {
        $allocated = (float) DrugBidAwardAllocation::query()
            ->where('drug_bid_award_id', $award->id)
            ->where('status', DrugBidAwardAllocation::STATUS_ACTIVE)
            ->sum('allocated_quantity');
        $winning = (float) $award->quantity;
        $remaining = round($winning - $allocated, 4);

        return [
            'winning_quantity' => $winning,
            'allocated_quantity' => round($allocated, 4),
            'remaining_quantity' => $remaining,
            'facility_count' => DrugBidAwardAllocation::query()
                ->where('drug_bid_award_id', $award->id)
                ->where('status', DrugBidAwardAllocation::STATUS_ACTIVE)
                ->count(),
            'status' => $this->status($winning, $allocated),
        ];
    }

    private function status(float $winning, float $allocated): string
    {
        if ($allocated <= 0) {
            return self::UNALLOCATED;
        }

        if ($allocated > $winning + 0.00005) {
            return self::OVER_ALLOCATED;
        }

        if (abs($allocated - $winning) <= 0.00005) {
            return self::FULLY_ALLOCATED;
        }

        return self::PARTIALLY_ALLOCATED;
    }
}
