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

        <livewire:invoices.quick-gdt-connect />

        @livewire('invoices.search-hoadon')
        @livewire('invoices.invoice-drive-sync-panel')
        @livewire('invoices.automatic-backup-panel')
    </div>
@endsection
