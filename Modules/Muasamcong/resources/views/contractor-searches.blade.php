@extends('Admin::layouts.master')

@section('title', 'Danh sách nhà thầu đã tra cứu')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Mua sắm công</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Danh sách nhà thầu đã tra cứu</h1>
                <p class="mt-1 text-sm text-gray-500">Dữ liệu đã lưu trên server được ưu tiên sử dụng lại. Cập nhật mới chạy bằng Queue và hiển thị trạng thái trực tiếp.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @include('Muasamcong::partials.dashboard-return-link')
                <a href="{{ route('muasamcong.contractors') }}"
                   class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                    + Tra cứu nhà thầu
                </a>
            </div>
        </div>

        @livewire('muasamcong.contractor-search-list')
    </div>
@endsection
