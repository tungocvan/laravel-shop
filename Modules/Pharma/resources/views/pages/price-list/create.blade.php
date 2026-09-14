@extends('Admin::layouts.master')
@section('title', 'Tạo bảng giá')
@section('content')
<div class="w-full space-y-5 px-3 py-5 sm:px-5 lg:px-6 2xl:px-8">
<div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <a href="{{ route('admin.pharma.price-lists.index') }}" class="text-sm font-semibold text-slate-500 hover:text-indigo-700">← Bảng giá thuốc</a>
        <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Tạo bảng giá thuốc</h1>
        <p class="mt-1 text-sm text-slate-500">Workspace thiết lập giá từ Medicine Master, tối ưu cho danh sách SKU và chính sách giá khách hàng.</p>
    </div>
    <div class="hidden rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-500 shadow-sm lg:block">Medicine Master → Price List → Customer / Global</div>
</div>
@livewire('pharma.price-list.create')
</div>
@endsection
