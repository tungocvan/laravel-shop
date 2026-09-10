@extends('Admin::layouts.master')

@section('title', 'Đồng bộ hóa đơn')

@section('content')
    <div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Canonical GDT acquisition</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-gray-900">Đồng bộ hóa đơn GDT</h1>
                <p class="mt-1 max-w-4xl text-sm leading-6 text-gray-500">Đây là điểm duy nhất của hệ thống được phép lấy dữ liệu trực tiếp từ GDT. Hệ thống lưu RAW header + detail trên server để Inventory và các nghiệp vụ khác tái sử dụng mà không gọi GDT lần hai.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.invoices.source-data') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700">Dữ liệu nguồn đã lưu</a>
                @include('Invoices::partials.dashboard-return-link')
            </div>
        </div>

        @if ($gdtReady)
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800">
                <div>
                    <p class="font-semibold">GDT đã sẵn sàng</p>
                    <p class="mt-1 text-emerald-700">Token đã được preflight với GDT. Khi bắt đầu đồng bộ, RAW đã có sẽ được tái sử dụng; chỉ dữ liệu còn thiếu mới cần lấy thêm.</p>
                </div>
                <a href="{{ route('admin.invoices.create-token') }}" class="rounded-xl border border-emerald-300 bg-white px-4 py-2 font-semibold text-emerald-700">Quản lý kết nối</a>
            </div>
        @else
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
                <div>
                    <p class="font-semibold">GDT chưa sẵn sàng hoặc token đã hết hạn</p>
                    <p class="mt-1 text-amber-800">Hãy kết nối lại trước khi chạy đồng bộ. Hệ thống fail-closed và không đưa tác vụ hàng loạt vào queue khi token không vượt qua preflight.</p>
                </div>
                <a href="{{ route('admin.invoices.create-token') }}" class="rounded-xl bg-amber-600 px-4 py-2 font-semibold text-white">Kết nối GDT</a>
            </div>
        @endif

        @livewire('invoices.search-hoadon')
        @livewire('invoices.invoice-drive-sync-panel')
        @livewire('invoices.automatic-backup-panel')
    </div>
@endsection
