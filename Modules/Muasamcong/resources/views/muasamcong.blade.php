@extends('Admin::layouts.master')

@section('title', 'Tra cứu Mua sắm công')

@section('content')
    <div class="space-y-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Tra cứu Mua sắm công</h1>
            <p class="mt-1 text-sm text-gray-500">Tra cứu đơn giá thuốc trúng thầu và lịch sử gói thầu doanh nghiệp đã tham gia trên Hệ thống mạng đấu thầu quốc gia.</p>
        </div>

        <section class="space-y-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Thuốc & đơn giá trúng thầu</h2>
                <p class="text-sm text-gray-500">Smart Pricing, wishlist và đồng bộ dữ liệu mặt hàng.</p>
            </div>
            @livewire('muasamcong.tracuu-thuoctrungthau')
        </section>

        <section class="space-y-3 border-t border-gray-200 pt-8">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Lịch sử nhà thầu</h2>
                <p class="text-sm text-gray-500">Danh sách gói thầu đã tham gia. Dữ liệu này chưa đồng nghĩa với kết quả trúng thầu.</p>
            </div>
            @livewire('muasamcong.contractor-history')
        </section>
    </div>
@endsection
