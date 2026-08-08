<?php

namespace Modules\Website\Livewire\Products;

use Livewire\Component;
use Illuminate\Support\Facades\Session;
use Modules\Website\Models\WpProduct;
use Modules\Website\Models\Review;
use Modules\Website\Services\CartService;
use Illuminate\Http\Request;

class ProductDetail extends Component
{
    public $product;
    public $reviews;
    public $quantity = 1;
    public $affiliateLink; // Link để người dùng mang đi chia sẻ

    public function mount($slug, Request $request)
    {
        // 1. Lấy thông tin sản phẩm
        $this->product = WpProduct::with(['categories', 'user']) // Eager load user nếu cần
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

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

        $this->reviews = Review::where('product_id', $this->product->id)
                       ->where('is_approved', true)
                       ->latest()
                       ->get();
    }

    public function increment()
    {
        $this->quantity++;
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
                'message' => 'Đã thêm ' . $this->product->title . ' vào giỏ hàng!'
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    // Lấy sản phẩm liên quan (Computed Property để tối ưu)
    public function getRelatedProductsProperty()
    {
        return WpProduct::where('id', '!=', $this->product->id)
            ->whereHas('categories', function($q) {
                $q->whereIn('id', $this->product->categories->pluck('id'));
            })
            ->where('is_active', true)
            ->take(4)
            ->get();
    }

    public function render()
    {
        return view('Website::livewire.products.product-detail');
    }
}
