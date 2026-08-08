<?php

namespace Modules\Website\Services;

use Modules\Website\Models\WpProduct;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ProductService
{
    /**
     * Lấy sản phẩm nổi bật
     * Logic: Check cột tags (JSON) có chứa giá trị "featured"
     */
    public function getFeaturedProducts(int $limit = 8): Collection
    {
        return WpProduct::query()
            ->active() // ScopeActive đã có trong Model WpProduct
            ->whereJsonContains('tags', 'featured')
            ->latest()
            ->take($limit)
            ->get();
    }

    /**
     * Lấy sản phẩm mới nhất
     */
    public function getNewArrivals(int $limit = 10): Collection
    {
        return WpProduct::query()
            ->active()
            ->latest('created_at')
            ->take($limit)
            ->get();
    }

    /**
     * Lấy sản phẩm Flash Sale (Giả lập logic giảm giá)
     */
    public function getFlashSaleProducts(int $limit = 6): Collection
    {
        return WpProduct::query()
            ->active()
            ->whereNotNull('sale_price')
            ->whereColumn('sale_price', '<', 'regular_price')
            ->inRandomOrder()
            ->take($limit)
            ->get();
    }

    /**
     * Lấy sản phẩm bán chạy (Best Sellers)
     */
    public function getBestSellers(int $limit = 8): Collection
    {
        // 1. Aggregation từ bảng order_items
        $topProductIds = DB::table('order_items')
            ->select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->pluck('product_id');

        // 2. Fallback nếu data trắng
        if ($topProductIds->isEmpty()) {
            return $this->getFeaturedProducts($limit);
        }

        // 3. Fetch Product details
        return WpProduct::query()
            ->active()
            ->whereIn('id', $topProductIds)
            ->get()
            ->sortBy(function ($model) use ($topProductIds) {
                return array_search($model->id, $topProductIds->toArray());
            });
    }
    public function getBestSellingProducts($limit = 5)
    {
        // TRONG THỰC TẾ (Production):
        // Bạn sẽ join với bảng order_items, sum(quantity) và order by desc.
        // Ví dụ:
        // return WpProduct::withCount('orderItems')
        //     ->orderBy('order_items_count', 'desc')
        //     ->take($limit)->get();

        // HIỆN TẠI (Demo UI):
        // Chúng ta lấy ngẫu nhiên để test giao diện Bảng Xếp Hạng.
        // Có thể ưu tiên lấy những sản phẩm có tag 'hot' nếu muốn.
        return WpProduct::query()
            ->where('is_active', true)
            ->with(['categories']) // Eager load để tránh lỗi N+1 ở View
            ->inRandomOrder()      // Random để mỗi lần F5 thấy sản phẩm khác nhau cho sinh động
            ->take($limit)
            ->get();
    }
}
