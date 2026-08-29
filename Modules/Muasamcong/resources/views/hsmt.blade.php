@extends('Admin::layouts.master')

@section('title', 'Tra cứu hồ sơ mời thầu')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">Tra cứu hồ sơ mời thầu</h1>
                <p class="mt-1 text-sm text-gray-500">Tìm thông báo mời thầu theo từ khóa, khoảng ngày và xuất các dòng đã chọn.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @include('Muasamcong::partials.dashboard-return-link')
            </div>
        </div>

        @livewire('muasamcong.search-hsmt')
    </div>
@endsection
