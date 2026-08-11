<?php

namespace Modules\Website\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Order\Services\OrderQueryService;

class AccountController extends Controller
{
    public function index(OrderQueryService $orders)
    {
        $totalOrders = $orders->countForCurrentUser();

        // Truyền biến sang view
        return view('Website::account.dashboard', compact('totalOrders'));
    }

    public function orders()
    {
        return view('Website::account.orders.index');
    }

    public function orderDetail($code)
    {
        return view('Website::account.orders.show', compact('code'));
    }

    /**
     * Trang Dashboard Affiliate
     */
    public function affiliate()
    {
        // Trả về view page (nơi chứa Livewire Component)
        return view('Website::account.affiliate');
    }

    public function profile()
    {
        return view('Website::pages.account.profile');
    }

    public function wishlist()
    {
        // Chỉ trả về view, logic lấy dữ liệu sẽ do Livewire đảm nhận
        return view('Website::pages.account.wishlist');
    }
}
