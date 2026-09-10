@extends('Admin::layouts.master')

@section('title', request()->filled('inbox') ? 'Xử lý nhập kho' : 'Hóa đơn chờ nhập kho')

@section('content')
<div class="space-y-6">
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Invoices → Inventory</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">{{ request()->filled('inbox') ? 'Xử lý nhập kho' : 'Hóa đơn chờ nhập kho' }}</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                @if(request()->filled('inbox'))
                    Đối chiếu mặt hàng, bổ sung thông tin lô/HSD, chọn kho và kiểm tra trước khi xác nhận nhập kho.
                @else
                    Chọn năm, tháng để xem hóa đơn mua hàng. Hóa đơn đủ dữ liệu có thể bắt đầu nhập kho ngay; hóa đơn thiếu chi tiết sẽ được đánh dấu để xử lý tại Invoices.
                @endif
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if(request()->filled('inbox'))
                <a href="{{ route('admin.inventory.invoice-inbox') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-indigo-300 bg-white px-5 py-2.5 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">← Danh sách hóa đơn</a>
            @endif
            <a href="{{ route('admin.inventory.dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:border-indigo-300">Inventory Dashboard</a>
        </div>
    </header>

    @if(request()->filled('inbox'))
        <livewire:inventory.invoice-inbox-workspace :selected-inbox-id="(int) request('inbox')" />
    @else
        <livewire:inventory.invoice-receiving-source-queue />
    @endif
</div>
@endsection
