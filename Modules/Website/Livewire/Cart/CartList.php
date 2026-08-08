<?php

namespace Modules\Website\Livewire\Cart;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Modules\Website\Services\CartService;
use Illuminate\Support\Facades\App;

class CartList extends Component
{
    public $couponCodeInput = '';

    // Inject Service (Laravel 12 style hoặc boot)
    protected function getCartService()
    {
        return App::make(CartService::class);
    } 

    #[Computed]
    public function cartData()
    {
        return $this->getCartService()->getCartSummary();
    }

    public function increment($itemId)
    {
        try {
            $this->getCartService()->incrementItem($itemId);
            unset($this->cartData);

            $this->dispatch('cart-updated');
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function decrement($itemId)
    {
        try {
            $this->getCartService()->decrementItem($itemId);
            unset($this->cartData);

            $this->dispatch('cart-updated');
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function remove($itemId)
    {
        try {
            $this->getCartService()->removeItem($itemId);
            unset($this->cartData);
            $this->dispatch('cart-updated');
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Đã xóa sản phẩm']);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function applyCoupon()
    {
        try {
            $this->getCartService()->applyCoupon($this->couponCodeInput);
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Áp dụng mã giảm giá thành công!']);
            $this->couponCodeInput = ''; // Reset input
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function removeCoupon()
    {
         $this->getCartService()->removeCoupon();
         $this->dispatch('notify', ['type' => 'success', 'message' => 'Đã gỡ mã giảm giá']);
    }

    public function render()
    {
        return view('Website::livewire.cart.cart-list');
    }
}
