<?php

namespace Modules\Website\Livewire\Account;

use Livewire\Component;
use Modules\Order\Services\OrderQueryService;

class OrderDetail extends Component
{
    public $orderCode;

    public function mount($code)
    {
        $this->orderCode = $code;
    }

    public function render(OrderQueryService $orders)
    {
        // Lấy đơn hàng theo code VÀ phải thuộc về user đang đăng nhập (Bảo mật)
        $order = $orders->findForCurrentUser($this->orderCode);

        return view('Website::livewire.account.order-detail', [
            'order' => $order,
        ]);
    }
}
