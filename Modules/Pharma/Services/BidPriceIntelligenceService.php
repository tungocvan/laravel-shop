<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Collection;
use Modules\Pharma\Data\BidPriceIntelligence;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Models\DrugBidAwardMatch;

class BidPriceIntelligenceService
{
    public function forMedicine(int $medicineId, int $limit = 20): BidPriceIntelligence
    {
        return $this->summarize($this->baseQuery()->where('bm.medicine_id', $medicineId)->limit($limit)->get());
    }

    public function forVariant(int $variantId, int $limit = 20): BidPriceIntelligence
    {
        return $this->summarize($this->baseQuery()->where('bm.medicine_variant_id', $variantId)->limit($limit)->get());
    }

    public function forPackage(int $packageId, int $limit = 20): BidPriceIntelligence
    {
        return $this->summarize($this->baseQuery()->where('bm.medicine_package_id', $packageId)->limit($limit)->get());
    }

    /** @return array<string, BidPriceIntelligence> */
    public function forItems(array $items, int $limitPerIdentity = 20): array
    {
        $variantIds = collect($items)->pluck('variant_id')->filter()->unique()->values();
        $packageIds = collect($items)->pluck('package_id')->filter()->unique()->values();

        $rows = $this->baseQuery()
            ->where(function ($query) use ($variantIds, $packageIds): void {
                if ($variantIds->isNotEmpty()) {
                    $query->whereIn('bm.medicine_variant_id', $variantIds);
                }
                if ($packageIds->isNotEmpty()) {
                    $method = $variantIds->isNotEmpty() ? 'orWhereIn' : 'whereIn';
                    $query->{$method}('bm.medicine_package_id', $packageIds);
                }
            })
            ->get();

        $result = [];
        foreach ($items as $item) {
            $key = $this->identityKey((int) $item['variant_id'], isset($item['package_id']) ? (int) $item['package_id'] : null);
            $matching = $rows->filter(function ($row) use ($item): bool {
                if (! empty($item['package_id'])) {
                    return (int) $row->medicine_package_id === (int) $item['package_id'];
                }

                return (int) $row->medicine_variant_id === (int) $item['variant_id'];
            })->take($limitPerIdentity)->values();
            $result[$key] = $this->summarize($matching);
        }

        return $result;
    }

    public function latestForVariant(int $variantId, ?int $packageId = null): ?DrugBidAward
    {
        $query = $this->baseQuery()->where('bm.medicine_variant_id', $variantId);
        if ($packageId !== null) {
            $query->where('bm.medicine_package_id', $packageId);
        }

        $row = $query->first();

        return $row ? DrugBidAward::query()->find($row->id) : null;
    }

    private function baseQuery()
    {
        return DrugBidAward::query()
            ->from('pharma_drug_bid_awards as a')
            ->join('pharma_drug_bid_award_matches as bm', 'bm.drug_bid_award_id', '=', 'a.id')
            ->whereNotNull('a.winning_price')
            ->where('a.is_active', true)
            ->whereNotIn('bm.review_status', [DrugBidAwardMatch::REVIEW_REJECTED, DrugBidAwardMatch::REVIEW_IGNORED])
            ->select([
                'a.*', 'bm.medicine_id as matched_medicine_id', 'bm.medicine_variant_id', 'bm.medicine_package_id',
                'bm.resolution_level', 'bm.review_status',
            ])
            ->orderByRaw('CASE WHEN a.decision_date IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('a.decision_date')
            ->orderByDesc('a.published_at')
            ->orderByDesc('a.id');
    }

    private function summarize(Collection $rows): BidPriceIntelligence
    {
        $prices = $rows->pluck('winning_price')->filter(fn ($value) => is_numeric($value))->map(fn ($value) => (float) $value)->sort()->values();
        $count = $prices->count();
        $median = null;
        if ($count > 0) {
            $middle = intdiv($count, 2);
            $median = $count % 2 === 1 ? $prices[$middle] : ($prices[$middle - 1] + $prices[$middle]) / 2;
        }

        $latest = $rows->first();

        return new BidPriceIntelligence(
            latest: $latest?->winning_price !== null ? (string) $latest->winning_price : null,
            min: $count ? (string) $prices->min() : null,
            max: $count ? (string) $prices->max() : null,
            average: $count ? (string) ($prices->sum() / $count) : null,
            median: $median !== null ? (string) $median : null,
            count: $count,
            latestAwardDate: $latest?->decision_date?->toDateString() ?? $latest?->published_at?->toDateString(),
            recentAwards: $rows,
        );
    }

    private function identityKey(int $variantId, ?int $packageId): string
    {
        return 'variant:'.$variantId.':package:'.($packageId ?? 0);
    }
}
