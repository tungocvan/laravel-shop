@extends('Admin::layouts.master')

@section('title', 'Bảng giá thuốc')

@section('content')
<div class="w-full space-y-6 px-3 py-5 sm:px-5 lg:px-6 2xl:px-8">
    @include('Pharma::pages.partials.dashboard-back')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div><h1 class="text-2xl font-bold text-gray-900">Bảng giá thuốc</h1><p class="mt-1 text-sm text-gray-500">Quản lý giá bán chung và bảng giá riêng theo khách hàng từ Danh mục thuốc chuẩn.</p></div>
        <div class="flex flex-wrap items-center gap-2">@livewire('pharma.price-list.export-configurator')<a href="{{ route('admin.pharma.price-lists.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700">+ Tạo bảng giá</a></div>
    </div>
    @livewire('pharma.price-list.index')
</div>
@endsection
