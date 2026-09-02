@extends('Admin::layouts.master')
@section('title', 'Giao diện đăng nhập')
@section('content')
    <div class="space-y-6">
        @include('System::partials.dashboard-return-link')

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Giao diện đăng nhập</h1>
                <p class="mt-1 text-sm text-gray-500">Quản lý theme và nhận diện cho cổng đăng nhập Admin và Client / PWA.</p>
            </div>
            <a href="{{ route('admin.system.settings.index') }}"
               class="inline-flex h-10 items-center justify-center rounded-xl border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                Cấu hình hệ thống
            </a>
        </div>

        @livewire('system.settings.partials.login-theme')
    </div>
@endsection
