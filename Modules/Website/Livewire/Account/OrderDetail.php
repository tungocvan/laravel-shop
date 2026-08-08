<?php

namespace Modules\Website\Livewire\Account;

use Livewire\Component;
use Modules\Website\Models\Order;

class OrderDetail extends Component
{
    public $orderCode;

    public function mount($code)
    {
        $this->orderCode = $code;
    }

    public function render()
    {
        // Lấy đơn hàng theo code VÀ phải thuộc về user đang đăng nhập (Bảo mật)
        $order = Order::with('items')
            ->where('order_code', $this->orderCode)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return view('Website::livewire.account.order-detail', [
            'order' => $order
        ]);
    }
}
