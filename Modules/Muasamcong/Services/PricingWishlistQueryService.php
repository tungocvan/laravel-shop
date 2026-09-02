<?php

namespace Modules\Muasamcong\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Muasamcong\Models\PricingWishlist;

class PricingWishlistQueryService
{
    public function query(int $userId, string $keyword = ''): Builder
    {
        $keyword = trim($keyword);

        return PricingWishlist::query()
            ->where('user_id', $userId)
            ->when($keyword !== '', function (Builder $query) use ($keyword): void {
                $query->where(function (Builder $nested) use ($keyword): void {
                    $nested->where('medicine_name', 'like', "%{$keyword}%")
                        ->orWhere('active_ingredient', 'like', "%{$keyword}%")
                        ->orWhere('medicine_group', 'like', "%{$keyword}%")
                        ->orWhere('ma_tbmt', 'like', "%{$keyword}%")
                        ->orWhere('search_keyword', 'like', "%{$keyword}%");
                });
            });
    }
}
