<?php

namespace Modules\Pharma\Services;

use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Models\PriceListItem;
use Modules\Pharma\Models\PriceListItemBidEvidence;

class PriceBidEvidenceService
{
    public function capture(PriceListItem $item, DrugBidAward $award, ?int $userId = null): PriceListItemBidEvidence
    {
        $source = $award->sources()->latest('id')->first();
        $match = $award->canonicalMatch;

        return PriceListItemBidEvidence::query()->updateOrCreate(
            ['price_list_item_id' => $item->id],
            [
                'drug_bid_award_id' => $award->id,
                'source_system' => $source?->source_system ?? $award->source_type,
                'source_record_key' => $source?->source_record_key ?? ($award->source_id ? (string) $award->source_id : null),
                'bid_price' => $award->winning_price,
                'quantity' => $award->quantity,
                'unit' => $award->unit,
                'investor_code' => $award->investor_code,
                'investor_name' => $award->investor_name,
                'contractor_code' => $award->contractor_code,
                'contractor_name' => $award->winning_company_name,
                'decision_number' => $award->decision_number,
                'award_date' => $award->decision_date ?? $award->published_at?->toDateString(),
                'medicine_id' => $match?->medicine_id ?? $item->medicine_id,
                'medicine_variant_id' => $match?->medicine_variant_id ?? $item->medicine_variant_id,
                'medicine_package_id' => $match?->medicine_package_id ?? $item->medicine_package_id,
                'captured_at' => now(),
                'captured_by' => $userId,
                'metadata' => ['selection' => 'price_list_reference', 'resolution_level' => $match?->resolution_level],
            ],
        );
    }
}
