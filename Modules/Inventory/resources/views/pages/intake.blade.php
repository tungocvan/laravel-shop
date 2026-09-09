@extends('Admin::layouts.master')

@section('title', 'Chuẩn hóa nhập kho')

@section('content')
<div class="mx-auto max-w-[1600px] space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div><h1 class="text-2xl font-bold text-slate-900">Chuẩn hóa dữ liệu nhập kho</h1><p class="mt-1 text-sm text-slate-500">Bulk intake hóa đơn mua vào, staging dữ liệu thô và chuẩn hóa hàng hóa trước khi publish vào kho.</p></div>
        <div class="flex gap-2"><a href="{{ route('admin.inventory.invoice-inbox') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Invoice Inbox</a><a href="{{ route('admin.inventory.dashboard') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">← Dashboard</a></div>
    </div>
    <livewire:inventory::receiving-intake-workspace />
</div>
@endsection
