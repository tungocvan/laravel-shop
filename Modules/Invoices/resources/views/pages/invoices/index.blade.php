@extends('Admin::layouts.master')

@section('title', 'Danh sách hóa đơn')

@section('content')
    <div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">Danh sách hóa đơn</h1>
                <p class="mt-1 text-sm text-gray-500">Lọc, thống kê, xuất Excel và tải PDF hóa đơn.</p>
            </div>
            <a href="{{ route('admin.invoices.reports.partners') }}" class="inline-flex h-11 items-center rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                Báo cáo đối tác
            </a>
        </div>

        @livewire('invoices.hoadon-list')
    </div>
@endsection
