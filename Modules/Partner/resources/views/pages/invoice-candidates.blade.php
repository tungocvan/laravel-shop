@extends('Admin::layouts.master')

@section('title', 'Đồng bộ Partner từ Invoices')

@section('content')
    <div class="space-y-6 px-4 py-5 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm font-medium text-indigo-600">Partner Master Hub</p>
                <h1 class="text-2xl font-bold text-gray-900">Đồng bộ Partner từ Invoices</h1>
                <p class="mt-1 max-w-3xl text-sm text-gray-500">Review các khách hàng/nhà cung cấp được phát hiện từ hóa đơn. Không có thao tác nào tự ghi đè Partner master data.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.partners.dashboard') }}" class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Về Partner Dashboard</a>
                <a href="{{ route('admin.partners.index') }}" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Quản lý Partner</a>
            </div>
        </div>

        @livewire('partner.invoice-candidate-review')
    </div>
@endsection
