@extends('Admin::layouts.master')

@section('title', 'Đồng bộ hóa đơn')

@section('content')
    <div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Đồng bộ hóa đơn GDT</h1>
            <p class="mt-1 text-sm text-gray-500">Xuất Excel, đưa tác vụ vào queue, quản lý file trung gian và backup dữ liệu hóa đơn.</p>
        </div>

        @livewire('invoices.search-hoadon')
        @livewire('invoices.automatic-backup-panel')
    </div>
@endsection
