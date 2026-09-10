@extends('Admin::layouts.master')

@section('title', 'Dữ liệu nguồn GDT')

@section('content')
@php
    $classificationDrilldown = request()->filled('businessClassification');
@endphp
<div class="mx-auto w-full max-w-[1600px] space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <header class="border-b border-slate-200 pb-6">
        <a href="{{ route('admin.invoices.dashboard') }}" class="inline-flex min-h-10 items-center gap-1 rounded-lg text-sm font-semibold text-slate-600 transition hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">← Quay về Dashboard</a>

        <div class="mt-3 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Invoices · Nguồn canonical</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Dữ liệu nguồn GDT</h1>
                <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600">Quản trị dữ liệu canonical đã lấy và lưu từ GDT, gồm header, chi tiết nguồn, phân loại nghiệp vụ và phân loại chi phí cấp 2. Màn hình này chỉ đọc dữ liệu nguồn đã lưu và không phát sinh yêu cầu tới GDT.</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                @if (Route::has('admin.inventory.invoice-inbox'))
                    <a href="{{ route('admin.inventory.invoice-inbox') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700 shadow-sm transition hover:border-indigo-300 hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">Tiếp tục nhập kho →</a>
                @endif
                <a href="{{ route('admin.invoices.hoadon') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">Đồng bộ GDT</a>
                <a href="{{ route('admin.invoices.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">Danh sách hóa đơn</a>
            </div>
        </div>
    </header>

    <div class="rounded-2xl border border-amber-100 bg-amber-50/60 px-4 py-3 text-sm text-amber-950 sm:px-5">
        <span class="font-semibold">Phân loại chi phí:</span>
        Khi chọn Dịch vụ / Chi phí, có thể chọn tiếp danh mục chi phí cấp 2 từ Master Data. Danh mục này có thể mở rộng về sau mà không thay cấu trúc hóa đơn nguồn.
    </div>

    <div class="rounded-2xl border border-indigo-100 bg-indigo-50/60 px-4 py-3 text-sm text-indigo-900 sm:px-5">
        <span class="font-semibold">Quy trình tiếp theo:</span>
        Hóa đơn mua vào đã phân loại Hàng hóa/Hỗn hợp được review tại Inventory Inbox → matching mặt hàng → chọn kho → tạo phiếu nhập DRAFT → xác nhận phiếu mới phát sinh tồn kho.
    </div>

    <livewire:invoices.source-data-manager
        :year="request('year', $classificationDrilldown ? 'all' : now()->format('Y'))"
        :month="request('month', $classificationDrilldown ? 'all' : now()->format('n'))"
        :business-classification="request('businessClassification', 'all')"
    />
</div>
@endsection
