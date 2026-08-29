@extends('Admin::layouts.master')

@section('title', 'Lịch sử nhà thầu')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Mua sắm công</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Lịch sử nhà thầu</h1>
                <p class="mt-1 text-sm text-gray-500">Ưu tiên dữ liệu đã lưu trên server; chỉ gọi API khi thực hiện tìm kiếm mới.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                @include('Muasamcong::partials.dashboard-return-link')
                <a href="{{ route('muasamcong.contractors.history') }}"
                   class="inline-flex items-center justify-center rounded-xl border border-indigo-200 bg-white px-4 py-2.5 text-sm font-semibold text-indigo-700 shadow-sm hover:bg-indigo-50">
                    Danh sách đã tra cứu
                </a>
                <a href="{{ route('muasamcong.index') }}"
                   class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                    ← Về tra cứu thuốc
                </a>
            </div>
        </div>

        @livewire('muasamcong.queued-contractor-history', [
            'initialSearchId' => isset($contractorSearch) ? $contractorSearch->id : null,
        ])
    </div>
@endsection
