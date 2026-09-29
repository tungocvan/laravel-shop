@extends('ClientPortal::layouts.application')

@section('title', $featurePresentation['page_title'] ?? 'Tồn kho Pharma')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Workspace Pharma dành cho User')
@section('app-dashboard-route', route('client.pharma.dashboard'))

@section('content')
@php
    $money = fn ($value) => number_format((float) $value, 0, ',', '.').' đ';
    $expiryLabels = ['expired'=>'Đã hết hạn','lt1'=>'< 1 tháng','lt3'=>'< 3 tháng','lt6'=>'< 6 tháng','safe'=>'≥ 6 tháng'];
    $hasFilters = $filters['q'] || $filters['expiry'] || $filters['cost_status'] || $filters['sort'];
@endphp
<div class="min-w-0 space-y-4 overflow-x-hidden pb-24 xl:pb-0">
    <section class="rounded-[1.75rem] bg-slate-950 px-5 py-5 text-white shadow-sm sm:px-7 sm:py-6">
        <a href="{{ route('client.pharma.dashboard') }}" class="hidden text-sm font-bold text-slate-300 hover:text-white lg:inline-flex">← Quay về dashboard</a>
        <p class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-400 lg:mt-5">{{ $featurePresentation['eyebrow'] ?? 'Inventory' }}</p>
        <h1 class="mt-1.5 text-2xl font-black tracking-tight sm:text-3xl">{{ $featurePresentation['page_title'] ?? 'Tồn kho Pharma' }}</h1>
        <p class="mt-1.5 max-w-3xl text-sm leading-5 text-slate-300 sm:leading-6">{{ $featurePresentation['page_description'] ?? 'Theo dõi số lượng tồn, giá trị, lô và hạn dùng.' }}</p>
    </section>

    <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-bold text-slate-500">Lô đang còn hàng</p><p class="mt-2 text-2xl font-black text-slate-950">{{ number_format($summary['balance_count']) }}</p></div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-bold text-slate-500">Giá trị tồn</p><p class="mt-2 text-lg font-black text-slate-950 sm:text-2xl">{{ $money($summary['inventory_value']) }}</p></div>
        <a href="{{ route('client.pharma.inventory', ['expiry'=>'lt6']) }}" class="rounded-3xl border p-4 shadow-sm transition active:scale-[0.985] motion-reduce:transform-none {{ $filters['expiry']==='lt6' ? 'border-amber-400 bg-amber-100 ring-2 ring-amber-200' : 'border-amber-200 bg-amber-50' }}"><p class="text-xs font-bold text-amber-700">Sắp hết hạn ≤ 6 tháng</p><p class="mt-2 text-2xl font-black text-amber-950">{{ number_format($summary['near_expiry_count']) }}</p></a>
        <a href="{{ route('client.pharma.inventory', ['cost_status'=>'unpriced']) }}" class="rounded-3xl border p-4 shadow-sm transition active:scale-[0.985] motion-reduce:transform-none {{ $filters['cost_status']==='unpriced' ? 'border-rose-400 bg-rose-100 ring-2 ring-rose-200' : 'border-rose-200 bg-rose-50' }}"><p class="text-xs font-bold text-rose-700">Chưa có giá vốn</p><p class="mt-2 text-2xl font-black text-rose-950">{{ number_format($summary['unpriced_count']) }}</p></a>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
        <form id="inventory-filter-form" method="GET" action="{{ route('client.pharma.inventory') }}" class="grid gap-3 lg:grid-cols-12 lg:items-start">
            <label class="min-w-0 lg:col-span-5">
                <span class="text-xs font-bold text-slate-500">Tìm thuốc</span>
                <div class="relative mt-1">
                    <input id="inventory-search-input" type="search" name="q" value="{{ $filters['q'] }}" autocomplete="off" placeholder="Tên thuốc / mã thuốc" class="w-full rounded-2xl border-slate-300 py-3 pl-4 pr-11 text-sm">
                    @if($filters['q'])
                        <a href="{{ route('client.pharma.inventory', array_filter(['expiry'=>$filters['expiry'],'cost_status'=>$filters['cost_status'],'sort'=>$filters['sort'],'per_page'=>$filters['per_page']])) }}" aria-label="Xóa từ khóa tìm kiếm" class="absolute right-2 top-1/2 inline-flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full text-lg font-bold text-slate-400 hover:bg-slate-100 hover:text-slate-700">×</a>
                    @endif
                </div>
                <span class="mt-1.5 block text-xs text-slate-400">Kết quả tự cập nhật khi bạn nhập.</span>
            </label>

            <div class="lg:col-span-7">
                <details id="inventory-advanced-filters" class="group" {{ $hasFilters ? 'open' : '' }}>
                    <summary class="cursor-pointer py-2 text-sm font-bold text-slate-700">Bộ lọc nâng cao @if($hasFilters)<span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-500">Đang áp dụng</span>@endif</summary>
                    <div class="grid gap-2 pt-2 sm:grid-cols-2 lg:grid-cols-3">
                        <select name="expiry" onchange="this.form.submit()" class="rounded-2xl border-slate-300 text-sm"><option value="">Tất cả hạn dùng</option>@foreach($expiryLabels as $value=>$label)<option value="{{ $value }}" @selected($filters['expiry']===$value)>{{ $label }}</option>@endforeach</select>
                        <select name="cost_status" onchange="this.form.submit()" class="rounded-2xl border-slate-300 text-sm"><option value="">Tất cả giá vốn</option><option value="priced" @selected($filters['cost_status']==='priced')>Có giá vốn</option><option value="unpriced" @selected($filters['cost_status']==='unpriced')>Chưa có giá vốn</option></select>
                        <select name="sort" onchange="this.form.submit()" class="rounded-2xl border-slate-300 text-sm sm:col-span-2 lg:col-span-1"><option value="">Hạn dùng gần nhất</option><option value="value_desc" @selected($filters['sort']==='value_desc')>Giá trị tồn lớn nhất</option><option value="value_asc" @selected($filters['sort']==='value_asc')>Giá trị tồn nhỏ nhất</option></select>
                    </div>
                </details>
            </div>
            <input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">
        </form>
        @if($hasFilters)
            <a href="{{ route('client.pharma.inventory', ['per_page'=>$filters['per_page']]) }}" class="mt-3 inline-flex text-sm font-bold text-slate-600">Xóa bộ lọc</a>
        @endif
    </section>

    <section id="inventory-mobile-list" class="grid gap-3 md:grid-cols-2 xl:hidden">
        @forelse($balances as $row)
            @php $expired=$row->expiry_date->isPast(); $near=!$expired && $row->expiry_date->lte(now()->addMonths(6)); @endphp
            <article data-inventory-card class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0"><p class="text-xs font-bold text-slate-400">{{ $row->medicine->medicine_code }}</p><h2 class="mt-1 truncate font-black text-slate-950">{{ $row->medicine->name }}</h2></div>
                    <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold {{ $expired ? 'bg-rose-100 text-rose-700' : ($near ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">{{ $expired ? 'Hết hạn' : ($near ? 'Sắp hết hạn' : 'Còn hạn') }}</span>
                </div>
                <p class="mt-2 text-xs font-medium text-slate-500">Lô <span class="font-bold text-slate-700">{{ $row->batch_number }}</span> · HSD <span class="font-bold text-slate-700">{{ $row->expiry_date->format('d/m/Y') }}</span></p>
                <dl class="mt-4 divide-y divide-slate-100 rounded-2xl bg-slate-50 px-3">
                    <div class="flex items-center justify-between gap-3 py-2.5"><dt class="text-xs font-bold text-slate-500">Tồn hiện tại</dt><dd class="text-base font-black text-slate-950">{{ rtrim(rtrim(number_format((float)$row->quantity_on_hand,3,'.',''),'0'),'.') }}</dd></div>
                    <div class="flex items-center justify-between gap-3 py-2.5"><dt class="text-xs font-bold text-slate-500">Giá vốn TB</dt><dd class="text-sm font-bold text-slate-800">{{ $row->average_cost_price !== null ? $money($row->average_cost_price) : '—' }}</dd></div>
                    <div class="flex items-center justify-between gap-3 py-2.5"><dt class="text-xs font-bold text-slate-500">Giá trị tồn</dt><dd class="text-sm font-black text-slate-950">{{ $row->inventory_value !== null ? $money($row->inventory_value) : '—' }}</dd></div>
                </dl>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500 md:col-span-2">Không có tồn kho phù hợp bộ lọc.</div>
        @endforelse
    </section>

    @if($balances->hasMorePages())
        <div id="inventory-load-more-wrap" class="flex justify-center xl:hidden">
            <a id="inventory-load-more" href="{{ $balances->nextPageUrl() }}" class="inline-flex min-h-11 items-center justify-center rounded-2xl border border-slate-300 bg-white px-6 py-2.5 text-sm font-black text-slate-700 shadow-sm active:scale-[0.985] motion-reduce:transform-none">Xem thêm</a>
        </div>
    @endif

    <section class="hidden overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm xl:block">
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Mã thuốc</th><th class="px-4 py-3">Tên thuốc</th><th class="px-4 py-3">Số lô</th><th class="px-4 py-3">Hạn dùng</th><th class="px-4 py-3 text-right">Tồn hiện tại</th><th class="px-4 py-3 text-right">Giá vốn TB</th><th class="px-4 py-3 text-right">Giá trị tồn</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($balances as $row)<tr><td class="px-4 py-3 font-bold text-slate-700">{{ $row->medicine->medicine_code }}</td><td class="px-4 py-3 font-bold text-slate-950">{{ $row->medicine->name }}</td><td class="px-4 py-3">{{ $row->batch_number }}</td><td class="px-4 py-3">{{ $row->expiry_date->format('d/m/Y') }}</td><td class="px-4 py-3 text-right font-black">{{ rtrim(rtrim(number_format((float)$row->quantity_on_hand,3,'.',''),'0'),'.') }}</td><td class="px-4 py-3 text-right">{{ $row->average_cost_price !== null ? $money($row->average_cost_price) : '—' }}</td><td class="px-4 py-3 text-right font-black">{{ $row->inventory_value !== null ? $money($row->inventory_value) : '—' }}</td></tr>@empty<tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">Không có tồn kho phù hợp bộ lọc.</td></tr>@endforelse</tbody></table></div>
    </section>

    <div class="hidden xl:flex xl:items-center xl:justify-between">
        <form method="GET" action="{{ route('client.pharma.inventory') }}" class="flex items-center gap-2">@foreach(request()->except(['per_page','page']) as $key=>$value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach<label class="text-sm font-bold text-slate-500">Hiển thị</label><select name="per_page" onchange="this.form.submit()" class="rounded-xl border-slate-300 text-sm">@foreach([25,50,100] as $size)<option value="{{ $size }}" @selected($filters['per_page']===$size)>{{ $size }} / trang</option>@endforeach</select></form>
        <div>{{ $balances->links() }}</div>
    </div>
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

    const list = document.getElementById('inventory-mobile-list');
    const wrap = document.getElementById('inventory-load-more-wrap');
    const more = document.getElementById('inventory-load-more');
    if (!list || !wrap || !more) return;

    more.addEventListener('click', async (event) => {
        if (window.matchMedia('(min-width: 1280px)').matches) return;
        event.preventDefault();
        if (more.dataset.loading === '1') return;

        more.dataset.loading = '1';
        more.textContent = 'Đang tải…';
        more.setAttribute('aria-busy', 'true');

        try {
            const response = await fetch(more.href, {headers: {'X-Requested-With': 'XMLHttpRequest'}});
            if (!response.ok) throw new Error('load-more');
            const html = await response.text();
            const documentPage = new DOMParser().parseFromString(html, 'text/html');
            documentPage.querySelectorAll('#inventory-mobile-list [data-inventory-card]').forEach((card) => list.appendChild(card));
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
    });
});
</script>
@endsection
