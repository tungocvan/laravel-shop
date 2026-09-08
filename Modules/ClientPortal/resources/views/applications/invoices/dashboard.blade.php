@extends('ClientPortal::layouts.application')

@section('title', $applicationPresentation['name'] ?? $application['name'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Không gian hóa đơn · '.$period)
@section('app-dashboard-route', route('client.invoices.dashboard'))

@section('content')
@php
    $periodListParams = ['year' => $selectedYear, 'month' => $selectedMonth ?? (int) now()->format('m')];
    $scopeLabel = $periodScope === 'year' ? 'trong năm' : 'trong tháng';
@endphp

<div class="space-y-6">
    <section class="overflow-hidden rounded-[2rem] bg-slate-950 px-5 py-6 text-white shadow-sm sm:px-7 sm:py-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="min-w-0">
                <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-slate-200">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    {{ $period }}
                </div>
                <h1 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Hóa đơn của bạn</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300 sm:text-base">Mặc định hiển thị tổng quan cả năm. Chọn một tháng để xem nhanh KPI, VAT và tình trạng PDF của riêng tháng đó.</p>
            </div>

            <form method="GET" action="{{ route('client.invoices.dashboard') }}" class="grid w-full gap-3 rounded-3xl bg-white/10 p-3 sm:grid-cols-[1fr_1fr_auto] lg:w-auto lg:min-w-[31rem]">
                <label class="text-xs font-bold uppercase tracking-wider text-slate-300">
                    Năm
                    <select name="year" class="mt-2 min-h-12 w-full rounded-2xl border border-white/10 bg-white px-3 text-sm font-bold text-slate-950">
                        @foreach($years as $year)
                            <option value="{{ $year }}" @selected($selectedYear === $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-xs font-bold uppercase tracking-wider text-slate-300">
                    Tháng
                    <select name="month" class="mt-2 min-h-12 w-full rounded-2xl border border-white/10 bg-white px-3 text-sm font-bold text-slate-950">
                        <option value="" @selected($selectedMonth === null)>Cả năm</option>
                        @for($month = 1; $month <= 12; $month++)
                            <option value="{{ $month }}" @selected($selectedMonth === $month)>Tháng {{ $month }}</option>
                        @endfor
                    </select>
                </label>
                <button type="submit" class="min-h-12 self-end rounded-2xl bg-white px-5 py-3 text-sm font-black text-slate-950 shadow-sm transition hover:bg-slate-100">Xem</button>
            </form>
        </div>
    </section>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Mua vào</p>
                    <p class="mt-2 text-3xl font-black tracking-tight text-slate-950">{{ number_format($stats['purchase_count'] ?? 0) }}</p>
                </div>
                <span class="rounded-2xl bg-sky-50 px-3 py-2 text-xs font-bold text-sky-700">Hóa đơn</span>
            </div>
            <p class="mt-4 text-lg font-bold text-slate-900">{{ number_format((float) ($stats['purchase_amount'] ?? 0), 0, ',', '.') }} ₫</p>
            <p class="mt-1 text-sm text-slate-500">Tổng giá trị {{ $scopeLabel }}</p>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Bán ra</p>
                    <p class="mt-2 text-3xl font-black tracking-tight text-slate-950">{{ number_format($stats['sold_count'] ?? 0) }}</p>
                </div>
                <span class="rounded-2xl bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700">Hóa đơn</span>
            </div>
            <p class="mt-4 text-lg font-bold text-slate-900">{{ number_format((float) ($stats['sold_amount'] ?? 0), 0, ',', '.') }} ₫</p>
            <p class="mt-1 text-sm text-slate-500">Tổng giá trị {{ $scopeLabel }}</p>
        </div>

        <div class="rounded-3xl border border-amber-200 bg-amber-50/60 p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-amber-700">PDF chưa có</p>
            <p class="mt-2 text-3xl font-black tracking-tight text-slate-950">{{ number_format($pdfMissing) }}</p>
            <p class="mt-3 text-sm leading-6 text-slate-600">Số hóa đơn {{ $scopeLabel }} chưa có PDF hoàn chỉnh.</p>
        </div>

        <div class="rounded-3xl border border-rose-200 bg-rose-50/60 p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-rose-700">PDF lỗi</p>
            <p class="mt-2 text-3xl font-black tracking-tight text-slate-950">{{ number_format($pdfErrors) }}</p>
            <p class="mt-3 text-sm leading-6 text-slate-600">Số hóa đơn {{ $scopeLabel }} cần kiểm tra lại tài liệu.</p>
        </div>
    </section>

    <section class="grid gap-4 lg:grid-cols-[1.4fr_1fr]">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-950">Tóm tắt {{ $periodScope === 'year' ? 'năm' : 'tháng' }}</h2>
                    <p class="mt-1 text-sm text-slate-500">Tổng {{ number_format($stats['count'] ?? 0) }} hóa đơn đã ghi nhận trong {{ mb_strtolower($period) }}.</p>
                </div>
                <span class="w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">VAT {{ number_format((float) ($stats['vat_amount'] ?? 0), 0, ',', '.') }} ₫</span>
            </div>
            <div class="mt-6 grid gap-3 sm:grid-cols-3">
                @foreach([5, 8, 10] as $rate)
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Thuế {{ $rate }}%</p>
                        <p class="mt-2 text-base font-bold text-slate-900">{{ number_format((float) ($stats['by_tax_rate'][$rate] ?? 0), 0, ',', '.') }} ₫</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Thao tác nhanh</p>
            <div class="mt-4 grid gap-3">
                <a href="{{ route('client.invoices.index', $periodListParams) }}" class="flex min-h-14 items-center justify-between rounded-2xl border border-slate-200 px-4 py-3 font-semibold text-slate-800 hover:bg-slate-50">
                    <span>{{ $selectedMonth === null ? 'Mở danh sách tháng hiện tại' : 'Mở danh sách tháng đã chọn' }}</span><span aria-hidden="true">→</span>
                </a>
                @if(auth('web')->user()?->can('client.invoices.sync'))
                    <a href="{{ route('client.invoices.sync') }}" class="flex min-h-14 items-center justify-between rounded-2xl border border-slate-200 px-4 py-3 font-semibold text-slate-800 hover:bg-slate-50">
                        <span>Đồng bộ từ GDT</span><span aria-hidden="true">→</span>
                    </a>
                @endif
            </div>
        </div>
    </section>
</div>
@endsection
