@extends('Admin::layouts.master')

@section('title', 'Dữ liệu nguồn hóa đơn')

@section('content')
<div class="mx-auto w-full max-w-[1600px] space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Invoices · Canonical source</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Dữ liệu nguồn GDT đã lưu</h1>
            <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600">Quản trị RAW header/detail đã lấy một lần từ GDT và bổ sung phân loại nghiệp vụ. Màn hình này không gọi GDT; mọi đồng bộ nguồn chỉ thực hiện tại trang Đồng bộ hóa đơn.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.invoices.hoadon') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Đồng bộ GDT</a>
            <a href="{{ route('admin.invoices.dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">← Dashboard</a>
        </div>
    </header>

    <livewire:invoices.source-data-manager />
</div>
@endsection
