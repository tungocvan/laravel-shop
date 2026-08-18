@extends('Admin::layouts.master')

@section('title', 'Danh sách đã đồng bộ')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Mua sắm công</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Danh sách đã đồng bộ</h1>
                <p class="mt-1 text-sm text-gray-500">Quản lý các thuốc đã lưu vào database, bổ sung KQLCNT, cập nhật đơn vị trúng thầu và xóa dữ liệu không còn cần theo dõi.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('muasamcong.wishlist') }}" class="inline-flex items-center justify-center rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-100">♥ Wishlist</a>
                <a href="{{ route('muasamcong.index') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">← Về tra cứu thuốc</a>
            </div>
        </div>

        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
            BBG sử dụng đúng bố cục template INAFO: đầy đủ header công ty, tiêu đề BẢNG CHÀO GIÁ, 19 cột dữ liệu và phần ký GIÁM ĐỐC CÔNG TY. Hãy tick các dòng trong bảng rồi bấm “Xuất BBG (X)” trong thanh thao tác của danh sách.
        </div>

        @livewire('muasamcong.synced-pricing-list')
    </div>
@endsection
