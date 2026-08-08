<?php

namespace Modules\Website\Services;

use Modules\Website\Models\Wishlist;
use Illuminate\Support\Facades\Auth;

class WishlistService
{
    /**
     * Lấy danh sách ID sản phẩm User đã like (Dùng để tối ưu N+1 Query)
     * Trả về mảng: [1, 5, 10, ...]
     */
    public function getUserWishlistIds($userId)
    {
        if (!$userId) return [];

        // Pluck chỉ lấy cột product_id -> Rất nhẹ
        return Wishlist::where('user_id', $userId)
            ->pluck('product_id')
            ->toArray();
    }

    /**
     * Toggle Like/Unlike
     * Return: 'added' hoặc 'removed'
     */
    public function toggle($userId, $productId)
    {
        $wishlist = Wishlist::where('user_id', $userId)
                            ->where('product_id', $productId)
                            ->first();

        if ($wishlist) {
            $wishlist->delete();
            return 'removed';
        } else {
            Wishlist::create([
                'user_id' => $userId,
                'product_id' => $productId
            ]);
            return 'added';
        }
    }

    /**
     * Đếm số lượng (Dùng cho Header Icon)
     */
    public function count($userId)
    {
        return Wishlist::where('user_id', $userId)->count();
    }
    /**
     * Lấy danh sách sản phẩm trong wishlist (có phân trang)
     */
    public function getWishlistItems($userId, $perPage = 10)
    {
        // Giả sử Model Wishlist có quan hệ 'product'
        // Hoặc query qua bảng products
        return \Modules\Website\Models\WpProduct::whereHas('wishlists', function($q) use ($userId) {
            $q->where('user_id', $userId);
        })->where('is_active', 1)->paginate($perPage);
    }
}
