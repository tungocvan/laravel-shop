<?php

namespace Modules\Website\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Order\Services\OrderQueryService;
use Illuminate\Http\Request;
use Modules\Website\Models\PharmaQuickOrderRequest;
use Modules\Website\Services\PharmaQuickOrderDisplayService;

class AccountController extends Controller
{
    public function index(OrderQueryService $orders)
    {
        $totalOrders = $orders->countForCurrentUser();

        // Truyền biến sang view
        return view('Website::account.dashboard', compact('totalOrders'));
    }

    public function orders(Request $request)
    {
        $pharmaRequests = PharmaQuickOrderRequest::query()
            ->with('inventoryIssue:id,status')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'pharma_page');

        return view('Website::account.orders.index', compact('pharmaRequests'));
    }

    public function pharmaRequestDetail(Request $request, int $id, PharmaQuickOrderDisplayService $display)
    {
        $pharmaRequest = PharmaQuickOrderRequest::query()
            ->with('inventoryIssue:id,status,posted_at')
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return view('Website::account.orders.pharma-request', [
            'pharmaRequest' => $pharmaRequest,
            'lines' => $display->lines($pharmaRequest),
        ]);
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
