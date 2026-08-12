<?php

namespace Modules\Website\Livewire\Products;

use Livewire\Component;
use Modules\Product\Services\ProductService;

class CategoryFilter extends Component
{
    public $activeSlug = null;

    // Hàm xử lý khi click chọn danh mục
    public function setCategory($slug)
    {
        // 1. Cập nhật state nội bộ để highlight nút
        $this->activeSlug = ($slug === '') ? null : $slug;

        // 2. Bắn sự kiện lên để ProductList (hoặc component nào lắng nghe) biết
        $this->dispatch('filter-category-updated', slug: $this->activeSlug);
    }

    public function render(ProductService $products)
    {
        // Lấy danh mục cha
        $categories = $products->storefrontRootCategories();

        return view('Website::livewire.products.category-filter', [
            'categories' => $categories,
        ]);
    }
}
