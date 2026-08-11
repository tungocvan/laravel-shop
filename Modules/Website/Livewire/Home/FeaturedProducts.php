<?php

namespace Modules\Website\Livewire\Home;

use Illuminate\Database\Eloquent\Collection;
use Livewire\Component; // Model sản phẩm
use Modules\Product\Models\Product;
use Modules\Website\Services\SettingsService;

class FeaturedProducts extends Component
{
    public Collection $products;

    public function mount(SettingsService $settings)
    {
        // 1. Lấy danh sách ID từ Settings
        $featuredIds = array_values(array_filter(array_map('intval', (array) $settings->get('home_featured_ids', []))));

        if (! empty($featuredIds)) {
            // 2. Query sản phẩm theo ID và sắp xếp đúng thứ tự Admin chọn
            // Sử dụng FIELD(id, ...) của MySQL để sort
            $idsString = implode(',', $featuredIds);

            $this->products = Product::whereIn('id', $featuredIds)
                ->where('is_active', true) // Chỉ lấy sp đang bật
                ->orderByRaw("FIELD(id, $idsString)")
                ->with('categories') // Eager load category để tránh N+1 query
                ->get();
        } else {
            // Fallback: Nếu chưa chọn gì thì lấy tạm 10 sp mới nhất
            $this->products = Product::where('is_active', true)
                ->latest()
                ->take(10)
                ->with('categories')
                ->get();
        }
    }

    public function addToCart($productId)
    {
        // Emit sự kiện để Global Cart xử lý (Clean Architecture)
        $this->dispatch('add-to-cart', productId: $productId);
        $this->dispatch('alert', type: 'success', message: 'Đã thêm vào giỏ hàng!');
    }

    public function render()
    {
        return view('Website::livewire.home.featured-products');
    }
}
