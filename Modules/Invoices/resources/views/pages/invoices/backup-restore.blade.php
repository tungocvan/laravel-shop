@extends('Admin::layouts.master')

@section('title', 'Backup & Restore hóa đơn')

@section('content')
    <div class="space-y-6">
        <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Invoices</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Backup & Restore Module</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Bảo vệ dữ liệu nghiệp vụ Invoices bằng snapshot có checksum, Restore Readiness Gate, Safety Backup và hậu kiểm sau khôi phục.</p>
            </div>
            <a href="{{ route('admin.invoices.dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Quay về Dashboard</a>
        </header>

        <livewire:invoices.module-backup-restore />
    </div>
@endsection
