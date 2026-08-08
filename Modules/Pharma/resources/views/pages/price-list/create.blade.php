@extends('Admin::layouts.master')

@section('title', 'Tạo bảng giá')

@section('content')
    <div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Tạo bảng giá Excel</h1>
            <p class="mt-1 text-sm text-gray-500">
                Chọn sản phẩm và cột cần xuất từ bảng giá tổng hợp của INAFO.
            </p>
        </div>

        @livewire('pharma.price-list.create')
    </div>
@endsection
