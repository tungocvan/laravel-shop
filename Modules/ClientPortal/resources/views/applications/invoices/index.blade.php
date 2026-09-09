@extends('ClientPortal::layouts.application')

@section('title', 'Danh sách hóa đơn')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Tra cứu và phân tích hóa đơn')
@section('app-dashboard-route', route('client.invoices.dashboard'))

@section('content')
@php
    $month = (int) substr($filters['issued_date_from'], 5, 2);
    $year = (int) substr($filters['issued_date_from'], 0, 4);
    $statusLabels = ['available' => 'Đã có PDF', 'missing' => 'Chưa có PDF', 'error' => 'PDF lỗi', 'unsupported' => 'Không hỗ trợ'];
    $showInvoiceTypeColumn = empty($filters['invoice_type']);
@endphp

<div class="space-y-5">
    <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-slate-500">Tháng {{ str_pad((string) $month, 2, '0', STR_PAD_LEFT) }}/{{ $year }}</p>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-950 sm:text-3xl">Danh sách hóa đơn</h1>
            <p class="mt-2 text-sm text-slate-500">{{ number_format($stats['count'] ?? 0) }} kết quả · {{ number_format((float) ($stats['total_amount'] ?? 0), 0, ',', '.') }} ₫</p>
        </div>
        <a href="{{ route('client.invoices.dashboard') }}" class="hidden min-h-11 items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 shadow-sm sm:inline-flex">Tổng quan</a>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <form method="GET" action="{{ route('client.invoices.index') }}" class="flex gap-2">
            <input type="hidden" name="invoice_type" value="{{ $filters['invoice_type'] }}">
            <input type="hidden" name="partner" value="{{ $filters['name'] }}">
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
            <input type="hidden" name="per_page" value="{{ $perPage }}">
            <label class="sr-only" for="invoice-search">Tìm hóa đơn</label>
            <input id="invoice-search" name="search" value="{{ $filters['search'] }}" type="search" inputmode="search" autocomplete="off" placeholder="MST, tên đối tác, số HĐ, ký hiệu..." class="min-h-12 min-w-0 flex-1 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-slate-400 focus:bg-white focus:ring-2 focus:ring-slate-200">
            <button type="submit" class="min-h-12 shrink-0 rounded-2xl bg-slate-950 px-5 py-3 text-sm font-bold text-white shadow-sm">Tìm</button>
        </form>

        <div class="mt-3 flex gap-2 overflow-x-auto pb-1 md:hidden">
            @foreach(['' => 'Tất cả', 'purchase' => 'Mua vào', 'sold' => 'Bán ra'] as $value => $label)
                <a href="{{ route('client.invoices.index', array_merge(request()->except('page'), ['invoice_type' => $value])) }}" @class([
                    'flex min-h-11 shrink-0 items-center rounded-full px-4 py-2 text-sm font-bold',
                    'bg-slate-950 text-white' => ($filters['invoice_type'] ?? '') === $value,
                    'border border-slate-200 bg-white text-slate-600' => ($filters['invoice_type'] ?? '') !== $value,
                ])>{{ $label }}</a>
            @endforeach

            <a href="{{ route('client.invoices.index') }}" class="flex min-h-11 shrink-0 items-center rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600">Xóa lọc</a>

            <details class="group relative shrink-0">
                <summary class="flex min-h-11 cursor-pointer list-none items-center rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600">Bộ lọc</summary>
                <form method="GET" action="{{ route('client.invoices.index') }}" class="fixed inset-x-0 bottom-0 z-50 max-h-[78dvh] overflow-y-auto rounded-t-[2rem] border-t border-slate-200 bg-white p-5 pb-[calc(1.25rem+env(safe-area-inset-bottom))] shadow-2xl">
                    <input type="hidden" name="search" value="{{ $filters['search'] }}">
                    <input type="hidden" name="invoice_type" value="{{ $filters['invoice_type'] }}">
                    <div class="mx-auto mb-5 h-1.5 w-12 rounded-full bg-slate-200"></div>
                    <h2 class="text-xl font-black">Bộ lọc quản trị</h2>
                    <p class="mt-1 text-sm text-slate-500">Các lựa chọn cập nhật ngay; đối tác nhấn Enter để tìm.</p>
                    <div class="mt-5 grid grid-cols-2 gap-3">
                        <label class="col-span-2 text-sm font-semibold text-slate-700">Đối tác
                            <x-search name="partner" value="{{ $filters['name'] }}" placeholder="Tìm tên hoặc MST đối tác..." class="mt-2" autocomplete="off" />
                        </label>
                        <label class="text-sm font-semibold text-slate-700">Tháng
                            <select name="month" onchange="this.form.submit()" class="mt-2 min-h-12 w-full rounded-2xl border border-slate-200 bg-white px-3">@for($m = 1; $m <= 12; $m++)<option value="{{ $m }}" @selected($m === $month)>Tháng {{ $m }}</option>@endfor</select>
                        </label>
                        <label class="text-sm font-semibold text-slate-700">Năm
                            <input name="year" onchange="this.form.submit()" type="number" min="2000" max="2100" value="{{ $year }}" class="mt-2 min-h-12 w-full rounded-2xl border border-slate-200 px-3">
                        </label>
                        <label class="col-span-2 text-sm font-semibold text-slate-700">Sắp xếp
                            <select name="sort" onchange="this.form.submit()" class="mt-2 min-h-12 w-full rounded-2xl border border-slate-200 bg-white px-3"><option value="date_desc" @selected($filters['sort'] === 'date_desc')>Mới nhất</option><option value="date_asc" @selected($filters['sort'] === 'date_asc')>Cũ nhất</option><option value="amount_desc" @selected($filters['sort'] === 'amount_desc')>Giá trị cao nhất</option><option value="amount_asc" @selected($filters['sort'] === 'amount_asc')>Giá trị thấp nhất</option><option value="partner_asc" @selected($filters['sort'] === 'partner_asc')>Đối tác A → Z</option><option value="partner_desc" @selected($filters['sort'] === 'partner_desc')>Đối tác Z → A</option></select>
                        </label>
                    </div>
                    <a href="{{ route('client.invoices.index') }}" class="mt-6 flex min-h-12 items-center justify-center rounded-2xl border border-slate-200 font-bold text-slate-700">Xóa bộ lọc</a>
                </form>
            </details>
        </div>

        <form method="GET" action="{{ route('client.invoices.index') }}" class="mt-4 hidden grid-cols-2 gap-3 md:grid lg:grid-cols-7">
            <input type="hidden" name="search" value="{{ $filters['search'] }}">
            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Loại HĐ<select name="invoice_type" onchange="this.form.submit()" class="mt-2 min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold"><option value="">Tất cả</option><option value="purchase" @selected(($filters['invoice_type'] ?? '') === 'purchase')>Mua vào</option><option value="sold" @selected(($filters['invoice_type'] ?? '') === 'sold')>Bán ra</option></select></label>
            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Đối tác<x-search name="partner" value="{{ $filters['name'] }}" placeholder="Tên hoặc MST đối tác..." class="mt-2" autocomplete="off" /></label>
            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Tháng<select name="month" onchange="this.form.submit()" class="mt-2 min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold">@for($m = 1; $m <= 12; $m++)<option value="{{ $m }}" @selected($m === $month)>{{ $m }}</option>@endfor</select></label>
            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Năm<input name="year" onchange="this.form.submit()" type="number" min="2000" max="2100" value="{{ $year }}" class="mt-2 min-h-11 w-full rounded-xl border border-slate-200 px-3 text-sm font-semibold"></label>
            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Sắp xếp<select name="sort" onchange="this.form.submit()" class="mt-2 min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold"><option value="date_desc" @selected($filters['sort'] === 'date_desc')>Mới nhất</option><option value="date_asc" @selected($filters['sort'] === 'date_asc')>Cũ nhất</option><option value="amount_desc" @selected($filters['sort'] === 'amount_desc')>Giá trị cao nhất</option><option value="amount_asc" @selected($filters['sort'] === 'amount_asc')>Giá trị thấp nhất</option><option value="partner_asc" @selected($filters['sort'] === 'partner_asc')>Đối tác A → Z</option><option value="partner_desc" @selected($filters['sort'] === 'partner_desc')>Đối tác Z → A</option></select></label>
            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Mỗi trang<select name="per_page" onchange="this.form.submit()" class="mt-2 min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold">@foreach([10,25,50,100] as $value)<option value="{{ $value }}" @selected($perPage === $value)>{{ $value }}</option>@endforeach</select></label>
            <a href="{{ route('client.invoices.index') }}" class="mt-6 flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm hover:bg-slate-50">Xóa bộ lọc</a>
        </form>
    </section>

    <form method="POST" action="{{ route('client.invoices.export') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="search" value="{{ $filters['search'] }}"><input type="hidden" name="partner" value="{{ $filters['name'] }}"><input type="hidden" name="invoice_type" value="{{ $filters['invoice_type'] }}"><input type="hidden" name="month" value="{{ $month }}"><input type="hidden" name="year" value="{{ $year }}"><input type="hidden" name="sort" value="{{ $filters['sort'] }}">

        @if($invoices->isEmpty())
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center"><h2 class="text-lg font-black">Không tìm thấy hóa đơn</h2><p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">Thử đổi từ khóa, tháng, đối tác hoặc cách sắp xếp. Không có dữ liệu riêng tư nào được lưu offline từ màn hình này.</p></div>
        @else
            <div class="space-y-3 md:hidden">
                @foreach($invoices as $invoice)
                    @php($pdfStatus = $pdfStatuses[$invoice->id] ?? 'missing')
                    <article class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex items-start gap-3">
                            <input type="checkbox" name="selected[]" value="{{ $invoice->id }}" class="mt-1 h-5 w-5 shrink-0 rounded border-slate-300" aria-label="Chọn hóa đơn {{ $invoice->invoice_number }}">
                            <a href="{{ route('client.invoices.show', $invoice) }}" class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-3"><div class="min-w-0"><h2 class="truncate font-black text-slate-950">{{ $invoice->name ?: 'Đối tác chưa xác định' }}</h2><p class="mt-1 text-xs font-semibold text-slate-500">MST {{ $invoice->tax_code ?: '—' }}</p></div><span @class(['shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold', 'bg-emerald-50 text-emerald-700' => $pdfStatus === 'available', 'bg-amber-50 text-amber-700' => $pdfStatus === 'missing', 'bg-rose-50 text-rose-700' => $pdfStatus === 'error', 'bg-slate-100 text-slate-600' => $pdfStatus === 'unsupported'])>{{ $statusLabels[$pdfStatus] ?? 'PDF' }}</span></div>
                                <div class="mt-4 flex items-end justify-between gap-3"><div><p class="text-xs text-slate-500">{{ optional($invoice->issued_date)->format('d/m/Y') }} · {{ $invoice->symbol ?: '—' }}/{{ $invoice->invoice_number ?: '—' }}</p><span @class(['mt-2 inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold', 'bg-sky-50 text-sky-700' => $invoice->invoice_type === 'purchase', 'bg-emerald-50 text-emerald-700' => $invoice->invoice_type === 'sold'])>{{ $invoice->invoice_type === 'purchase' ? 'Mua vào' : ($invoice->invoice_type === 'sold' ? 'Bán ra' : 'Chưa phân loại') }}</span></div><p class="text-right text-base font-black text-slate-950">{{ number_format((float) $invoice->total_amount, 0, ',', '.') }} ₫</p></div>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="hidden overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm md:block">
                <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-slate-500"><tr><th class="w-12 px-4 py-4">Chọn</th><th class="px-4 py-4">Đối tác</th>@if($showInvoiceTypeColumn)<th class="px-4 py-4">Loại HĐ</th>@endif<th class="px-4 py-4">Hóa đơn</th><th class="px-4 py-4">Ngày</th><th class="px-4 py-4 text-right">Giá trị</th><th class="px-4 py-4">PDF</th></tr></thead><tbody class="divide-y divide-slate-100">
                    @foreach($invoices as $invoice)
                        @php($pdfStatus = $pdfStatuses[$invoice->id] ?? 'missing')
                        <tr class="hover:bg-slate-50/80"><td class="px-4 py-4"><input type="checkbox" name="selected[]" value="{{ $invoice->id }}" class="h-5 w-5 rounded border-slate-300" aria-label="Chọn hóa đơn {{ $invoice->invoice_number }}"></td><td class="px-4 py-4"><a href="{{ route('client.invoices.show', $invoice) }}" class="font-bold text-slate-950 hover:underline">{{ $invoice->name ?: 'Chưa xác định' }}</a><div class="mt-1 text-xs text-slate-500">{{ $invoice->tax_code ?: '—' }}</div></td>@if($showInvoiceTypeColumn)<td class="px-4 py-4"><span @class(['rounded-full px-2.5 py-1 text-xs font-bold', 'bg-sky-50 text-sky-700' => $invoice->invoice_type === 'purchase', 'bg-emerald-50 text-emerald-700' => $invoice->invoice_type === 'sold', 'bg-slate-100 text-slate-600' => ! in_array($invoice->invoice_type, ['purchase', 'sold'], true)])>{{ $invoice->invoice_type === 'purchase' ? 'Mua vào' : ($invoice->invoice_type === 'sold' ? 'Bán ra' : 'Chưa phân loại') }}</span></td>@endif<td class="px-4 py-4"><div class="font-semibold">{{ $invoice->symbol ?: '—' }}/{{ $invoice->invoice_number ?: '—' }}</div></td><td class="whitespace-nowrap px-4 py-4">{{ optional($invoice->issued_date)->format('d/m/Y') }}</td><td class="whitespace-nowrap px-4 py-4 text-right font-black">{{ number_format((float) $invoice->total_amount, 0, ',', '.') }} ₫</td><td class="px-4 py-4"><span @class(['rounded-full px-2.5 py-1 text-xs font-bold', 'bg-emerald-50 text-emerald-700' => $pdfStatus === 'available', 'bg-amber-50 text-amber-700' => $pdfStatus === 'missing', 'bg-rose-50 text-rose-700' => $pdfStatus === 'error', 'bg-slate-100 text-slate-600' => $pdfStatus === 'unsupported'])>{{ $statusLabels[$pdfStatus] ?? 'PDF' }}</span></td></tr>
                    @endforeach
                </tbody></table></div>
            </div>

            @if(auth('web')->user()?->can('client.invoices.export'))
                <div class="sticky bottom-[calc(4.5rem+env(safe-area-inset-bottom))] z-20 flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-xl backdrop-blur md:bottom-4"><div class="min-w-0"><p class="text-sm font-bold text-slate-900">Xuất Excel</p><p class="truncate text-xs text-slate-500">Có chọn: xuất phần chọn · Không chọn: xuất toàn bộ kết quả lọc</p></div><button type="submit" class="min-h-11 shrink-0 rounded-xl bg-slate-950 px-4 text-sm font-bold text-white">Xuất</button></div>
            @endif
        @endif
    </form>

    @if($invoices->hasPages())<div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">{{ $invoices->onEachSide(1)->links() }}</div>@endif
</div>
@endsection
