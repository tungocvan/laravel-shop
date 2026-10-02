@extends('ClientPortal::layouts.application')

@section('title', $featurePresentation['page_title'] ?? 'Tồn kho Pharma')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Workspace Pharma dành cho User')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)

@section('content')
@php
    $money = fn ($value) => number_format((float) $value, 0, ',', '.').' đ';
    $expiryLabels = ['expired'=>'Đã hết hạn','lt1'=>'< 1 tháng','lt3'=>'< 3 tháng','lt6'=>'< 6 tháng','safe'=>'≥ 6 tháng'];
    $hasFilters = $filters['q'] || $filters['expiry'] || ($canViewCosts && ($filters['cost_status'] || $filters['sort']));
@endphp
<div class="min-w-0 space-y-4 overflow-x-hidden pb-8">
    <section class="rounded-[1.75rem] bg-slate-950 px-5 py-5 text-white shadow-sm sm:px-7 sm:py-6">
        <a href="{{ route('client.pharma.dashboard') }}" aria-label="Quay lại Không gian làm việc Pharma" class="inline-flex min-h-10 items-center rounded-xl px-1 text-sm font-bold text-slate-300 hover:text-white">← Quay về dashboard</a>
        <p class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-400 mt-2">{{ $featurePresentation['eyebrow'] ?? 'Inventory' }}</p>
        <h1 class="mt-1.5 text-2xl font-black tracking-tight sm:text-3xl">{{ $featurePresentation['page_title'] ?? 'Tồn kho Pharma' }}</h1>
        <p class="mt-1.5 max-w-3xl text-sm leading-5 text-slate-300 sm:leading-6">{{ $featurePresentation['page_description'] ?? 'Theo dõi số lượng tồn, giá trị, lô và hạn dùng.' }}</p>
    </section>

    @if($canViewCosts)
        <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                <p class="text-xs font-black uppercase tracking-wide text-emerald-700">Giá trị tồn theo giá vốn</p>
                <p class="mt-3 text-2xl font-black text-emerald-950">{{ $money($summary['inventory_value']) }}</p>
                <p class="mt-1 text-xs leading-5 text-emerald-700">Tổng giá trị tồn, bao gồm cả hàng còn hạn và đã hết hạn.</p>
                <div class="mt-4 border-t border-emerald-200 pt-3">
                    <div class="flex items-center justify-between gap-3"><span class="text-xs font-bold text-emerald-800">Trong đó còn hạn</span><strong class="whitespace-nowrap text-sm text-emerald-950">{{ $money($summary['valid_value']) }}</strong></div>
                    <p class="mt-1 text-xs text-emerald-700">{{ number_format($summary['valid_count']) }} lô còn tồn chưa quá hạn.</p>
                </div>
            </div>
            <a href="{{ route('client.pharma.inventory', ['cost_status'=>'unpriced']) }}" class="rounded-3xl border border-amber-200 bg-amber-50 p-5 shadow-sm transition active:scale-[0.985] motion-reduce:transform-none">
                <p class="text-xs font-black uppercase tracking-wide text-amber-700">Lô chưa định giá</p><p class="mt-3 text-2xl font-black text-amber-950">{{ number_format($summary['unpriced_count']) }}</p><p class="mt-1 text-xs leading-5 text-amber-700">Các lô còn tồn chưa có giá vốn điều chỉnh và giá vốn NCC đang hiệu lực.</p>
            </a>
            <a href="{{ route('client.pharma.inventory', ['expiry'=>'lt6']) }}" class="rounded-3xl border border-orange-200 bg-orange-50 p-5 shadow-sm transition active:scale-[0.985] motion-reduce:transform-none">
                <p class="text-xs font-black uppercase tracking-wide text-orange-700">Giá trị hàng cận hạn ≤ 6 tháng</p><p class="mt-3 whitespace-nowrap text-2xl font-black text-orange-950">{{ $money($summary['near_expiry_value']) }}</p><p class="mt-1 text-xs leading-5 text-orange-700">{{ number_format($summary['near_expiry_count']) }} lô còn tồn · Không tính hàng đã hết hạn.</p>
            </a>
            <a href="{{ route('client.pharma.inventory', ['expiry'=>'expired']) }}" class="rounded-3xl border border-rose-200 bg-rose-50 p-5 shadow-sm transition active:scale-[0.985] motion-reduce:transform-none">
                <p class="text-xs font-black uppercase tracking-wide text-rose-700">Hàng hết hạn còn tồn</p><p class="mt-3 whitespace-nowrap text-2xl font-black text-rose-950">{{ $money($summary['expired_value']) }}</p><p class="mt-1 text-xs leading-5 text-rose-700">{{ number_format($summary['expired_count']) }} lô còn tồn đã quá hạn dùng.</p>
            </a>
        </section>
    @else
        <section class="grid grid-cols-2 gap-3">
            <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-bold text-slate-500">Lô đang còn hàng</p><p class="mt-2 text-2xl font-black text-slate-950">{{ number_format($summary['balance_count']) }}</p></div>
            <a href="{{ route('client.pharma.inventory', ['expiry'=>'lt6']) }}" class="rounded-3xl border border-amber-200 bg-amber-50 p-4 shadow-sm transition active:scale-[0.985] motion-reduce:transform-none"><p class="text-xs font-bold text-amber-700">Sắp hết hạn ≤ 6 tháng</p><p class="mt-2 text-2xl font-black text-amber-950">{{ number_format($summary['near_expiry_count']) }}</p></a>
        </section>
    @endif

    @if(auth('web')->user()?->can('client.pharma.inventory.receipts'))
        <a href="{{ route('client.pharma.inventory.receipts') }}" class="flex items-center justify-between gap-4 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm transition active:scale-[0.985] motion-reduce:transform-none">
            <div><p class="font-black text-slate-950">Phiếu nhập kho</p><p class="mt-1 text-xs text-slate-500">Tra cứu phiếu nhập · Chi tiết lô hàng · Chỉ đọc</p></div><span class="text-xl text-slate-400">›</span>
        </a>
    @endif

    <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
        <form id="inventory-filter-form" method="GET" action="{{ route('client.pharma.inventory') }}">
            <div class="grid gap-x-4 gap-y-3 lg:grid-cols-12">
                <label class="min-w-0 lg:col-span-4">
                    <span class="flex h-7 items-center text-xs font-bold text-slate-500">Tìm thuốc</span>
                    <div class="relative mt-1">
                        <input id="inventory-search-input" type="search" name="q" value="{{ $filters['q'] }}" autocomplete="off" placeholder="Tên thuốc / hoạt chất" class="h-12 w-full rounded-2xl border border-slate-300 bg-white px-4 pr-11 text-sm shadow-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
                        @if($filters['q'])
                            <a href="{{ route('client.pharma.inventory', array_filter(['expiry'=>$filters['expiry'],'cost_status'=>$canViewCosts ? $filters['cost_status'] : null,'sort'=>$canViewCosts ? $filters['sort'] : null])) }}" aria-label="Xóa từ khóa tìm kiếm" class="absolute right-2 top-1/2 inline-flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full text-lg font-bold text-slate-400 hover:bg-slate-100 hover:text-slate-700">×</a>
                        @endif
                    </div>
                </label>

                <div class="min-w-0 lg:col-span-8">
                    <div class="flex h-7 items-center gap-2">
                        <button id="inventory-filter-toggle" type="button" aria-expanded="{{ $hasFilters ? 'true' : 'false' }}" class="inline-flex items-center gap-1 text-xs font-bold text-slate-500 lg:pointer-events-none">
                            <span class="lg:hidden">▾</span><span>Bộ lọc nâng cao</span>
                        </button>
                        @if($hasFilters)
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-500">Đang áp dụng</span>
                            <a href="{{ route('client.pharma.inventory') }}" class="ml-auto inline-flex items-center rounded-full bg-rose-50 px-2.5 py-1 text-[11px] font-black text-rose-700 ring-1 ring-inset ring-rose-200 transition hover:bg-rose-100">× Xóa bộ lọc</a>
                        @endif
                    </div>
                    <div id="inventory-filter-panel" class="mt-1 grid gap-2 {{ $hasFilters ? '' : 'hidden' }} sm:grid-cols-2 lg:grid lg:grid-cols-3">
                        <select name="expiry" onchange="this.form.submit()" class="h-12 rounded-2xl border border-slate-300 bg-white px-4 text-sm shadow-sm focus:border-slate-500 focus:ring-2 focus:ring-slate-200"><option value="">Tất cả hạn dùng</option>@foreach($expiryLabels as $value=>$label)<option value="{{ $value }}" @selected($filters['expiry']===$value)>{{ $label }}</option>@endforeach</select>
                        @if($canViewCosts)
                            <select name="cost_status" onchange="this.form.submit()" class="h-12 rounded-2xl border border-slate-300 bg-white px-4 text-sm shadow-sm focus:border-slate-500 focus:ring-2 focus:ring-slate-200"><option value="">Tất cả giá vốn</option><option value="priced" @selected($filters['cost_status']==='priced')>Có giá vốn</option><option value="unpriced" @selected($filters['cost_status']==='unpriced')>Chưa có giá vốn</option></select>
                            <select name="sort" onchange="this.form.submit()" class="h-12 rounded-2xl border border-slate-300 bg-white px-4 text-sm shadow-sm focus:border-slate-500 focus:ring-2 focus:ring-slate-200 sm:col-span-2 lg:col-span-1"><option value="">Hạn dùng gần nhất</option><option value="value_desc" @selected($filters['sort']==='value_desc')>Giá trị tồn lớn nhất</option><option value="value_asc" @selected($filters['sort']==='value_asc')>Giá trị tồn nhỏ nhất</option></select>
                        @endif
                    </div>
                </div>
            </div>
            <p class="mt-2 text-xs text-slate-400">Kết quả tự cập nhật khi bạn nhập.</p>
        </form>
    </section>

    <section id="inventory-mobile-list" class="grid gap-3 md:grid-cols-2 xl:hidden">
        @forelse($balances as $row)
            @php $expired=$row->expiry_date->isPast(); $near=!$expired && $row->expiry_date->lte(now()->addMonths(6)); @endphp
            <a data-inventory-card href="{{ route('client.pharma.inventory.balances.show', $row) }}" class="block rounded-3xl border border-slate-200 bg-white p-4 shadow-sm transition active:scale-[0.985] motion-reduce:transform-none">
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
                <div class="mt-3 flex items-center justify-end gap-2 text-xs font-black text-slate-600"><span>Xem biến động lô</span><span aria-hidden="true">›</span></div>
            </a>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500 md:col-span-2">Không có tồn kho phù hợp bộ lọc.</div>
        @endforelse
    </section>

    <section class="hidden overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm xl:block">
        <div class="overflow-x-auto"><table class="w-full table-fixed divide-y divide-slate-200 text-sm"><colgroup><col class="w-[14%]"><col class="w-[27%]"><col class="w-[10%]"><col class="w-[11%]"><col class="w-[10%]">@if($canViewCosts)<col class="w-[13%]"><col class="w-[15%]">@endif</colgroup><thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Tên thuốc</th><th class="px-4 py-3">Hoạt chất / Quy cách</th><th class="px-4 py-3 whitespace-nowrap">Số lô</th><th class="px-4 py-3 whitespace-nowrap">Hạn dùng</th><th class="px-4 py-3 text-right">Tồn hiện tại</th>@if($canViewCosts)<th class="px-4 py-3 text-right whitespace-nowrap">Giá vốn TB</th><th class="px-4 py-3 text-right whitespace-nowrap">Giá trị tồn</th>@endif</tr></thead><tbody id="inventory-desktop-body" class="divide-y divide-slate-100">@forelse($balances as $row)<tr data-inventory-row class="group"><td class="px-4 py-3 font-bold text-slate-950"><a href="{{ route('client.pharma.inventory.balances.show', $row) }}" class="inline-flex items-center gap-2 hover:underline">{{ $row->medicine->name }}<span class="text-slate-300 transition group-hover:translate-x-0.5">›</span></a></td><td class="px-4 py-3"><div class="font-medium text-slate-700">{{ $row->medicine->active_ingredients ?: '—' }}</div><div class="mt-1 text-xs text-slate-400">{{ $row->medicine->packaging_specification ?: '—' }}</div></td><td class="px-4 py-3 whitespace-nowrap">{{ $row->batch_number }}</td><td class="px-4 py-3 whitespace-nowrap">{{ $row->expiry_date->format('d/m/Y') }}</td><td class="px-4 py-3 text-right font-black whitespace-nowrap">{{ rtrim(rtrim(number_format((float)$row->quantity_on_hand,3,'.',''),'0'),'.') }}</td>@if($canViewCosts)<td class="px-4 py-3 text-right whitespace-nowrap">{{ $row->average_cost_price !== null ? $money($row->average_cost_price) : '—' }}</td><td class="px-4 py-3 text-right font-black whitespace-nowrap">{{ $row->inventory_value !== null ? $money($row->inventory_value) : '—' }}</td>@endif</tr>@empty<tr><td colspan="{{ $canViewCosts ? 7 : 5 }}" class="px-4 py-8 text-center text-slate-500">Không có tồn kho phù hợp bộ lọc.</td></tr>@endforelse</tbody></table></div>
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

    const filterToggle = document.getElementById('inventory-filter-toggle');
    const filterPanel = document.getElementById('inventory-filter-panel');
    if (filterToggle && filterPanel) {
        filterToggle.addEventListener('click', () => {
            if (window.matchMedia('(min-width: 1024px)').matches) return;
            const isHidden = filterPanel.classList.toggle('hidden');
            filterToggle.setAttribute('aria-expanded', isHidden ? 'false' : 'true');
        });
    }

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
