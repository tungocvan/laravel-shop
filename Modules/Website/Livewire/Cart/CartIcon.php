<?php

namespace Modules\Website\Livewire\Cart;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\App;
use Modules\Website\Services\CartService;

class CartIcon extends Component
{
    // Sử dụng computed property để cache kết quả trong 1 request vòng đời Livewire
    #[Computed]
    public function count()
    {
        try {
            $cartService = App::make(CartService::class);
            $cart = $cartService->getCart(); // Gọi Service lấy cart chuẩn

            // Tính tổng số lượng items (sum quantity) chứ không phải count dòng
            // Ví dụ: Mua 2 cái áo + 3 cái quần = 5 items
            return $cart ? $cart->items->sum('quantity') : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    // Lắng nghe sự kiện để refresh component
    #[On('cart-updated')]
    public function refreshCart()
    {
        // Khi dispatch cart-updated, Livewire sẽ re-render component
        // và hàm computed 'count' sẽ được tính lại.
        unset($this->count);
    }

    public function render()
    {
        return view('Website::livewire.cart.cart-icon');
    }
}
