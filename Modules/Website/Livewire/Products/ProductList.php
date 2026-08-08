<?php

namespace Modules\Website\Livewire\Products;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Modules\Website\Models\WpProduct;
use Modules\Website\Models\Category;
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
                'message' => $e->getMessage()
            ]);
        }
    }

    public function render()
    {
        // 1. Lấy Categories cho Sidebar Filter
        $categories = Category::withCount('products')
            ->whereNull('parent_id') // Lấy danh mục cha
            ->where('type', 'product')
            ->get();

        // 2. Khởi tạo Query
        $query = WpProduct::query()->where('is_active', true);

        // Filter: Search
        if ($this->search) {
            $query->where(fn($q) =>
                $q->where('title', 'like', "%{$this->search}%")
                  ->orWhere('short_description', 'like', "%{$this->search}%")
            );
        }

        // Filter: Categories (Ưu tiên Checkbox, sau đó đến Slug từ URL)
       // LỌC THEO CATEGORY (Sửa ở đây)
        if (!empty($this->selected_categories)) {
            $query->whereHas('categories', function($q) {
                $q->whereIn('categories.id', $this->selected_categories);
            });
        } elseif ($this->categorySlug) {
            $query->whereHas('categories', function($q) {
                $q->where('categories.slug', $this->categorySlug);
            });
        }

        // Filter: Price Range (Xử lý chuỗi "min-max")
        if ($this->price_range) {
            $parts = explode('-', $this->price_range);
            if (count($parts) == 2) {
                $min = (int)$parts[0];
                $max = (int)$parts[1];
                // Lọc trên giá cuối cùng (Sale price nếu có, không thì Regular price)
                $query->whereRaw('COALESCE(sale_price, regular_price) BETWEEN ? AND ?', [$min, $max]);
            }
        }

        // 3. Sorting
        switch ($this->sort) {
            case 'price_asc':
                $query->orderByRaw('COALESCE(sale_price, regular_price) ASC');
                break;
            case 'price_desc':
                $query->orderByRaw('COALESCE(sale_price, regular_price) DESC');
                break;
            case 'name_asc':
                $query->orderBy('title', 'asc');
                break;
            case 'latest':
            default:
                $query->latest();
                break;
        }

        return view('Website::livewire.products.product-list', [
            'products' => $query->paginate(12),
            'categories' => $categories // Truyền biến fix lỗi Undefined
        ]);
    }

    public function paginationView()
    {
        return 'Website::livewire.partials.pagination';
    }
}
