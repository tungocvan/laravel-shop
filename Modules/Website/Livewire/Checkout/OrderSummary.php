<?php

namespace Modules\Website\Livewire\Checkout;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\App;
use Modules\Website\Services\CartService;

class OrderSummary extends Component
{
    public $couponCode = '';

    // Inject Service
    protected function getCartService()
    {
        return App::make(CartService::class);
    }

    // Lấy dữ liệu giỏ hàng (Computed Property)
    #[Computed]
    public function summary()
    {
        return $this->getCartService()->getCartSummary();
    }

    public function applyCoupon()
    {
        if (empty($this->couponCode)) return;

        try {
            $this->getCartService()->applyCoupon($this->couponCode);
            $this->couponCode = ''; // Reset input
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Áp dụng mã giảm giá thành công!']);

            // Emit sự kiện để các component khác (nếu có) update
            $this->dispatch('cart-updated');
        } catch (\Exception $e) {
            $this->addError('coupon', $e->getMessage());
        }
    }

    public function removeCoupon()
    {
        try {
            $this->getCartService()->removeCoupon();
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Đã gỡ mã giảm giá']);
            $this->dispatch('cart-updated');
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('Website::livewire.checkout.order-summary');
    }
}
