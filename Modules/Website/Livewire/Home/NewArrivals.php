<?php

namespace Modules\Website\Livewire\Home;

use Livewire\Component;
use Modules\Website\Models\WpProduct;
use Modules\Admin\Models\Setting; // Import Model Setting
use Illuminate\Database\Eloquent\Collection;

class NewArrivals extends Component
{
    public Collection $products;

    public function mount()
    {
        // 1. Lấy cấu hình số lượng từ Admin (Mặc định 10 nếu chưa set)
        $limit = Setting::where('key', 'home_new_arrivals_count')->value('value');
        $limit = $limit ? (int)$limit : 10;

        // 2. Query tự động theo limit
        $this->products = WpProduct::where('is_active', true)
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
