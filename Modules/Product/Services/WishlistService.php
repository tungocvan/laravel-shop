<?php

namespace Modules\Product\Services;

use Modules\Product\Models\Product;
use Modules\Product\Models\Wishlist;

class WishlistService
{
    public function getUserWishlistIds($userId): array
    {
        if (! $userId) {
            return [];
        }

        return Wishlist::query()
            ->where('user_id', $userId)
            ->pluck('product_id')
            ->toArray();
    }

    public function toggle($userId, $productId): string
    {
        $wishlist = Wishlist::query()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        if ($wishlist) {
            $wishlist->delete();

            return 'removed';
        }

        Wishlist::query()->create([
            'user_id' => $userId,
            'product_id' => $productId,
        ]);

        return 'added';
    }

    public function count($userId): int
    {
        return Wishlist::query()
            ->where('user_id', $userId)
            ->count();
    }

    public function getWishlistItems($userId, int $perPage = 10)
    {
        return Product::query()
            ->whereHas('wishlists', function ($query) use ($userId): void {
                $query->where('user_id', $userId);
            })
            ->where('is_active', 1)
            ->paginate($perPage);
    }
}
