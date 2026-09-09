@extends('Admin::layouts.master')

@section('title', 'Hóa đơn chờ nhập kho')

@section('content')
<div class="space-y-6">
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Invoices → Inventory</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Hóa đơn chờ nhập kho</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Đồng bộ chi tiết hóa đơn mua vào qua contract V1, review matching và chỉ tạo phiếu nhập DRAFT. Không tự động cộng tồn.</p>
        </div>
        <a href="{{ route('admin.inventory.dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:border-indigo-300">← Inventory Dashboard</a>
    </header>

    <livewire:inventory.invoice-inbox-workspace />
</div>
@endsection
