<?php

namespace Modules\Pharma\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Pharma\Models\DrugBidAward;

class DrugBidAwardResultGroupService
{
    public function resultKey(DrugBidAward $award): string
    {
        $code = trim((string) $award->bidding_notice_code);
        return $code !== '' ? 'tbmt:'.$code : 'award:'.$award->id;
    }

    public function awardsQuery(DrugBidAward $award): Builder
    {
        $code = trim((string) $award->bidding_notice_code);
        return DrugBidAward::query()->when($code !== '', fn ($query) => $query->where('bidding_notice_code', $code), fn ($query) => $query->whereKey($award->id));
    }
}
