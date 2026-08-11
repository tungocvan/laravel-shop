@extends('Website::layouts.frontend')
@section('content')
    @php
        $bank = config('website.website.payment.bank_transfer', config('website.payment.bank_transfer', []));
    @endphp

    <div class="max-w-3xl mx-auto py-16 px-4 sm:px-6 lg:px-8">
        @if (session('payment_error'))
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                {{ session('payment_error') }}
            </div>
        @endif

        @if ($order->payment_method === 'bank_transfer' && $order->status === 'pending_payment')
            <div class="bg-white rounded-lg shadow-lg border border-emerald-200 overflow-hidden p-8">
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-100 mb-4">
                        <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M5 6h14M5 14h14M7 18h10"></path>
                        </svg>
                    </div>
                    <h2 class="text-3xl font-extrabold text-gray-900">Thông tin chuyển khoản</h2>
                    <p class="text-gray-500 mt-2">Đơn hàng đã được tạo và đang chờ xác nhận thanh toán.</p>
                </div>

                <div class="rounded-xl border border-gray-200 bg-gray-50 p-6 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500">Ngân hàng</p>
                            <p class="font-bold text-gray-900">{{ $bank['bank_name'] ?? 'Chưa cấu hình' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Chủ tài khoản</p>
                            <p class="font-bold text-gray-900">{{ $bank['account_name'] ?? 'Chưa cấu hình' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Số tài khoản</p>
                            <p class="font-bold text-gray-900 select-all">{{ $bank['account_number'] ?? 'Chưa cấu hình' }}</p>
                        </div>
                        @if (!empty($bank['branch']))
                            <div>
                                <p class="text-gray-500">Chi nhánh</p>
                                <p class="font-bold text-gray-900">{{ $bank['branch'] }}</p>
                            </div>
                        @endif
                    </div>

                    <div class="border-t border-gray-200 pt-4">
                        <p class="text-sm text-gray-500">Số tiền</p>
                        <p class="text-2xl font-extrabold text-emerald-700">{{ number_format($order->total) }}đ</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Nội dung chuyển khoản</p>
                        <div class="mt-1 rounded-lg border border-dashed border-emerald-400 bg-white px-4 py-3 text-lg font-bold text-emerald-700 select-all">
                            {{ $order->order_code }}
                        </div>
                    </div>

                    @if (!empty($bank['instructions']))
                        <p class="text-sm text-gray-600">{{ $bank['instructions'] }}</p>
                    @endif
                </div>

                <div class="mt-8 flex justify-center gap-4">
                    @auth
                        <a href="{{ route('account.orders') }}" class="text-blue-600 font-medium hover:underline">Xem đơn hàng</a>
                    @endauth
                    <a href="{{ route('product.list') }}" class="text-blue-600 font-medium hover:underline">Tiếp tục mua sắm</a>
                </div>
            </div>
        @elseif ($order->payment_method === 'momo' && $order->status === 'pending_payment')
            <div class="bg-white rounded-lg shadow-lg border border-pink-200 overflow-hidden text-center p-8">
                <img src="https://developers.momo.vn/v3/img/logo.svg" class="w-14 h-14 mx-auto mb-5" alt="MoMo">
                <h2 class="text-3xl font-extrabold text-gray-900 mb-2">Thanh toán MoMo chưa hoàn tất</h2>
                <p class="text-gray-600 mb-3">Đơn hàng <strong>{{ $order->order_code }}</strong> đang ở trạng thái chờ thanh toán.</p>
                <p class="text-sm text-gray-500">Website không tự xác nhận thanh toán nếu chưa nhận được phản hồi MoMo hợp lệ.</p>

                <div class="mt-8 flex justify-center gap-4">
                    @auth
                        <a href="{{ route('account.orders') }}" class="text-blue-600 font-medium hover:underline">Xem đơn hàng</a>
                    @endauth
                    <a href="{{ route('home') }}" class="text-blue-600 font-medium hover:underline">Về trang chủ</a>
                </div>
            </div>
        @else
            <div class="bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden text-center p-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 mb-6">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h2 class="text-3xl font-extrabold text-gray-900 mb-2">
                    {{ $order->payment_method === 'momo' ? 'Thanh toán thành công!' : 'Đặt hàng thành công!' }}
                </h2>
                <p class="text-gray-500 mb-6">
                    Mã đơn hàng của bạn là <span class="font-bold text-gray-900">{{ $order->order_code }}</span>
                </p>

                <div class="flex justify-center gap-4">
                    @auth
                        <a href="{{ route('account.orders') }}" class="text-blue-600 font-medium hover:underline">Xem lịch sử đơn hàng</a>
                        <span class="text-gray-300">|</span>
                    @endauth
                    <a href="{{ route('product.list') }}" class="text-blue-600 font-medium hover:underline">Tiếp tục mua sắm</a>
                </div>
            </div>
        @endif
    </div>
@endsection
