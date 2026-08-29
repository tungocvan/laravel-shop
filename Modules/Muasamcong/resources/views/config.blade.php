@extends('Admin::layouts.master')

@section('title', 'Cấu hình Mua sắm công')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">Cấu hình Mua sắm công</h1>
                <p class="mt-1 text-sm text-gray-500">
                    Quản lý endpoint, thời gian chờ và thông tin phiên kết nối với Hệ thống mạng đấu thầu quốc gia.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @include('Muasamcong::partials.dashboard-return-link')
            </div>
        </div>

        @livewire('muasamcong.config-manager')
    </div>
@endsection
