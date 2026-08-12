<?php

namespace Modules\Website\Livewire\Home;

use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;
use Modules\Website\Services\HomepageContentService;

class FeaturedProducts extends Component
{
    public Collection $products;

    public function mount(HomepageContentService $homepage)
    {
        $this->products = $homepage->featuredProducts();
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
