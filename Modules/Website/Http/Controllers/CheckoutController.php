<?php

namespace Modules\Website\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Order\Models\Order;
use Modules\Website\Models\Cart;
use Modules\Website\Services\CheckoutService;
use Modules\Website\Services\MomoService;

class CheckoutController extends Controller
{
    public function index()
    {
        $sessionId = session()->getId();
        $hasCart = Cart::where('session_id', $sessionId)->exists();

        if (! $hasCart) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng đang trống!');
        }

        return view('Website::checkout.index');
    }

    public function success()
    {
        if (! session()->has('order_code')) {
            return redirect()->route('home');
        }

        $orderCode = session('order_code');
        $order = Order::where('order_code', $orderCode)->first();

        if (! $order) {
            return redirect()->route('home')->with('error', 'Không tìm thấy đơn hàng.');
        }

        return view('Website::checkout.success', compact('order'));
    }

    /**
     * Redirect URL: trình duyệt khách hàng quay về từ MoMo.
     */
    public function momoCallback(
        Request $request,
        CheckoutService $checkoutService,
        MomoService $momoService
    ): RedirectResponse {
        try {
            $order = $checkoutService->processMomoResult($request->all(), $momoService);

            session()->flash('order_code', $order->order_code);

            if ((int) $request->input('resultCode', -1) === 0) {
                session()->flash('success_message', 'Thanh toán MoMo thành công.');
            } else {
                session()->flash('payment_error', 'Thanh toán MoMo chưa thành công. Bạn có thể liên hệ cửa hàng để được hỗ trợ.');
            }

            return redirect()->route('checkout.success');
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('home')->with('error', 'Không thể xác minh kết quả thanh toán MoMo.');
        }
    }

    /**
     * IPN URL: MoMo gọi server-to-server. Endpoint này phải idempotent.
     */
    public function momoIpn(
        Request $request,
        CheckoutService $checkoutService,
        MomoService $momoService
    ): JsonResponse {
        try {
            $checkoutService->processMomoResult($request->all(), $momoService);

            return response()->json(['message' => 'received']);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'invalid payment notification'], 400);
        }
    }
}
