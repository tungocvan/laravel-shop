<?php

namespace Modules\Admin\Livewire\Dashboard;

use Livewire\Component;
use Modules\Website\Models\Order;

class RecentOrders extends Component
{
    public function render()
    {
        // Lấy 5 đơn hàng mới nhất
        $orders = Order::latest()->take(5)->get();

        return view('Admin::livewire.dashboard.recent-orders', [
            'orders' => $orders
        ]);
    }
}
