@extends('Admin::layouts.master')

@section('title', 'Báo cáo tổng hợp đối tác')

@section('content')
    <div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">Báo cáo tổng hợp đối tác</h1>
                <p class="mt-1 text-sm text-gray-500">Phân tích doanh thu bán ra, giá trị mua vào, VAT và chênh lệch theo từng công ty.</p>
            </div>
            @include('Invoices::partials.dashboard-return-link')
        </div>
        @livewire('invoices.partner-report')
    </div>
@endsection
