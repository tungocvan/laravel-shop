@extends('Admin::layouts.master')
@section('title', 'System Administration Hub')
@section('content')
<div class="mx-auto max-w-7xl space-y-6 px-1 py-2 sm:px-3">
    @include('System::partials.dashboard-return-link')

    <header class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
        <p class="text-xs font-bold uppercase tracking-[0.14em] text-indigo-600">System Administration Hub</p>
        <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Trung tâm quản trị hệ thống</h1>
        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
            Truy cập nhanh các workspace vận hành, dữ liệu, modules, giao diện và công cụ kỹ thuật.
            Mỗi chức năng vẫn do module sở hữu quản lý và kiểm soát quyền truy cập riêng.
        </p>
        <p class="mt-2 text-xs text-slate-500">
            Themes, hình ảnh và cấu hình chung không còn là tab riêng của System.
        </p>
    </header>

    @forelse($groups as $group => $workspaces)
        <section aria-label="{{ $group }}" class="space-y-3">
            <div class="flex items-center gap-3">
                <h2 class="text-base font-bold text-slate-900 sm:text-lg">{{ $group }}</h2>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">{{ count($workspaces) }}</span>
            </div>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($workspaces as $workspace)
                    <a href="{{ route($workspace['route'], $workspace['params'] ?? []) }}"
                       class="group flex min-h-36 flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="text-base font-semibold text-slate-950 transition group-hover:text-indigo-700">{{ $workspace['label'] }}</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $workspace['description'] }}</p>
                            </div>
                            <span aria-hidden="true" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-indigo-600">
                                @switch($workspace['icon'])
                                    @case('database') <i class="fas fa-database"></i> @break
                                    @case('blocks') <i class="fas fa-cubes"></i> @break
                                    @case('layout') <i class="fas fa-columns"></i> @break
                                    @case('terminal') <i class="fas fa-terminal"></i> @break
                                    @case('code') <i class="fas fa-code"></i> @break
                                    @case('palette') <i class="fas fa-palette"></i> @break
                                    @case('settings') <i class="fas fa-sliders-h"></i> @break
                                    @default <i class="fas fa-server"></i>
                                @endswitch
                            </span>
                        </div>
                        <span class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-indigo-700">
                            Mở workspace <span aria-hidden="true">→</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @empty
        <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-600">
            Không có workspace khả dụng với quyền hiện tại.
        </div>
    @endforelse

    <p class="text-xs leading-5 text-slate-500">
        System Hub chỉ điều hướng đến chức năng hiện hữu. Admin Layout vẫn thuộc module Admin;
        các thao tác database, script và cấu hình môi trường tiếp tục được bảo vệ tại route và component đích.
    </p>
</div>
@endsection
