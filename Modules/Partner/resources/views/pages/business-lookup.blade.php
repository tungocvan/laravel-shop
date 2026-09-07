@extends('Admin::layouts.master')

@section('title', 'Tra cứu doanh nghiệp')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-indigo-600">Partner Master Hub</p>
                <h1 class="text-2xl font-bold text-gray-900">Tra cứu doanh nghiệp</h1>
                <p class="mt-1 text-sm text-gray-500">Tra cứu nguồn tham khảo, kiểm tra xung đột và chỉ đồng bộ sau khi bạn xác nhận.</p>
            </div>
            <a href="{{ route('admin.partners.dashboard') }}" class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Quay về Dashboard</a>
        </div>

        <livewire:partner::business-lookup />
    </div>
@endsection
