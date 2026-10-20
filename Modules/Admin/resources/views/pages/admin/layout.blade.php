@extends('Admin::layouts.master')

@section('title', 'Tổng quan giao diện Admin')

@section('content')
    <div class="mb-4">
        @if (\Illuminate\Support\Facades\Route::has('admin.system.dashboard') && auth('admin')->user()?->can('system.manage'))
            <a href="{{ route('admin.system.dashboard') }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400">
                <span aria-hidden="true">←</span> Quay về Dashboard
            </a>
        @endif
    </div>
    <x-admin::page-header
        title="Tổng quan giao diện Admin"
        description="Theo dõi trạng thái và truy cập nhanh từng khu vực cấu hình UI Admin."
        eyebrow="Không gian giao diện"
    />

    <x-admin::content-section>
        @livewire('admin.settings.admin-layout-dashboard')
    </x-admin::content-section>
@endsection
