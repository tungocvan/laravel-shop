<?php

namespace Modules\Website\Livewire\Products;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use Modules\Product\Services\ProductService;
use Modules\Website\Services\CartService;

class ProductDetail extends Component
{
    public $product;

    public $reviews;

    public $quantity = 1;

    public $affiliateLink; // Link để người dùng mang đi chia sẻ

    public function mount($slug, Request $request, ProductService $products)
    {
        // 1. Lấy thông tin sản phẩm
        $this->product = $products->findActiveBySlugWithCategories($slug)->load('user');

        // 2. Xử lý Logic Affiliate (Người mua click vào link giới thiệu)
        if ($request->has('ref')) {
            // Lưu mã người giới thiệu vào Session trong 30 ngày (hoặc cookie)
            Session::put('affiliate_ref', $request->get('ref'));
        }

        // 3. Tạo link Affiliate cho người đang xem (để họ mang đi chia sẻ)
        if (auth()->check()) {
            // Nếu đã đăng nhập, gắn thêm ?ref=ID_CUA_HO vào link
            $this->affiliateLink = route('product.detail', ['slug' => $slug, 'ref' => auth()->id()]);
        } else {
            // Nếu chưa đăng nhập, chỉ hiện link gốc
            $this->affiliateLink = route('product.detail', ['slug' => $slug]);
        }

        $this->reviews = $products->approvedReviews($this->product);
    }

    public function increment()
    {
        if ($this->quantity < $this->product->quantity) {
            $this->quantity++;
        }
    }

    public function decrement()
    {
        if ($this->quantity > 1) {
            $this->quantity--;
        }
    }

    public function addToCart()
    {
        try {
            app(CartService::class)->addItem($this->product->id, $this->quantity);

            $this->dispatch('cart-updated');
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Đã thêm '.$this->product->title.' vào giỏ hàng!',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    // Lấy sản phẩm liên quan (Computed Property để tối ưu)
    public function getRelatedProductsProperty()
    {
        return app(ProductService::class)->relatedActive($this->product);
    }

    public function render()
    {
        return view('Website::livewire.products.product-detail');
    }
}
