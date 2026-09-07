@extends('Admin::layouts.master')

@section('title', 'Partner Dashboard')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm font-medium text-indigo-600">Partner Master Hub</p>
                <h1 class="text-2xl font-bold text-gray-900">Dashboard Partner</h1>
                <p class="mt-1 text-sm text-gray-500">Tổng quan đối tác, chất lượng dữ liệu và nguồn dữ liệu bên ngoài.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.partners.lookup') }}" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Tra cứu doanh nghiệp</a>
                <a href="{{ route('admin.partners.index') }}" class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Quản lý Partner</a>
            </div>
        </div>

        @livewire('partner.dashboard')
    </div>
@endsection
