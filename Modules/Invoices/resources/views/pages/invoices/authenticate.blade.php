@extends('Admin::layouts.master')

@section('title', 'Kết nối GDT')

@section('content')
    <div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Kết nối Cổng hóa đơn điện tử</h1>
            <p class="mt-1 text-sm text-gray-500">Xác thực GDT và tra cứu nhanh hóa đơn bán ra hoặc mua vào.</p>
        </div>

        @livewire('invoices.gdt-login')
        @livewire('invoices.gdt-invoice')
    </div>
@endsection
