<?php

namespace Modules\Website\Livewire\Account;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Website\Models\Order;

class OrderList extends Component
{
    use WithPagination;

    public function render()
    {
        // Lấy đơn hàng của user đang đăng nhập
        $orders = Order::where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        return view('Website::livewire.account.order-list', [
            'orders' => $orders
        ]);
    }
}
