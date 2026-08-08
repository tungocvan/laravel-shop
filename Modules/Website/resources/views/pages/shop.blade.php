@extends('Website::layouts.frontend')

@section('title', 'Cửa hàng - Tất cả sản phẩm')

@section('content')
    {{-- Nhúng Livewire Component xử lý logic lọc/hiển thị --}}
    @livewire('website.products.product-list')
@endsection
