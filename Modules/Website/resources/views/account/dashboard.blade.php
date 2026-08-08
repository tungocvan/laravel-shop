@extends('Website::layouts.account')

@section('content-account')

<div class="bg-white rounded-lg shadow border border-gray-200 p-6">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Xin chào, {{ auth()->user()->name }}!</h1>

    <p class="text-gray-600 mb-6">
        Chào mừng bạn quay trở lại. Từ bảng điều khiển tài khoản, bạn có thể xem các <span class="font-bold text-gray-800">đơn đặt hàng gần đây</span>, quản lý <span class="font-bold text-gray-800">địa chỉ giao hàng</span> và <span class="font-bold text-gray-800">sửa mật khẩu</span> và thông tin tài khoản.
    </p>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        <a href="{{ route('account.orders') }}" class="block group">
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-100 flex items-center gap-4 transition-all duration-200 group-hover:shadow-md group-hover:border-blue-300 cursor-pointer">
                <div class="p-3 bg-blue-100 rounded-full text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 font-medium group-hover:text-blue-700">Đơn hàng của tôi</p>
                    <p class="text-2xl font-bold text-gray-900 group-hover:text-blue-700">
                        {{ $totalOrders }}
                    </p>
                </div>
            </div>
        </a>

        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 flex items-center gap-4 opacity-60">
            <div class="p-3 bg-gray-200 rounded-full text-gray-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500">Sổ địa chỉ</p>
                <p class="text-xl font-bold text-gray-900">-</p>
            </div>
        </div>

    </div>
</div>

@endsection
