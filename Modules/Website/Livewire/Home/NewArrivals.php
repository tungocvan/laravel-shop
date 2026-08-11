<?php

namespace Modules\Website\Livewire\Home;

use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;
use Modules\Product\Models\Product;
use Modules\Website\Services\SettingsService;

class NewArrivals extends Component
{
    public Collection $products;

    public function mount(SettingsService $settings)
    {
        // 1. Lấy cấu hình số lượng từ Admin (Mặc định 10 nếu chưa set)
        $limit = (int) $settings->get('home_new_arrivals_count', 10);

        // 2. Query tự động theo limit
        $this->products = Product::where('is_active', true)
            ->latest('created_at') // Mới nhất lên đầu
            ->take($limit)         // Lấy theo số lượng cấu hình
            ->with('categories')
            ->get();
    }

    public function addToCart($productId)
    {
        $this->dispatch('add-to-cart', productId: $productId);
        $this->dispatch('alert', type: 'success', message: 'Đã thêm vào giỏ hàng!');
    }

    public function render()
    {
        return view('Website::livewire.home.new-arrivals');
    }
}
