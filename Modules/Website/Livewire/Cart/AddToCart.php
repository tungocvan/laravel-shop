<?php

namespace Modules\Website\Livewire\Cart;

use Livewire\Component;
use Illuminate\Support\Facades\App;
use Modules\Website\Services\CartService;
use Modules\Website\Models\WpProduct;

class AddToCart extends Component
{
    public $productId;
    public $quantity = 1;
    public $style = 'default';

    // Biến để lưu trữ thông tin tồn kho
    public $productStock = 0;

    public function mount($productId, $style = 'default')
    {
        $this->productId = $productId;
        $this->style = $style;

        // Lấy thông tin tồn kho ngay lúc init
        $product = WpProduct::find($productId);
        $this->productStock = $product ? $product->quantity : 0;
    }

    public function addToCart()
    {
        // 1. Check nhanh ở frontend trước khi gọi service
        if ($this->productStock <= 0) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Sản phẩm này đã hết hàng!']);
            return;
        }

        if ($this->quantity > $this->productStock) {
            $this->dispatch('notify', ['type' => 'error', 'message' => "Kho chỉ còn {$this->productStock} sản phẩm."]);
            return;
        }

        try {
            $cartService = App::make(CartService::class);
            $cartService->addItem($this->productId, $this->quantity);

            $this->dispatch('cart-updated');

            // Thông báo thành công
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Đã thêm vào giỏ hàng!'
            ]);

            if ($this->style === 'default') {
                $this->quantity = 1;
            }

        } catch (\Exception $e) {
            // Bắt lỗi từ Service (VD: Hết hàng trong lúc đang thao tác)
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function render()
    {
        return view('Website::livewire.cart.add-to-cart');
    }
}
