@extends('ClientPortal::layouts.application')

@section('title', 'Chi tiết hóa đơn')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Chi tiết hóa đơn')
@section('app-dashboard-route', route('client.invoices.dashboard'))

@section('content')
@php
    $pdfLabels = ['available' => 'PDF sẵn sàng', 'missing' => 'Chưa có PDF', 'error' => 'PDF lỗi', 'unsupported' => 'Không hỗ trợ PDF'];
@endphp

<div class="mx-auto max-w-5xl space-y-5">
    <div class="flex items-center justify-between gap-3">
        <a href="{{ $returnTo }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm">Quay lại</a>
        <span @class([
            'rounded-full px-3 py-1.5 text-xs font-bold',
            'bg-sky-50 text-sky-700' => $invoice->invoice_type === 'purchase',
            'bg-emerald-50 text-emerald-700' => $invoice->invoice_type === 'sold',
        ])>{{ $invoice->invoice_type === 'purchase' ? 'Mua vào' : 'Bán ra' }}</span>
    </div>

    <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 p-5 sm:p-7">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Đối tác</p>
            <h1 class="mt-2 text-2xl font-black tracking-tight text-slate-950 sm:text-3xl">{{ $invoice->name ?: 'Đối tác chưa xác định' }}</h1>
            <p class="mt-2 text-sm font-semibold text-slate-500">MST {{ $invoice->tax_code ?: '—' }}</p>
        </div>

        <div class="grid gap-px bg-slate-100 sm:grid-cols-2 lg:grid-cols-4">
            <div class="bg-white p-5"><p class="text-xs font-bold uppercase tracking-wider text-slate-400">Số hóa đơn</p><p class="mt-2 font-black text-slate-950">{{ $invoice->invoice_number ?: '—' }}</p></div>
            <div class="bg-white p-5"><p class="text-xs font-bold uppercase tracking-wider text-slate-400">Ký hiệu</p><p class="mt-2 font-black text-slate-950">{{ $invoice->symbol ?: '—' }}</p></div>
            <div class="bg-white p-5"><p class="text-xs font-bold uppercase tracking-wider text-slate-400">Ngày lập</p><p class="mt-2 font-black text-slate-950">{{ optional($invoice->issued_date)->format('d/m/Y') ?: '—' }}</p></div>
            <div class="bg-white p-5"><p class="text-xs font-bold uppercase tracking-wider text-slate-400">Thuế suất</p><p class="mt-2 font-black text-slate-950">{{ $invoice->tax_rate !== null ? rtrim(rtrim((string) $invoice->tax_rate, '0'), '.').'%' : '—' }}</p></div>
        </div>
    </section>

    <section class="grid gap-4 lg:grid-cols-[1.3fr_0.7fr]">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="text-lg font-black text-slate-950">Giá trị hóa đơn</h2>
            <dl class="mt-5 space-y-4">
                <div class="flex items-center justify-between gap-4"><dt class="text-sm text-slate-500">Trước VAT</dt><dd class="text-right font-bold text-slate-900">{{ number_format((float) $invoice->amount_before_vat, 0, ',', '.') }} ₫</dd></div>
                <div class="flex items-center justify-between gap-4"><dt class="text-sm text-slate-500">VAT</dt><dd class="text-right font-bold text-slate-900">{{ number_format((float) $invoice->vat_amount, 0, ',', '.') }} ₫</dd></div>
                <div class="border-t border-slate-200 pt-4 flex items-end justify-between gap-4"><dt class="font-bold text-slate-700">Tổng cộng</dt><dd class="text-right text-2xl font-black tracking-tight text-slate-950">{{ number_format((float) $invoice->total_amount, 0, ',', '.') }} ₫</dd></div>
            </dl>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Tài liệu</p>
            <div class="mt-4 flex items-center justify-between gap-3 rounded-2xl bg-slate-50 p-4">
                <div><p class="font-bold text-slate-950">Hóa đơn PDF</p><p class="mt-1 text-xs text-slate-500">{{ $pdfLabels[$pdfStatus] ?? 'Chưa xác định' }}</p></div>
                <span @class([
                    'h-3 w-3 rounded-full',
                    'bg-emerald-500' => $pdfStatus === 'available',
                    'bg-amber-500' => $pdfStatus === 'missing',
                    'bg-rose-500' => $pdfStatus === 'error',
                    'bg-slate-400' => $pdfStatus === 'unsupported',
                ])></span>
            </div>
            @if($pdfStatus === 'available' && auth('web')->user()?->can('client.invoices.pdf.download'))
                <a href="{{ route('client.invoices.pdf', $invoice) }}" class="mt-4 flex min-h-12 w-full items-center justify-center rounded-2xl bg-slate-950 px-4 text-sm font-bold text-white">Tải PDF</a>
            @elseif($pdfStatus === 'missing')
                <p class="mt-4 rounded-2xl bg-amber-50 p-4 text-sm leading-6 text-amber-900">PDF chưa có trên server. Việc tải mới từ nhà cung cấp được tách khỏi màn hình xem để tránh thao tác ngoài ý muốn trên mobile.</p>
            @endif
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <h2 class="text-lg font-black text-slate-950">Thông tin đối tác</h2>
        <dl class="mt-5 grid gap-4 sm:grid-cols-2">
            <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Địa chỉ</dt><dd class="mt-2 text-sm leading-6 text-slate-700">{{ $invoice->address ?: '—' }}</dd></div>
            <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Mã tra cứu</dt><dd class="mt-2 break-all text-sm font-semibold text-slate-700">{{ $invoice->lookup_code ?: '—' }}</dd></div>
            <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Email</dt><dd class="mt-2 break-all text-sm text-slate-700">{{ $invoice->email ?: '—' }}</dd></div>
            <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Điện thoại</dt><dd class="mt-2 text-sm text-slate-700">{{ $invoice->phone ?: '—' }}</dd></div>
        </dl>
    </section>
</div>
@endsection
