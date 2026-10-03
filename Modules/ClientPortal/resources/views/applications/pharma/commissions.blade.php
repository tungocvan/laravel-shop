@extends('ClientPortal::layouts.application')

@section('title', $featurePresentation['page_title'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Commission Ledger · chỉ đọc')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)

@section('content')
<div class="mb-4">
    <a href="{{ route('client.pharma.dashboard') }}" class="inline-flex min-h-11 items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition active:scale-[0.985] motion-reduce:transform-none">← Không gian làm việc Pharma</a>
</div>

<div class="min-w-0 space-y-4 overflow-x-hidden">
    <section class="rounded-[2rem] bg-slate-950 px-5 py-6 text-white shadow-sm sm:px-7">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-300">{{ $featurePresentation['eyebrow'] }}</p>
        <h1 class="mt-2 text-2xl font-black tracking-tight sm:text-3xl">{{ $featurePresentation['page_title'] }}</h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-300">{{ $featurePresentation['page_description'] }}</p>
        <dl class="mt-5 grid gap-3 sm:grid-cols-3">
            <div class="rounded-2xl bg-white/10 p-4"><dt class="text-xs font-bold uppercase tracking-wide text-slate-300">Doanh số ghi nhận</dt><dd class="mt-1 text-xl font-black tabular-nums">{{ number_format($summary['revenue'],0,',','.') }} đ</dd></div>
            <div class="rounded-2xl bg-white/10 p-4"><dt class="text-xs font-bold uppercase tracking-wide text-slate-300">Hoa hồng ròng</dt><dd class="mt-1 text-xl font-black tabular-nums">{{ number_format($summary['commission'],0,',','.') }} đ</dd></div>
            <div class="rounded-2xl bg-white/10 p-4"><dt class="text-xs font-bold uppercase tracking-wide text-slate-300">Chưa xác định</dt><dd class="mt-1 text-xl font-black tabular-nums">{{ number_format($summary['unresolved']) }}</dd></div>
        </dl>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <nav class="flex gap-2 overflow-x-auto pb-1" aria-label="Nguồn hoa hồng">
            @foreach($sources as $value=>$label)
                <a href="{{ route('client.pharma.commissions', array_filter(['source'=>$value==='all' ? null : $value,'from'=>$filters['from'],'to'=>$filters['to'],'q'=>$filters['q']])) }}" class="whitespace-nowrap rounded-full border px-4 py-2 text-sm font-black transition active:scale-[0.985] motion-reduce:transform-none {{ $filters['source']===$value ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 bg-white text-slate-600' }}">{{ $label }}</a>
            @endforeach
        </nav>

        <form id="commission-filter-form" method="GET" action="{{ route('client.pharma.commissions') }}" class="mt-4 grid gap-3 lg:grid-cols-[minmax(0,1fr)_160px_160px_auto] lg:items-end">
            <input type="hidden" name="source" value="{{ $filters['source'] }}">
            <label class="relative min-w-0">
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Tìm kiếm</span>
                <input id="commission-search-input" data-pwa-debounced-search="800" data-pwa-search-region="#commission-results" data-pwa-search-clear="#commission-search-clear" type="search" name="q" value="{{ $filters['q'] }}" autocomplete="off" placeholder="Thuốc, mã thuốc, khách hàng, số phiếu..." class="h-[46px] w-full rounded-2xl border border-slate-300 px-4 pr-11 text-sm text-slate-950 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
                <button id="commission-search-clear" data-pwa-search-clear-button="#commission-search-input" type="button" aria-label="Xóa tìm kiếm hoa hồng" class="absolute bottom-[7px] right-1.5 inline-flex h-8 w-8 items-center justify-center rounded-full text-lg font-bold text-slate-400 hover:bg-slate-100 hover:text-slate-700 {{ $filters['q']==='' ? 'hidden' : '' }}">×</button>
            </label>
            <label><span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Từ ngày</span><input type="date" name="from" value="{{ $filters['from'] }}" class="h-[46px] w-full rounded-2xl border border-slate-300 bg-white px-3 text-sm font-semibold" onchange="this.form.requestSubmit()"></label>
            <label><span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Đến ngày</span><input type="date" name="to" value="{{ $filters['to'] }}" class="h-[46px] w-full rounded-2xl border border-slate-300 bg-white px-3 text-sm font-semibold" onchange="this.form.requestSubmit()"></label>
            <a href="{{ route('client.pharma.commissions') }}" class="inline-flex h-[46px] items-center justify-center rounded-2xl border border-slate-300 bg-white px-4 text-sm font-black text-slate-700">Xóa bộ lọc</a>
        </form>
    </section>

    <div id="commission-results" class="space-y-4">
        <section id="commission-list" class="grid gap-3 xl:grid-cols-2">
            @forelse($rows as $row)
                @php
                    $isReversal=$row->entry_type===\Modules\Pharma\Models\InventoryIssueCommission::TYPE_REVERSAL;
                    $isUnresolved=$row->status===\Modules\Pharma\Models\InventoryIssueCommission::STATUS_UNRESOLVED;
                    $customer=$row->partner?->name ?: $row->issue?->recipient_name ?: '—';
                @endphp
                <article data-commission-item class="min-w-0 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition active:scale-[0.985] motion-reduce:transform-none">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ $row->calculated_at?->format('d/m/Y H:i') }} · {{ $row->source_type==='bid' ? 'Trúng thầu' : 'Bảng giá' }}</p>
                            <h2 class="mt-1 line-clamp-2 font-black text-slate-950">{{ $row->medicine?->name ?: 'Sản phẩm #'.$row->medicine_id }}</h2>
                            <p class="mt-1 truncate text-sm text-slate-500">{{ $row->medicine?->medicine_code ?: '—' }} · {{ $customer }}</p>
                        </div>
                        <span class="shrink-0 rounded-full px-3 py-1.5 text-xs font-black {{ $isUnresolved ? 'bg-amber-100 text-amber-800' : ($isReversal ? 'bg-slate-200 text-slate-700' : 'bg-emerald-100 text-emerald-800') }}">{{ $isUnresolved ? 'Chưa xác định' : ($isReversal ? 'Hoàn tác' : 'Đã ghi nhận') }}</span>
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-3 rounded-2xl bg-slate-50 p-4 text-sm">
                        <div><dt class="text-xs font-bold text-slate-400">SL thực xuất</dt><dd class="mt-1 font-black tabular-nums text-slate-800">{{ number_format((float)$row->quantity,0,',','.') }}</dd></div>
                        <div><dt class="text-xs font-bold text-slate-400">Doanh số</dt><dd class="mt-1 font-black tabular-nums text-slate-800">{{ number_format((float)$row->revenue_amount,0,',','.') }} đ</dd></div>
                        <div><dt class="text-xs font-bold text-slate-400">Chính sách</dt><dd class="mt-1 font-black tabular-nums text-slate-800">{{ $row->commission_percentage!==null ? rtrim(rtrim(number_format((float)$row->commission_percentage,4,'.',''), '0'),'.').'%' : '—' }}</dd></div>
                        <div><dt class="text-xs font-bold text-slate-400">Hoa hồng</dt><dd class="mt-1 font-black tabular-nums {{ (float)$row->commission_amount<0 ? 'text-rose-700' : 'text-slate-950' }}">{{ number_format((float)$row->commission_amount,0,',','.') }} đ</dd></div>
                    </dl>
                    <p class="mt-3 text-xs font-semibold text-slate-400">Phiếu {{ $row->issue?->number ?: '#'.$row->issue_id }}{{ $row->resolution_note ? ' · '.$row->resolution_note : '' }}</p>
                </article>
            @empty
                <div class="xl:col-span-2 rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center"><h2 class="font-black text-slate-800">Chưa có hoa hồng phù hợp</h2><p class="mt-2 text-sm text-slate-500">Thử thay đổi khoảng ngày, nguồn hoặc từ khóa tìm kiếm.</p></div>
            @endforelse
        </section>

        @if($rows->hasMorePages())
            <div id="commission-load-more-wrap" class="pt-1 text-center">
                <a id="commission-load-more" data-pwa-load-more data-pwa-load-more-target="#commission-list" data-pwa-load-more-items="#commission-list [data-commission-item]" data-pwa-load-more-wrap="#commission-load-more-wrap" data-pwa-pending-label="Đang tải…" href="{{ $rows->nextPageUrl() }}" class="inline-flex min-h-12 w-full items-center justify-center rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-black text-slate-800 shadow-sm transition active:scale-[0.985] sm:w-auto motion-reduce:transform-none">Xem thêm</a>
                <p class="mt-2 text-xs font-semibold text-slate-400">Đã hiển thị {{ $rows->count() }} / {{ $rows->total() }}</p>
            </div>
        @endif
    </div>
</div>
@endsection
