<?php

namespace Modules\Admin\Livewire\Dashboard;

use Livewire\Component;
use Modules\Website\Models\Order;
use Carbon\Carbon;

class RevenueChart extends Component
{
    public $labels = [];
    public $data = [];

    public function mount()
    {
        // Lấy doanh thu 7 ngày gần nhất
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);

            // Format ngày hiển thị trục X (VD: 16/01)
            $this->labels[] = $date->format('d/m');

            // Tính tổng tiền ngày đó (đơn đã completed)
            $sum = Order::whereDate('created_at', $date->format('Y-m-d'))
                        ->where('status', 'completed')
                        ->sum('total');

            $this->data[] = $sum;
        }
    }

    public function render()
    {
        return view('Admin::livewire.dashboard.revenue-chart');
    }
}
