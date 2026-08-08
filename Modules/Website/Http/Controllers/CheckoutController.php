<?php

namespace Modules\Website\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function index()
    {
        // Kiểm tra nếu giỏ hàng rỗng thì đá về trang chủ
        $sessionId = session()->getId();
        $hasCart = \Modules\Website\Models\Cart::where('session_id', $sessionId)->exists();

        if (!$hasCart) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng đang trống!');
        }

        return view('Website::checkout.index');
    }

    public function success()
    {
        if (!session()->has('order_code')) {
            return redirect()->route('home');
        }

        $orderCode = session('order_code');
        // Lấy thông tin đơn hàng để hiển thị số tiền và QR
        $order = \Modules\Website\Models\Order::where('order_code', $orderCode)->first();

        return view('Website::checkout.success', compact('order'));
    }

}
