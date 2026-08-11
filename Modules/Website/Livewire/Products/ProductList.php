<?php

namespace Modules\Website\Livewire\Products;

use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Product\Services\ProductService;
use Modules\Website\Services\CartService;

class ProductList extends Component
{
    use WithPagination;

    // Giữ trạng thái trên URL để người dùng có thể copy link đã lọc
    #[Url(history: true)]
    public $search = '';

    #[Url(history: true)]
    public $categorySlug = null;

    #[Url(history: true)]
    public $sort = 'latest';

    #[Url(history: true)]
    public $price_range = ''; // Dạng chuỗi: "min-max"

    public $selected_categories = []; // Dùng cho Checkbox Sidebar

    public $view_mode = 'grid';

    // LẮNG NGHE SỰ KIỆN
    #[On('search-updated')]
    public function updateSearch($search)
    {
        $this->search = $search;
        $this->resetPage();
    }

    #[On('sort-updated')]
    public function updateSort($sort)
    {
        $this->sort = $sort;
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset(['selected_categories', 'price_range', 'search', 'categorySlug']);
        $this->resetPage();
    }

    public function addToCart($productId)
    {
        try {
            app(CartService::class)->addItem($productId);
            $this->dispatch('cart-updated');
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function render(ProductService $products)
    {
        // 1. Lấy Categories cho Sidebar Filter
        $categories = $products->storefrontCategories();

        return view('Website::livewire.products.product-list', [
            'products' => $products->paginateStorefront([
                'search' => $this->search,
                'selected_categories' => $this->selected_categories,
                'category_slug' => $this->categorySlug,
                'price_range' => $this->price_range,
                'sort' => $this->sort,
            ]),
            'categories' => $categories, // Truyền biến fix lỗi Undefined
        ]);
    }

    public function paginationView()
    {
        return 'Website::livewire.partials.pagination';
    }
}
