@extends('Admin::layouts.master')
@section('title', 'Tạo bảng giá')
@section('content')
<div class="mx-auto w-full max-w-[1600px] space-y-6 px-4 py-6 sm:px-6 lg:px-8">
<a href="{{ route('admin.pharma.price-lists.index') }}" class="text-sm font-semibold text-gray-600">← Bảng giá thuốc</a>
<div><h1 class="text-2xl font-bold tracking-tight text-gray-900">Tạo bảng giá thuốc</h1><p class="mt-1 text-sm text-gray-500">Medicine Master là nguồn sản phẩm duy nhất. Excel chỉ còn là output/export.</p></div>
@livewire('pharma.price-list.create')
</div>
@endsection
