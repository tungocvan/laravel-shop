@extends('Admin::layouts.master')

@section('title', 'Cấu hình Mua sắm công')

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Cấu hình Mua sắm công</h1>
            <p class="mt-1 text-sm text-gray-500">
                Quản lý endpoint, thời gian chờ và thông tin phiên kết nối với Hệ thống mạng đấu thầu quốc gia.
            </p>
        </div>

        @livewire('muasamcong.config-manager')
    </div>
@endsection
