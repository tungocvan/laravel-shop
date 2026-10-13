@extends('Admin::layouts.master')
@section('title', 'Quản trị danh mục sản phẩm')
@section('content')
<div class="mx-auto max-w-4xl space-y-6 p-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Quản trị danh mục sản phẩm</h1>
        <p class="mt-2 text-sm text-gray-500">Cấu hình bộ lọc của trang /product trong template Pharma. Không thay đổi bộ lọc Default.</p>
    </div>
    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-3 text-green-800">{{ session('success') }}</div>
    @endif
    <form method="POST" action="{{ route('admin.product.settings.update') }}" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        <h2 class="text-lg font-semibold">Bộ lọc tra cứu Pharma</h2>
        <p class="mb-5 mt-1 text-sm text-gray-500">Bật hoặc tắt các bộ lọc hiển thị trên Website.</p>
        <div class="space-y-4">
            @foreach($filters as $key => $label)
                <label class="flex cursor-pointer items-center justify-between gap-4 rounded-lg border border-gray-200 px-4 py-3">
                    <span class="text-sm font-medium text-gray-800">{{ $label }}</span>
                    <input type="checkbox" name="filters[{{ $key }}]" value="1" @checked(!empty($visibility[$key])) class="h-5 w-5 rounded border-gray-300 text-emerald-700">
                </label>
            @endforeach
        </div>
        <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('product.list') }}" target="_blank" class="text-sm font-semibold text-emerald-700">Xem trang sản phẩm ↗</a>
            <button type="submit" class="rounded-lg bg-emerald-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Lưu cấu hình</button>
        </div>
    </form>
</div>
@endsection
