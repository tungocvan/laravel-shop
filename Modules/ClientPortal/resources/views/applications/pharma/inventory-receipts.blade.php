@extends('ClientPortal::layouts.application')
@section('title','Phiếu nhập kho')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle','Workspace Pharma dành cho User')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)

@section('content')
@php
    $labels=['draft'=>'Nháp','pending_approval'=>'Chờ duyệt','approved'=>'Đã duyệt','posted'=>'Đã ghi sổ','cancelled'=>'Đã hủy'];
    $railStatuses=[''=>'Tất cả', ...$labels];
    $activeStatus=(string)($filters['status'] ?? '');
    $hasFilters=filled($filters['q']) || $activeStatus!=='';
@endphp

<div class="min-w-0 max-w-full overflow-x-hidden bg-slate-50 pb-24 lg:pb-8" data-receipt-workspace>
    <header class="sticky top-0 z-30 -mx-4 border-b border-slate-200 bg-white/95 px-4 py-4 backdrop-blur sm:-mx-6 sm:px-6 lg:static lg:mx-0 lg:rounded-3xl lg:border lg:px-6">
        <div class="relative flex items-center justify-center">
            <a href="{{ route('client.pharma.inventory') }}" class="absolute left-0 inline-flex h-11 w-11 items-center justify-center rounded-full text-2xl text-slate-900 transition active:scale-95 motion-reduce:transform-none" aria-label="Quay lại tồn kho">←</a>
            <div class="px-12 text-center">
                <p class="text-[10px] font-black uppercase tracking-[0.16em] text-slate-400">Inventory · Receipts</p>
                <h1 class="mt-0.5 text-xl font-black tracking-tight text-slate-950 sm:text-2xl">Phiếu nhập kho</h1>
            </div>
        </div>
    </header>

    <section class="mt-4">
        <div class="flex items-center gap-2">
            <form id="receipt-search-form" method="GET" action="{{ route('client.pharma.inventory.receipts') }}" class="flex min-w-0 flex-1 gap-2 lg:max-w-[620px]">
                @if($activeStatus!=='')<input type="hidden" name="status" value="{{ $activeStatus }}">@endif
                <label class="relative min-w-0 flex-1">
                    <span class="sr-only">Tìm kiếm phiếu nhập</span>
                    <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-xl text-slate-500">⌕</span>
                    <input id="receipt-search-input" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Số phiếu / nhà cung cấp / hóa đơn" data-pwa-debounced-search="600" data-pwa-search-region="#receipt-search-region" data-pwa-search-clear="#receipt-search-clear" class="h-14 w-full rounded-2xl border border-slate-300 bg-white pl-12 pr-11 text-[15px] font-medium text-slate-900 placeholder:text-[13px] placeholder:font-normal outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
                    <button id="receipt-search-clear" type="button" data-pwa-search-clear-button="#receipt-search-input" class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full px-2 py-1 text-xl text-slate-500 {{ $filters['q'] ? '' : 'hidden' }}" aria-label="Xóa từ khóa tìm kiếm">×</button>
                </label>
            </form>
            @can('client.pharma.inventory.receipts.create')
                <a href="{{ route('client.pharma.inventory.receipts.create') }}" class="ml-auto hidden h-14 shrink-0 items-center justify-center gap-2 rounded-2xl bg-slate-950 px-5 text-sm font-black text-white shadow-sm transition active:scale-[0.985] lg:inline-flex motion-reduce:transform-none">
                    <span class="text-base font-light leading-none">+</span><span>Thêm phiếu nhập</span>
                </a>
            @endcan
        </div>
    </section>

    <div id="receipt-search-region">
    <nav class="mt-3 flex w-full max-w-full snap-x snap-proximity items-center gap-1.5 overflow-x-auto overscroll-x-contain pb-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" data-receipt-status-bar aria-label="Trạng thái phiếu nhập">
        @foreach($railStatuses as $value=>$label)
            @php($statusCount=$value==='' ? ($statusCounts['all'] ?? 0) : ($statusCounts[$value] ?? 0))
            @if($statusCount>0 || $activeStatus===$value)
                <a href="{{ route('client.pharma.inventory.receipts', array_filter(['q'=>$filters['q'],'status'=>$value], fn($v)=>$v!=='' && $v!==null)) }}" class="inline-flex h-7 w-fit shrink-0 snap-start items-center whitespace-nowrap rounded-full border px-2.5 text-[11px] font-bold transition active:scale-[0.985] motion-reduce:transform-none {{ $activeStatus===$value ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 bg-white text-slate-600' }}">
                    {{ $label }} <span class="ml-1 opacity-70">{{ number_format($statusCount) }}</span>
                </a>
            @else
                <span aria-disabled="true" data-disabled-status class="inline-flex h-7 w-fit shrink-0 cursor-not-allowed snap-start items-center whitespace-nowrap rounded-full border border-slate-100 bg-slate-50 px-2.5 text-[11px] font-bold text-slate-300">
                    {{ $label }} <span class="ml-1">{{ number_format($statusCount) }}</span>
                </span>
            @endif
        @endforeach
        @if($hasFilters)
            <a href="{{ route('client.pharma.inventory.receipts') }}" class="ml-1 inline-flex h-7 shrink-0 items-center whitespace-nowrap rounded-full border border-slate-200 bg-white px-2.5 text-[11px] font-black text-rose-600 transition active:scale-[0.985] motion-reduce:transform-none">Xóa bộ lọc</a>
        @endif
    </nav>

        <section id="receipt-mobile-list" class="mt-4 grid min-w-0 max-w-full grid-cols-1 gap-3 md:grid-cols-2 xl:hidden">
            @forelse($receipts as $receipt)
                <a data-receipt-card href="{{ route('client.pharma.inventory.receipts.show',$receipt) }}" class="min-w-0 max-w-full overflow-hidden rounded-3xl border border-slate-200 bg-white p-4 shadow-sm transition active:scale-[0.985] motion-reduce:transform-none">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="break-all text-xs font-black uppercase tracking-wide text-slate-500">{{ $receipt->number }}</p>
                            <h2 class="mt-1 line-clamp-2 break-words text-base font-black leading-5 text-slate-950">{{ $receipt->supplier_name ?: 'Chưa xác định nhà cung cấp' }}</h2>
                        </div>
                        <span class="h-fit shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-black text-slate-700">{{ $labels[$receipt->status] ?? $receipt->status }}</span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-3 border-t border-slate-100 pt-3 text-sm">
                        <div><p class="text-xs text-slate-500">Ngày nhập</p><p class="mt-0.5 font-bold text-slate-800">{{ $receipt->receipt_date?->format('d/m/Y') ?: '—' }}</p></div>
                        <div class="text-right"><p class="text-xs text-slate-500">Số lượng</p><p class="mt-0.5 font-black tabular-nums text-slate-950">{{ number_format((float)$receipt->total_quantity,0,',','.') }} · {{ number_format($receipt->items_count) }} dòng</p></div>
                    </div>
                    <div class="mt-3 flex items-center justify-between gap-3 rounded-2xl bg-slate-50 px-3 py-2.5">
                        <div class="min-w-0"><p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Hóa đơn</p><p class="mt-0.5 truncate text-sm font-bold text-slate-700">{{ $receipt->invoice_number ?: 'Chưa có số HĐ' }}@if($receipt->invoice_symbol) · {{ $receipt->invoice_symbol }}@endif</p></div>
                        <span class="shrink-0 text-xl font-black text-slate-300">›</span>
                    </div>
                </a>
            @empty
                <div class="col-span-full flex min-h-[48vh] flex-col items-center justify-center rounded-3xl border border-dashed border-slate-300 bg-white px-6 text-center">
                    <div class="flex h-20 w-20 items-center justify-center rounded-3xl bg-slate-100 text-4xl text-slate-400">≡</div>
                    <h2 class="mt-5 text-xl font-black text-slate-900">Không có phiếu nhập phù hợp</h2>
                    <p class="mt-2 text-sm text-slate-500">Thử thay đổi từ khóa hoặc trạng thái đang chọn.</p>
                    @if($hasFilters)<a href="{{ route('client.pharma.inventory.receipts') }}" class="mt-4 inline-flex min-h-11 items-center rounded-2xl border border-slate-300 bg-white px-5 text-sm font-black text-slate-700">Xóa bộ lọc</a>@endif
                </div>
            @endforelse
        </section>

        <section class="mt-4 hidden overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm xl:block">
            <table class="w-full table-fixed text-left text-sm">
                <thead class="bg-slate-50 text-xs font-black uppercase tracking-wide text-slate-500">
                    <tr><th class="w-[17%] px-5 py-4">Số phiếu</th><th class="w-[14%] px-5 py-4">Ngày nhập</th><th class="w-[25%] px-5 py-4">Nhà cung cấp</th><th class="w-[20%] px-5 py-4">Hóa đơn</th><th class="w-[12%] px-5 py-4 text-right">Số lượng</th><th class="w-[12%] px-5 py-4">Trạng thái</th></tr>
                </thead>
                <tbody id="receipt-desktop-body" class="divide-y divide-slate-100">
                    @foreach($receipts as $receipt)
                        <tr data-receipt-row class="transition hover:bg-slate-50">
                            <td class="px-5 py-4"><a class="font-black text-slate-950" href="{{ route('client.pharma.inventory.receipts.show',$receipt) }}">{{ $receipt->number }}</a></td>
                            <td class="px-5 py-4 font-semibold text-slate-700">{{ $receipt->receipt_date?->format('d/m/Y') ?: '—' }}</td>
                            <td class="px-5 py-4"><a class="block truncate font-bold text-slate-800" href="{{ route('client.pharma.inventory.receipts.show',$receipt) }}">{{ $receipt->supplier_name ?: '—' }}</a></td>
                            <td class="px-5 py-4"><span class="font-semibold text-slate-700">{{ $receipt->invoice_number ?: '—' }}</span>@if($receipt->invoice_symbol)<span class="mt-0.5 block truncate text-xs text-slate-500">{{ $receipt->invoice_symbol }}</span>@endif</td>
                            <td class="px-5 py-4 text-right"><p class="font-black tabular-nums text-slate-950">{{ number_format((float)$receipt->total_quantity,0,',','.') }}</p><p class="mt-0.5 text-xs text-slate-400">{{ number_format($receipt->items_count) }} dòng</p></td>
                            <td class="px-5 py-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-700">{{ $labels[$receipt->status] ?? $receipt->status }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        @if($receipts->hasMorePages())
            <div id="receipt-load-more-wrap" class="mt-5 text-center xl:hidden">
                <a id="receipt-load-more" href="{{ $receipts->nextPageUrl() }}" data-pwa-load-more data-pwa-load-more-target="#receipt-mobile-list" data-pwa-load-more-items="#receipt-mobile-list [data-receipt-card]" data-pwa-load-more-wrap="#receipt-load-more-wrap" data-pwa-pending-label="Đang tải…" class="inline-flex min-h-12 w-full items-center justify-center rounded-2xl border border-slate-300 bg-white px-6 text-sm font-black text-slate-800 shadow-sm transition active:scale-[0.985] sm:w-auto motion-reduce:transform-none">Xem thêm</a>
                <p class="mt-2 text-xs font-semibold text-slate-400">Đã hiển thị {{ $receipts->count() }} / {{ $receipts->total() }}</p>
            </div>
            <div class="mt-5 hidden text-center xl:block">
                <a href="{{ $receipts->nextPageUrl() }}" class="inline-flex min-h-12 items-center justify-center rounded-2xl border border-slate-300 bg-white px-6 text-sm font-black text-slate-800 shadow-sm transition active:scale-[0.985] motion-reduce:transform-none">Xem thêm</a>
                <p class="mt-2 text-xs font-semibold text-slate-400">Trang {{ $receipts->currentPage() }} · {{ number_format($receipts->total()) }} phiếu</p>
            </div>
        @endif
    </div>

    @can('client.pharma.inventory.receipts.create')
        <a href="{{ route('client.pharma.inventory.receipts.create') }}" class="fixed z-40 inline-flex h-11 w-11 items-center justify-center rounded-full bg-slate-950 text-xl font-light text-white shadow-lg transition active:scale-[0.985] lg:hidden motion-reduce:transform-none" style="right:calc(18px + env(safe-area-inset-right,0px));bottom:calc(18px + env(safe-area-inset-bottom,0px))" aria-label="Thêm phiếu nhập">+</a>
    @endcan
</div>
@endsection
