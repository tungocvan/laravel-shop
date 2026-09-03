@extends('Admin::layouts.master')
@section('title', 'Đăng nhập & Điều hướng')
@section('content')
    <div class="space-y-6">
        @include('System::partials.dashboard-return-link')

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Đăng nhập & Điều hướng</h1>
                <p class="mt-1 text-sm text-gray-500">Chọn trang mặc định được mở sau khi Admin đăng nhập thành công.</p>
            </div>
            <a href="{{ route('admin.system.settings.index') }}"
               class="inline-flex h-10 items-center justify-center rounded-xl border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                Cấu hình hệ thống
            </a>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            @livewire('system.settings.partials.login-redirect')
        </div>
    </div>
@endsection