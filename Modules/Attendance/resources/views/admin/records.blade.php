@extends('Admin::layouts.master')

@section('title', 'Danh sách chấm công')

@section('content')
    <div class="space-y-6">
        <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Attendance</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Danh sách chấm công</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Tra cứu, lọc và xử lý các bản ghi chấm công theo đúng quyền được cấp.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.attendance.dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:border-indigo-300 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">Về Dashboard chấm công</a>
                <a href="{{ route('admin.dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:border-indigo-300 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">Admin Dashboard</a>
            </div>
        </header>

        <livewire:attendance.admin-records-table />
    </div>
@endsection
