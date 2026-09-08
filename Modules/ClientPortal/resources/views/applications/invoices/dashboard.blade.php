@extends('ClientPortal::layouts.application')

@section('title', $applicationPresentation['name'] ?? $application['name'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Không gian hóa đơn · '.$period)
@section('app-dashboard-route', route('client.invoices.dashboard'))

@section('content')
<div class="space-y-6">
    <section class="overflow-hidden rounded-[2rem] bg-slate-950 px-5 py-6 text-white shadow-sm sm:px-7 sm:py-8">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-slate-200">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    Tháng {{ $period }}
                </div>
                <h1 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Hóa đơn của bạn</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300 sm:text-base">Theo dõi nhanh hóa đơn mua vào, bán ra và tình trạng PDF trước khi đi sâu vào chi tiết.</p>
            </div>
            <a href="{{ route('client.invoices.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-2xl bg-white px-5 py-3 text-sm font-bold text-slate-950 shadow-sm transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-white/70">
                Mở danh sách
            </a>
        </div>
    </section>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('client.invoices.index', ['invoice_type' => 'purchase']) }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Mua vào</p>
                    <p class="mt-2 text-3xl font-black tracking-tight text-slate-950">{{ number_format($stats['purchase_count'] ?? 0) }}</p>
                </div>
                <span class="rounded-2xl bg-sky-50 px-3 py-2 text-xs font-bold text-sky-700">Hóa đơn</span>
            </div>
            <p class="mt-4 text-lg font-bold text-slate-900">{{ number_format((float) ($stats['purchase_amount'] ?? 0), 0, ',', '.') }} ₫</p>
            <p class="mt-1 text-sm text-slate-500">Tổng giá trị trong tháng</p>
        </a>

        <a href="{{ route('client.invoices.index', ['invoice_type' => 'sold']) }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Bán ra</p>
                    <p class="mt-2 text-3xl font-black tracking-tight text-slate-950">{{ number_format($stats['sold_count'] ?? 0) }}</p>
                </div>
                <span class="rounded-2xl bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700">Hóa đơn</span>
            </div>
            <p class="mt-4 text-lg font-bold text-slate-900">{{ number_format((float) ($stats['sold_amount'] ?? 0), 0, ',', '.') }} ₫</p>
            <p class="mt-1 text-sm text-slate-500">Tổng giá trị trong tháng</p>
        </a>

        <a href="{{ route('client.invoices.index', ['pdf_status' => 'missing']) }}" class="rounded-3xl border border-amber-200 bg-amber-50/60 p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-amber-700">PDF chưa có</p>
            <p class="mt-2 text-3xl font-black tracking-tight text-slate-950">{{ number_format($pdfMissing) }}</p>
            <p class="mt-3 text-sm leading-6 text-slate-600">Cần bổ sung tài liệu để hồ sơ hóa đơn đầy đủ hơn.</p>
        </a>

        <a href="{{ route('client.invoices.index', ['pdf_status' => 'error']) }}" class="rounded-3xl border border-rose-200 bg-rose-50/60 p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-rose-700">PDF lỗi</p>
            <p class="mt-2 text-3xl font-black tracking-tight text-slate-950">{{ number_format($pdfErrors) }}</p>
            <p class="mt-3 text-sm leading-6 text-slate-600">Ưu tiên kiểm tra các hóa đơn chưa tải tài liệu thành công.</p>
        </a>
    </section>

    <section class="grid gap-4 lg:grid-cols-[1.4fr_1fr]">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-950">Tóm tắt tháng</h2>
                    <p class="mt-1 text-sm text-slate-500">Tổng {{ number_format($stats['count'] ?? 0) }} hóa đơn đã ghi nhận.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">VAT {{ number_format((float) ($stats['vat_amount'] ?? 0), 0, ',', '.') }} ₫</span>
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
                <a href="{{ route('client.invoices.index') }}" class="flex min-h-14 items-center justify-between rounded-2xl border border-slate-200 px-4 py-3 font-semibold text-slate-800 hover:bg-slate-50">
                    <span>Tra cứu hóa đơn</span><span aria-hidden="true">→</span>
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
