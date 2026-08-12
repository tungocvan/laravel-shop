<?php

namespace Modules\Website\Livewire\Account;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Order\Services\OrderQueryService;

class OrderList extends Component
{
    use WithPagination;

    public function render(OrderQueryService $orders)
    {
        // Lấy đơn hàng của user đang đăng nhập
        $orders = $orders->paginateForCurrentUser();

        return view('Website::livewire.account.order-list', [
            'orders' => $orders,
        ]);
    }
}
