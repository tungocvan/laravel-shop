<?php

namespace Modules\Website\Livewire\Dashboard;

use App\Models\User;
use Livewire\Component;
use Modules\Order\Models\Order;
use Modules\Product\Models\Product;

class StatsOverview extends Component
{
    public $totalRevenue = 0;

    public $newOrdersCount = 0;

    public $totalProducts = 0;

    public $totalCustomers = 0;

    // Biến tính % tăng trưởng (để làm màu cho đẹp)
    public $revenueGrowth = 0;

    public function mount()
    {
        // 1. Tổng doanh thu (Chỉ tính đơn đã hoàn thành)
        $this->totalRevenue = Order::where('status', 'completed')->sum('total');

        // 2. Số đơn hàng MỚI (Pending) cần xử lý
        $this->newOrdersCount = Order::where('status', 'pending')->count();

        // 3. Tổng sản phẩm đang bán
        $this->totalProducts = Product::where('is_active', true)->count();

        // 4. Tổng khách hàng
        $this->totalCustomers = User::count();

        // --- Logic tính tăng trưởng giả lập (hoặc thật nếu muốn) ---
        // Ví dụ: Doanh thu hôm nay so với hôm qua. Ở đây mình hardcode nhẹ để demo UI
        $this->revenueGrowth = 12.5;
    }

    public function render()
    {
        return view('Website::livewire.dashboard.stats-overview');
    }
}
