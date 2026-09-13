@extends('Admin::layouts.master')
@section('title', 'Cấu hình hệ thống')
@section('content')
    <div class="space-y-6">
        @include('System::partials.dashboard-return-link')

        <header class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-indigo-600">Configuration Hub</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-950">Cấu hình hệ thống</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                Các nhóm cấu hình trùng lặp đã được gom về đúng workspace sở hữu. Themes, hình ảnh và cấu hình chung không còn là tab riêng của System.
            </p>
        </header>

        <div class="grid gap-4 lg:grid-cols-2">
            <a href="{{ route('admin.system.settings.login-theme') }}"
               class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-indigo-300 hover:shadow-md">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Quản trị truy cập</p>
                <h2 class="mt-2 text-lg font-bold text-slate-950 group-hover:text-indigo-700">Giao diện & Đăng nhập</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Branding đăng nhập, logo/ảnh nền, landing page Admin và điều hướng mặc định.</p>
                <span class="mt-4 inline-flex text-sm font-semibold text-indigo-700">Mở workspace →</span>
            </a>

            @if ((bool) auth('admin')->user()?->can('system.env.view'))
                <a href="{{ route('admin.system.settings.env') }}"
                   class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-indigo-300 hover:shadow-md">
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Cấu hình hạ tầng</p>
                    <h2 class="mt-2 text-lg font-bold text-slate-950 group-hover:text-indigo-700">Môi trường & Tích hợp</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Database, Email, Runtime & Bridge, Storage / Cloud, Web & Analytics và thanh toán.</p>
                    <span class="mt-4 inline-flex text-sm font-semibold text-indigo-700">Mở workspace →</span>
                </a>
            @endif
        </div>

        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 text-sm leading-6 text-blue-900">
            <span class="font-semibold">Ownership mới:</span>
            Runtime/Bridge và Web/Analytics nằm trong Môi trường & Tích hợp; giao diện và điều hướng đăng nhập nằm trong Giao diện & Đăng nhập. Các component legacy chưa bị xóa trong phase này để tránh làm mất dữ liệu hoặc phá caller cũ.
        </div>
    </div>
@endsection
