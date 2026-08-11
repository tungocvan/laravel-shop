<?php

namespace Modules\Website\Livewire\Checkout;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use Modules\Website\Http\Requests\CheckoutRequest;
use Modules\Website\Services\CheckoutService;
use Modules\Website\Services\MomoService;

class CheckoutForm extends Component
{
    public $customer_name;
    public $customer_phone;
    public $customer_email;
    public $customer_address;
    public $note;
    public $payment_method = 'cod';

    protected function rules()
    {
        return (new CheckoutRequest())->rules();
    }

    protected function messages()
    {
        return (new CheckoutRequest())->messages();
    }

    public function mount()
    {
        if (Auth::check()) {
            $user = Auth::user();
            $this->customer_name = $user->name;
            $this->customer_email = $user->email;
            $this->customer_phone = $user->phone ?? '';
            $this->customer_address = $user->address ?? '';
        }
    }

    public function placeOrder(CheckoutService $checkoutService, MomoService $momoService)
    {
        $this->validate();

        try {
            $order = $checkoutService->createOrder([
                'customer_name' => $this->customer_name,
                'customer_phone' => $this->customer_phone,
                'customer_email' => $this->customer_email,
                'customer_address' => $this->customer_address,
                'note' => $this->note,
                'payment_method' => $this->payment_method,
            ]);
        } catch (\Throwable $e) {
            report($e);
            $this->addError('system', $e->getMessage());

            return null;
        }

        Session::regenerate();
        session()->flash('order_code', $order->order_code);

        if ($order->payment_method === 'momo') {
            try {
                $payment = $momoService->createPayment($order);

                return redirect()->away($payment['payUrl']);
            } catch (\Throwable $e) {
                report($e);
                session()->flash('payment_error', $e->getMessage());

                // Order đã được tạo và giữ trạng thái pending_payment. Không quay lại
                // checkout để tránh người dùng hiểu nhầm rằng order chưa tồn tại.
                return redirect()->route('checkout.success');
            }
        }

        session()->flash('success_message', 'Đặt hàng thành công!');

        return redirect()->route('checkout.success');
    }

    public function render()
    {
        return view('Website::livewire.checkout.checkout-form');
    }
}
