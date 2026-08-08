@extends('Admin::layouts.master')

@section('content')
    <h1 class="text-2xl font-bold mb-6 text-gray-800">Danh sách sản phẩm</h1>
    @livewire('admin.products.product-table')
@endsection
