@extends('ClientPortal::layouts.application')

@section('title', $applicationPresentation['name'] ?? $application['name'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Báo cáo quản trị · '.$period)
@section('app-dashboard-route', route('client.invoices.dashboard'))

@section('content')
@php
    $money = fn ($value) => number_format((float) $value, 0, ',', '.').' ₫';
    $periodLabel = $periodScope === 'year' ? 'cả năm' : 'tháng đã chọn';
@endphp

<div class="space-y-6">
    <section class="overflow-hidden rounded-[2rem] bg-slate-950 px-5 py-6 text-white shadow-sm sm:px-7 sm:py-8">
        <div class="grid gap-6 xl:grid-cols-[1fr_auto] xl:items-end">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-slate-200">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                    {{ $period }}
                </div>
                <h1 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Tổng quan kinh doanh</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-300 sm:text-base">Theo dõi doanh thu bán ra, giá trị mua vào, VAT, xu hướng theo tháng và tình trạng tài liệu hóa đơn trên một màn hình.</p>
            </div>

            <form method="GET" action="{{ route('client.invoices.dashboard') }}" class="grid gap-3 rounded-3xl bg-white/10 p-3 sm:grid-cols-2" data-dashboard-period-form>
                <label class="text-xs font-bold uppercase tracking-wider text-slate-300">Năm
                    <select name="year" onchange="this.form.submit()" class="mt-2 min-h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-bold text-slate-950 shadow-sm">
                        @foreach($years as $year)
                            <option value="{{ $year }}" @selected($selectedYear === $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-xs font-bold uppercase tracking-wider text-slate-300">Tháng
                    <select name="month" onchange="this.form.submit()" class="mt-2 min-h-12 w-full rounded-2xl border-0 bg-white px-4 text-sm font-bold text-slate-950 shadow-sm">
                        <option value="" @selected($selectedMonth === null)>Cả năm</option>
                        @for($month = 1; $month <= 12; $month++)
                            <option value="{{ $month }}" @selected($selectedMonth === $month)>Tháng {{ $month }}</option>
                        @endfor
                    </select>
                </label>
            </form>
        </div>
    </section>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Doanh thu bán ra</p>
            <p class="mt-3 text-2xl font-black tracking-tight text-slate-950">{{ $money($stats['sold_amount'] ?? 0) }}</p>
            <div class="mt-3 flex items-center justify-between text-sm text-slate-500"><span>{{ number_format($stats['sold_count'] ?? 0) }} hóa đơn</span><span>{{ $periodLabel }}</span></div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Giá trị mua vào</p>
            <p class="mt-3 text-2xl font-black tracking-tight text-slate-950">{{ $money($stats['purchase_amount'] ?? 0) }}</p>
            <div class="mt-3 flex items-center justify-between text-sm text-slate-500"><span>{{ number_format($stats['purchase_count'] ?? 0) }} hóa đơn</span><span>{{ $periodLabel }}</span></div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Tổng hóa đơn</p>
            <p class="mt-3 text-3xl font-black tracking-tight text-slate-950">{{ number_format($stats['count'] ?? 0) }}</p>
            <p class="mt-3 text-sm text-slate-500">VAT ghi nhận: <strong class="text-slate-800">{{ $money($stats['vat_amount'] ?? 0) }}</strong></p>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Tình trạng tài liệu</p>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">PDF</span>
            </div>
            <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                <div class="rounded-2xl bg-emerald-50 px-2 py-3"><p class="text-xl font-black text-emerald-700">{{ number_format($pdfComplete) }}</p><p class="mt-1 text-[11px] font-bold text-emerald-700">Hoàn chỉnh</p></div>
                <div class="rounded-2xl bg-amber-50 px-2 py-3"><p class="text-xl font-black text-amber-700">{{ number_format($pdfMissing) }}</p><p class="mt-1 text-[11px] font-bold text-amber-700">Chưa có</p></div>
                <div class="rounded-2xl bg-rose-50 px-2 py-3"><p class="text-xl font-black text-rose-700">{{ number_format($pdfErrors) }}</p><p class="mt-1 text-[11px] font-bold text-rose-700">Lỗi</p></div>
            </div>
        </div>
    </section>

    <section class="grid gap-4 xl:grid-cols-[1.5fr_1fr]">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Nhìn nhanh trong năm</p>
                    <h2 class="mt-1 text-xl font-black text-slate-950">Xu hướng 12 tháng · {{ $selectedYear }}</h2>
                </div>
                <div class="flex items-center gap-4 text-xs font-semibold text-slate-500"><span><i class="mr-1 inline-block h-2.5 w-2.5 rounded-full bg-slate-950"></i>Bán ra</span><span><i class="mr-1 inline-block h-2.5 w-2.5 rounded-full bg-slate-300"></i>Mua vào</span></div>
            </div>

            <div class="mt-6 grid grid-cols-6 gap-2 sm:grid-cols-12">
                @foreach($monthlyPerformance as $row)
                    @php
                        $soldHeight = max(3, ((float) $row['sold_total'] / $monthlyMax) * 100);
                        $purchaseHeight = max(3, ((float) $row['purchase_total'] / $monthlyMax) * 100);
                    @endphp
                    <a href="{{ route('client.invoices.dashboard', ['year' => $selectedYear, 'month' => $row['month']]) }}" class="group min-w-0 text-center" title="Tháng {{ $row['month'] }}: bán ra {{ $money($row['sold_total']) }}, mua vào {{ $money($row['purchase_total']) }}">
                        <div class="flex h-36 items-end justify-center gap-1 rounded-2xl bg-slate-50 px-1.5 pb-2 pt-3 transition group-hover:bg-slate-100">
                            <span class="w-2.5 rounded-t-full bg-slate-950" style="height: {{ $soldHeight }}%"></span>
                            <span class="w-2.5 rounded-t-full bg-slate-300" style="height: {{ $purchaseHeight }}%"></span>
                        </div>
                        <p class="mt-2 text-xs font-bold text-slate-500">T{{ $row['month'] }}</p>
                    </a>
                @endforeach
            </div>
            <p class="mt-4 text-xs leading-5 text-slate-400">Chạm vào một tháng để xem KPI riêng tháng đó. Biểu đồ dùng cùng một thang đo cho bán ra và mua vào.</p>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Tháng hiện tại trong năm đang xem</p>
            <h2 class="mt-1 text-xl font-black text-slate-950">{{ $currentMonthLabel }}</h2>
            <div class="mt-5 space-y-4">
                <div class="flex items-end justify-between gap-4 border-b border-slate-100 pb-4"><div><p class="text-sm font-semibold text-slate-500">Doanh thu bán ra</p><p class="mt-1 text-xl font-black text-slate-950">{{ $money($currentMonthStats['sold_amount'] ?? 0) }}</p></div><span class="text-sm font-bold text-slate-500">{{ number_format($currentMonthStats['sold_count'] ?? 0) }} HĐ</span></div>
                <div class="flex items-end justify-between gap-4 border-b border-slate-100 pb-4"><div><p class="text-sm font-semibold text-slate-500">Giá trị mua vào</p><p class="mt-1 text-xl font-black text-slate-950">{{ $money($currentMonthStats['purchase_amount'] ?? 0) }}</p></div><span class="text-sm font-bold text-slate-500">{{ number_format($currentMonthStats['purchase_count'] ?? 0) }} HĐ</span></div>
                <div class="flex items-end justify-between gap-4"><div><p class="text-sm font-semibold text-slate-500">VAT</p><p class="mt-1 text-lg font-black text-slate-950">{{ $money($currentMonthStats['vat_amount'] ?? 0) }}</p></div><span class="text-sm font-bold text-slate-500">{{ number_format($currentMonthStats['count'] ?? 0) }} tổng HĐ</span></div>
            </div>
        </div>
    </section>

    <section class="grid gap-4 xl:grid-cols-[1.35fr_1fr]">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Tổng kết nhiều năm</p>
                    <h2 class="mt-1 text-xl font-black text-slate-950">Tình hình doanh thu qua các năm</h2>
                </div>
                <span class="hidden text-xs font-semibold text-slate-400 sm:block">Tối đa 6 năm gần nhất</span>
            </div>

            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs font-bold uppercase tracking-wider text-slate-400"><tr><th class="pb-3 pr-4">Năm</th><th class="pb-3 pr-4 text-right">Doanh thu bán ra</th><th class="pb-3 pr-4 text-right">Mua vào</th><th class="pb-3 pr-4 text-right">Hóa đơn</th><th class="pb-3 text-right">So năm trước</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($yearlyPerformance as $row)
                            <tr>
                                <td class="py-4 pr-4"><a href="{{ route('client.invoices.dashboard', ['year' => $row['year']]) }}" class="font-black text-slate-950 hover:underline">{{ $row['year'] }}</a></td>
                                <td class="py-4 pr-4 text-right font-bold text-slate-900">{{ $money($row['sold_total'] ?? 0) }}</td>
                                <td class="py-4 pr-4 text-right font-semibold text-slate-600">{{ $money($row['purchase_total'] ?? 0) }}</td>
                                <td class="py-4 pr-4 text-right font-semibold text-slate-600">{{ number_format($row['invoice_count'] ?? 0) }}</td>
                                <td class="py-4 text-right">
                                    @if($row['sold_growth'] === null)
                                        <span class="text-slate-400">—</span>
                                    @else
                                        <span @class(['font-bold', 'text-emerald-700' => $row['sold_growth'] >= 0, 'text-rose-700' => $row['sold_growth'] < 0])>{{ $row['sold_growth'] >= 0 ? '+' : '' }}{{ number_format($row['sold_growth'], 1, ',', '.') }}%</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-8 text-center text-slate-500">Chưa có dữ liệu lịch sử để so sánh.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Cơ cấu VAT · {{ $period }}</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-3 xl:grid-cols-1">
                    @foreach([5, 8, 10] as $rate)
                        <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3"><span class="text-sm font-bold text-slate-500">Thuế {{ $rate }}%</span><strong class="text-slate-950">{{ $money($stats['by_tax_rate'][$rate] ?? 0) }}</strong></div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Nghiệp vụ</p>
                <p class="mt-2 text-sm leading-6 text-slate-500">PWA ưu tiên báo cáo; các tác vụ xử lý được đặt ở khu vực phụ để không làm loãng Dashboard.</p>
                <div class="mt-4 grid gap-3">
                    <a href="{{ route('client.invoices.index') }}" class="flex min-h-12 items-center justify-between rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold text-slate-800 hover:bg-slate-50"><span>Tra cứu hóa đơn</span><span aria-hidden="true">→</span></a>
                    @if(auth('web')->user()?->can('client.invoices.sync'))
                        <a href="{{ route('client.invoices.sync') }}" class="flex min-h-12 items-center justify-between rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold text-slate-800 hover:bg-slate-50"><span>Đồng bộ từ GDT</span><span aria-hidden="true">→</span></a>
                    @endif
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
