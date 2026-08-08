@extends('Admin::layouts.master')

@section('title', 'Danh sách hóa đơn')

@section('content')
    <div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Danh sách hóa đơn</h1>
            <p class="mt-1 text-sm text-gray-500">Lọc, thống kê, xuất Excel và tải PDF hóa đơn.</p>
        </div>

        @livewire('invoices.hoadon-list')
    </div>
@endsection
