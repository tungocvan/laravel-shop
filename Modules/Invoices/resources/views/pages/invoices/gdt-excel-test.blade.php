@extends('Admin::layouts.master')

@section('title', 'GDT Excel Test')

@section('content')
<div class="mx-auto w-full max-w-5xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.16em] text-indigo-600">Invoices · Test độc lập</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Đồng bộ GDT → Excel</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-500">Đăng nhập tạm bằng MST của công ty cần kiểm tra, chọn khoảng ngày và tải hóa đơn mua vào/bán ra thành Excel. Dữ liệu test không import vào danh sách hóa đơn nghiệp vụ.</p>
        </div>
        @include('Invoices::partials.dashboard-return-link')
    </header>
    <livewire:invoices.gdt-excel-sandbox />
</div>
@endsection
