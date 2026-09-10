@extends('Admin::layouts.master')

@section('title', 'Dữ liệu nguồn GDT')

@section('content')
<div class="mx-auto w-full max-w-[1600px] space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <header class="border-b border-slate-200 pb-6">
        <a href="{{ route('admin.invoices.dashboard') }}" class="inline-flex min-h-10 items-center gap-1 rounded-lg text-sm font-semibold text-slate-600 transition hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">← Quay về Dashboard</a>

        <div class="mt-3 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Invoices · Nguồn canonical</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Dữ liệu nguồn GDT</h1>
                <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600">Quản trị dữ liệu canonical đã lấy và lưu từ GDT, gồm header, chi tiết nguồn và phân loại nghiệp vụ. Màn hình này chỉ đọc dữ liệu nguồn đã lưu và không phát sinh yêu cầu tới GDT.</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                <a href="{{ route('admin.invoices.hoadon') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">Đồng bộ GDT</a>
                <a href="{{ route('admin.invoices.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">Danh sách hóa đơn</a>
            </div>
        </div>
    </header>

    <livewire:invoices.source-data-manager />
</div>
@endsection
