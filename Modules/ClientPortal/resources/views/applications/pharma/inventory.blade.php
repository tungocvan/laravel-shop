@extends('ClientPortal::layouts.application')

@section('title', $featurePresentation['page_title'] ?? 'Tồn kho Pharma')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Workspace Pharma dành cho User')
@section('app-dashboard-route', route('client.pharma.dashboard'))

@section('content')
@php
    $money = fn ($value) => number_format((float) $value, 0, ',', '.').' đ';
    $expiryLabels = ['expired'=>'Đã hết hạn','lt1'=>'< 1 tháng','lt3'=>'< 3 tháng','lt6'=>'< 6 tháng','safe'=>'≥ 6 tháng'];
    $hasFilters = $filters['q'] || $filters['expiry'] || ($canViewCosts && ($filters['cost_status'] || $filters['sort']));
@endphp
<div class="min-w-0 space-y-4 overflow-x-hidden pb-24 xl:pb-8">
    <section class="rounded-[1.75rem] bg-slate-950 px-5 py-5 text-white shadow-sm sm:px-7 sm:py-6">
        <a href="{{ route('client.pharma.dashboard') }}" class="hidden text-sm font-bold text-slate-300 hover:text-white lg:inline-flex">← Quay về dashboard</a>
        <p class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-400 lg:mt-5">{{ $featurePresentation['eyebrow'] ?? 'Inventory' }}</p>
        <h1 class="mt-1.5 text-2xl font-black tracking-tight sm:text-3xl">{{ $featurePresentation['page_title'] ?? 'Tồn kho Pharma' }}</h1>
        <p class="mt-1.5 max-w-3xl text-sm leading-5 text-slate-300 sm:leading-6">{{ $featurePresentation['page_description'] ?? 'Theo dõi số lượng tồn, giá trị, lô và hạn dùng.' }}</p>
    </section>

    <section class="grid grid-cols-2 gap-3 {{ $canViewCosts ? 'lg:grid-cols-4' : 'lg:grid-cols-2' }}">
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-bold text-slate-500">Lô đang còn hàng</p><p class="mt-2 text-2xl font-black text-slate-950">{{ number_format($summary['balance_count']) }}</p></div>
        @if($canViewCosts)
            <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-bold text-slate-500">Giá trị tồn</p><p class="mt-2 text-lg font-black text-slate-950 sm:text-2xl">{{ $money($summary['inventory_value']) }}</p></div>
        @endif
        <a href="{{ route('client.pharma.inventory', ['expiry'=>'lt6']) }}" class="rounded-3xl border p-4 shadow-sm transition active:scale-[0.985] motion-reduce:transform-none {{ $filters['expiry']==='lt6' ? 'border-amber-400 bg-amber-100 ring-2 ring-amber-200' : 'border-amber-200 bg-amber-50' }}"><p class="text-xs font-bold text-amber-700">Sắp hết hạn ≤ 6 tháng</p><p class="mt-2 text-2xl font-black text-amber-950">{{ number_format($summary['near_expiry_count']) }}</p></a>
        @if($canViewCosts)
            <a href="{{ route('client.pharma.inventory', ['cost_status'=>'unpriced']) }}" class="rounded-3xl border p-4 shadow-sm transition active:scale-[0.985] motion-reduce:transform-none {{ $filters['cost_status']==='unpriced' ? 'border-rose-400 bg-rose-100 ring-2 ring-rose-200' : 'border-rose-200 bg-rose-50' }}"><p class="text-xs font-bold text-rose-700">Chưa có giá vốn</p><p class="mt-2 text-2xl font-black text-rose-950">{{ number_format($summary['unpriced_count']) }}</p></a>
        @endif
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
        <form id="inventory-filter-form" method="GET" action="{{ route('client.pharma.inventory') }}" class="grid gap-3 lg:grid-cols-12 lg:items-start">
            <label class="min-w-0 lg:col-span-5">
                <span class="text-xs font-bold text-slate-500">Tìm thuốc</span>
                <div class="relative mt-1">
                    <input id="inventory-search-input" type="search" name="q" value="{{ $filters['q'] }}" autocomplete="off" placeholder="Tên thuốc / hoạt chất" class="w-full rounded-2xl border border-slate-300 bg-white py-3 pl-4 pr-11 text-sm shadow-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
                    @if($filters['q'])
                        <a href="{{ route('client.pharma.inventory', array_filter(['expiry'=>$filters['expiry'],'cost_status'=>$canViewCosts ? $filters['cost_status'] : null,'sort'=>$canViewCosts ? $filters['sort'] : null])) }}" aria-label="Xóa từ khóa tìm kiếm" class="absolute right-2 top-1/2 inline-flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full text-lg font-bold text-slate-400 hover:bg-slate-100 hover:text-slate-700">×</a>
                    @endif
                </div>
                <span class="mt-1.5 block text-xs text-slate-400">Kết quả tự cập nhật khi bạn nhập.</span>
            </label>

            <div class="lg:col-span-7">
                <details id="inventory-advanced-filters" class="group" {{ $hasFilters ? 'open' : '' }}>
                    <summary class="cursor-pointer py-2 text-sm font-bold text-slate-700">Bộ lọc nâng cao @if($hasFilters)<span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-500">Đang áp dụng</span>@endif</summary>
                    <div class="grid gap-2 pt-2 {{ $canViewCosts ? 'sm:grid-cols-2 lg:grid-cols-3' : '' }}">
                        <select name="expiry" onchange="this.form.submit()" class="rounded-2xl border border-slate-300 bg-white text-sm shadow-sm focus:border-slate-500 focus:ring-2 focus:ring-slate-200"><option value="">Tất cả hạn dùng</option>@foreach($expiryLabels as $value=>$label)<option value="{{ $value }}" @selected($filters['expiry']===$value)>{{ $label }}</option>@endforeach</select>
                        @if($canViewCosts)
                            <select name="cost_status" onchange="this.form.submit()" class="rounded-2xl border border-slate-300 bg-white text-sm shadow-sm focus:border-slate-500 focus:ring-2 focus:ring-slate-200"><option value="">Tất cả giá vốn</option><option value="priced" @selected($filters['cost_status']==='priced')>Có giá vốn</option><option value="unpriced" @selected($filters['cost_status']==='unpriced')>Chưa có giá vốn</option></select>
                            <select name="sort" onchange="this.form.submit()" class="rounded-2xl border border-slate-300 bg-white text-sm shadow-sm focus:border-slate-500 focus:ring-2 focus:ring-slate-200 sm:col-span-2 lg:col-span-1"><option value="">Hạn dùng gần nhất</option><option value="value_desc" @selected($filters['sort']==='value_desc')>Giá trị tồn lớn nhất</option><option value="value_asc" @selected($filters['sort']==='value_asc')>Giá trị tồn nhỏ nhất</option></select>
                        @endif
                    </div>
                </details>
            </div>
        </form>
        @if($hasFilters)
            <a href="{{ route('client.pharma.inventory') }}" class="mt-3 inline-flex text-sm font-bold text-slate-600">Xóa bộ lọc</a>
        @endif
    </section>

    <section id="inventory-mobile-list" class="grid gap-3 md:grid-cols-2 xl:hidden">
        @forelse($balances as $row)
            @php $expired=$row->expiry_date->isPast(); $near=!$expired && $row->expiry_date->lte(now()->addMonths(6)); @endphp
            <article data-inventory-card class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0"><h2 class="font-black text-slate-950">{{ $row->medicine->name }}</h2><p class="mt-1 text-xs leading-5 text-slate-500">{{ $row->medicine->active_ingredients ?: 'Chưa có hoạt chất' }}</p></div>
                    <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold {{ $expired ? 'bg-rose-100 text-rose-700' : ($near ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">{{ $expired ? 'Hết hạn' : ($near ? 'Sắp hết hạn' : 'Còn hạn') }}</span>
                </div>
                <p class="mt-2 text-xs text-slate-500">{{ $row->medicine->packaging_specification ?: 'Chưa có quy cách' }}</p>
                <p class="mt-2 text-xs font-medium text-slate-500">Lô <span class="font-bold text-slate-700">{{ $row->batch_number }}</span> · HSD <span class="font-bold text-slate-700">{{ $row->expiry_date->format('d/m/Y') }}</span></p>
                <dl class="mt-4 divide-y divide-slate-100 rounded-2xl bg-slate-50 px-3">
                    <div class="flex items-center justify-between gap-3 py-2.5"><dt class="text-xs font-bold text-slate-500">Tồn hiện tại</dt><dd class="text-base font-black text-slate-950">{{ rtrim(rtrim(number_format((float)$row->quantity_on_hand,3,'.',''),'0'),'.') }}</dd></div>
                    @if($canViewCosts)
                        <div class="flex items-center justify-between gap-3 py-2.5"><dt class="text-xs font-bold text-slate-500">Giá vốn TB</dt><dd class="text-sm font-bold text-slate-800">{{ $row->average_cost_price !== null ? $money($row->average_cost_price) : '—' }}</dd></div>
                        <div class="flex items-center justify-between gap-3 py-2.5"><dt class="text-xs font-bold text-slate-500">Giá trị tồn</dt><dd class="text-sm font-black text-slate-950">{{ $row->inventory_value !== null ? $money($row->inventory_value) : '—' }}</dd></div>
                    @endif
                </dl>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500 md:col-span-2">Không có tồn kho phù hợp bộ lọc.</div>
        @endforelse
    </section>

    <section class="hidden overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm xl:block">
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Tên thuốc</th><th class="px-4 py-3">Hoạt chất / Quy cách</th><th class="px-4 py-3">Số lô</th><th class="px-4 py-3">Hạn dùng</th><th class="px-4 py-3 text-right">Tồn hiện tại</th>@if($canViewCosts)<th class="px-4 py-3 text-right">Giá vốn TB</th><th class="px-4 py-3 text-right">Giá trị tồn</th>@endif</tr></thead><tbody id="inventory-desktop-body" class="divide-y divide-slate-100">@forelse($balances as $row)<tr data-inventory-row><td class="px-4 py-3 font-bold text-slate-950">{{ $row->medicine->name }}</td><td class="px-4 py-3"><div class="font-medium text-slate-700">{{ $row->medicine->active_ingredients ?: '—' }}</div><div class="mt-1 text-xs text-slate-400">{{ $row->medicine->packaging_specification ?: '—' }}</div></td><td class="px-4 py-3">{{ $row->batch_number }}</td><td class="px-4 py-3">{{ $row->expiry_date->format('d/m/Y') }}</td><td class="px-4 py-3 text-right font-black">{{ rtrim(rtrim(number_format((float)$row->quantity_on_hand,3,'.',''),'0'),'.') }}</td>@if($canViewCosts)<td class="px-4 py-3 text-right">{{ $row->average_cost_price !== null ? $money($row->average_cost_price) : '—' }}</td><td class="px-4 py-3 text-right font-black">{{ $row->inventory_value !== null ? $money($row->inventory_value) : '—' }}</td>@endif</tr>@empty<tr><td colspan="{{ $canViewCosts ? 7 : 5 }}" class="px-4 py-8 text-center text-slate-500">Không có tồn kho phù hợp bộ lọc.</td></tr>@endforelse</tbody></table></div>
    </section>

    @if($balances->hasMorePages())
        <div id="inventory-load-more-wrap" class="flex flex-col items-center gap-2 py-2">
            <div id="inventory-scroll-sentinel" class="h-px w-full" aria-hidden="true"></div>
            <a id="inventory-load-more" href="{{ $balances->nextPageUrl() }}" class="inline-flex min-h-11 items-center justify-center rounded-2xl border border-slate-300 bg-white px-6 py-2.5 text-sm font-black text-slate-700 shadow-sm active:scale-[0.985] motion-reduce:transform-none">Xem thêm</a>
            <p class="text-xs text-slate-400">Cuộn xuống để tự tải thêm.</p>
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('inventory-filter-form');
    const input = document.getElementById('inventory-search-input');
    if (form && input) {
        let timer;
        input.addEventListener('input', () => {
            window.clearTimeout(timer);
            timer = window.setTimeout(() => form.requestSubmit(), 350);
        });
    }

    const details = document.getElementById('inventory-advanced-filters');
    const desktop = window.matchMedia('(min-width: 1024px)');
    if (details && desktop.matches) details.open = true;

    const mobileList = document.getElementById('inventory-mobile-list');
    const desktopBody = document.getElementById('inventory-desktop-body');
    const wrap = document.getElementById('inventory-load-more-wrap');
    const more = document.getElementById('inventory-load-more');
    const sentinel = document.getElementById('inventory-scroll-sentinel');
    if (!wrap || !more) return;

    const loadMore = async () => {
        if (more.dataset.loading === '1') return;
        more.dataset.loading = '1';
        more.textContent = 'Đang tải…';
        more.setAttribute('aria-busy', 'true');

        try {
            const response = await fetch(more.href, {headers: {'X-Requested-With': 'XMLHttpRequest'}});
            if (!response.ok) throw new Error('load-more');
            const html = await response.text();
            const documentPage = new DOMParser().parseFromString(html, 'text/html');

            documentPage.querySelectorAll('#inventory-mobile-list [data-inventory-card]').forEach((card) => mobileList?.appendChild(card));
            documentPage.querySelectorAll('#inventory-desktop-body [data-inventory-row]').forEach((row) => desktopBody?.appendChild(row));

            const next = documentPage.getElementById('inventory-load-more');
            if (next) {
                more.href = next.href;
                more.textContent = 'Xem thêm';
                more.removeAttribute('aria-busy');
                more.dataset.loading = '0';
            } else {
                wrap.remove();
            }
        } catch (error) {
            more.textContent = 'Thử lại';
            more.removeAttribute('aria-busy');
            more.dataset.loading = '0';
        }
    };

    more.addEventListener('click', (event) => {
        event.preventDefault();
        loadMore();
    });

    if ('IntersectionObserver' in window && sentinel) {
        const observer = new IntersectionObserver((entries) => {
            if (entries.some((entry) => entry.isIntersecting)) loadMore();
        }, {rootMargin: '320px 0px'});
        observer.observe(sentinel);
    }
});
</script>
@endsection
