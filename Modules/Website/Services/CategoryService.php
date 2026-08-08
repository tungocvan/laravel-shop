<?php

namespace Modules\Website\Services;

use Modules\Website\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    /**
     * Lấy danh mục nổi bật hiển thị trang chủ
     * Logic: Lấy danh mục cha (parent_id = null), sắp xếp theo sort_order
     */
    public function getHomeCategories(int $limit = 8): Collection
    {
        return Category::query()
            ->active()
            ->root() // Scope defined in Model
            ->where('type', 'product') // Chỉ lấy danh mục sản phẩm
            ->sorted() // Scope defined in Model
            ->take($limit)
            ->get();
    }
}
