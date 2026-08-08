<?php

namespace Modules\Website\Livewire\Products;

use Livewire\Component;
use Modules\Website\Models\Category;

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

    public function render()
    {
        // Lấy danh mục cha
        $categories = Category::active()
            ->root()
            ->orderBy('sort_order')
            ->get();

        return view('Website::livewire.products.category-filter', [
            'categories' => $categories
        ]);
    }
}
