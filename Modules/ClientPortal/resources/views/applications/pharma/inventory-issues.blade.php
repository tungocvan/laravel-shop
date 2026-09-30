@extends('ClientPortal::layouts.application')

@section('title', 'Đơn hàng')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Workspace Pharma dành cho User')
@section('app-dashboard-route', route('client.pharma.dashboard'))

@section('content')
@php
    $money = fn ($value) => number_format((float) $value, 0, ',', '.').' đ';
    $statusLabels = ['draft'=>'Nháp','pending_approval'=>'Chờ duyệt','posted'=>'Đã xuất','cancelled'=>'Đã hủy'];
    $sourceLabels = ['normal'=>'Theo bảng giá','bid'=>'Theo kết quả trúng thầu'];
    $hasFilters = $filters['status'] || $filters['source'] || $filters['from_date'] || $filters['to_date'];
@endphp

<div class="min-w-0 max-w-full overflow-x-hidden min-h-[calc(100vh-5rem)] bg-slate-50 pb-24 lg:pb-8" data-inventory-issues-workspace>
    <header class="sticky top-0 z-30 -mx-4 border-b border-slate-200 bg-white/95 px-4 py-4 backdrop-blur sm:-mx-6 sm:px-6 lg:static lg:mx-0 lg:rounded-3xl lg:border lg:px-6">
        <div class="relative flex items-center justify-center">
            <a href="{{ route('client.pharma.inventory') }}" class="absolute left-0 inline-flex h-11 w-11 items-center justify-center rounded-full text-2xl text-slate-900 transition active:scale-95" aria-label="Quay lại">←</a>
            <h1 class="px-12 text-center text-xl font-black tracking-tight text-slate-950 sm:text-2xl">Đơn hàng / Phiếu xuất</h1>
        </div>
    </header>

    <section class="mt-4">
        <form id="issue-search-form" method="GET" action="{{ route('client.pharma.orders') }}" class="flex gap-2">
            @foreach(['status','source','from_date','to_date'] as $key)
                @if($filters[$key])<input type="hidden" name="{{ $key }}" value="{{ $filters[$key] }}">@endif
            @endforeach
            <label class="relative min-w-0 flex-1">
                <span class="sr-only">Tìm kiếm đơn hàng</span>
                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-xl text-slate-500">⌕</span>
                <input id="issue-search-input" name="q" value="{{ $filters['q'] }}" placeholder="Tìm đơn hàng / khách hàng / bệnh viện" class="h-14 w-full rounded-2xl border border-slate-300 bg-white pl-12 pr-11 text-[15px] font-medium text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
                @if($filters['q'])
                    <a href="{{ route('client.pharma.orders', array_filter(['status'=>$filters['status'],'source'=>$filters['source'],'from_date'=>$filters['from_date'],'to_date'=>$filters['to_date']])) }}" class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full px-2 py-1 text-xl text-slate-500" aria-label="Xóa từ khóa tìm kiếm">×</a>
                @endif
            </label>
            <button id="issue-filter-toggle" type="button" class="relative inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-slate-300 bg-white text-xl text-slate-700 active:scale-95" aria-controls="issue-filter-sheet" aria-expanded="false">
                ⏷
                @if($hasFilters)<span class="absolute right-2 top-2 h-2.5 w-2.5 rounded-full bg-rose-600"></span>@endif
            </button>
        </form>
    </section>

    <nav class="mt-4 flex max-w-full gap-2 overflow-x-auto overscroll-x-contain pb-1 [scrollbar-width:none]" aria-label="Trạng thái đơn hàng">
        @foreach([''=>'Tất cả','draft'=>'Nháp','pending_approval'=>'Chờ duyệt','posted'=>'Đã xuất','cancelled'=>'Đã hủy'] as $value=>$label)
            <a href="{{ route('client.pharma.orders', array_filter(['q'=>$filters['q'],'status'=>$value,'source'=>$filters['source'],'from_date'=>$filters['from_date'],'to_date'=>$filters['to_date']], fn($v)=>$v!=='' && $v!==null)) }}"
               class="shrink-0 rounded-full border px-4 py-2 text-sm font-bold {{ $filters['status']===$value ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 bg-white text-slate-600' }}">
                {{ $label }} <span class="ml-1 opacity-70">{{ $value==='' ? $counts['all'] : ($counts[$value] ?? 0) }}</span>
            </a>
        @endforeach
        @if($filters['q'] || $hasFilters)
            <a data-clear-order-filters href="{{ route('client.pharma.orders') }}" class="shrink-0 rounded-full border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-bold text-rose-700 active:scale-95">
                Xóa bộ lọc
            </a>
        @endif
    </nav>

    <section id="issue-mobile-list" class="mt-4 grid min-w-0 max-w-full grid-cols-1 gap-3 md:grid-cols-2 xl:hidden">
        @forelse($issues as $issue)
            <a data-issue-card href="{{ route('client.pharma.orders.show', $issue->id) }}" class="min-w-0 max-w-full overflow-hidden rounded-3xl border border-slate-200 bg-white p-4 shadow-sm transition active:scale-[0.985] motion-reduce:transform-none">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="break-all text-xs font-black uppercase tracking-wide text-slate-500">{{ $issue->number }}</p>
                        <h2 class="mt-1 line-clamp-2 break-words text-base font-black leading-5 text-slate-950">{{ $issue->recipient_name ?: 'Chưa xác định nơi nhận' }}</h2>
                    </div>
                    <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-black {{ $issue->status==='posted' ? 'bg-emerald-100 text-emerald-800' : ($issue->status==='cancelled' ? 'bg-slate-200 text-slate-600' : 'bg-amber-100 text-amber-800') }}">{{ $statusLabels[$issue->status] ?? $issue->status }}</span>
                </div>
                <p class="mt-3 break-words text-sm font-bold text-slate-700">{{ $sourceLabels[$issue->issue_source ?? 'normal'] ?? 'Theo bảng giá' }} · {{ number_format($issue->items_count) }} sản phẩm</p>
                <div class="mt-3 flex items-end justify-between gap-3 border-t border-slate-100 pt-3">
                    <div><p class="text-xs text-slate-500">Ngày lập</p><p class="mt-0.5 text-sm font-bold text-slate-800">{{ $issue->issue_date?->format('d/m/Y') }}</p></div>
                    <div class="text-right"><p class="text-xs text-slate-500">Tổng tiền</p><p class="mt-0.5 text-base font-black text-slate-950">{{ $money($issue->total_value ?? 0) }}</p></div>
                </div>
            </a>
        @empty
            <div class="col-span-full flex min-h-[52vh] flex-col items-center justify-center px-6 text-center">
                <div class="flex h-20 w-20 items-center justify-center rounded-3xl bg-slate-100 text-4xl text-slate-400">≡</div>
                <h2 class="mt-5 text-xl font-black text-slate-900">Không có dữ liệu phù hợp!</h2>
                <p class="mt-2 text-sm text-slate-500">Vui lòng kiểm tra lại từ khóa hoặc bộ lọc.</p>
            </div>
        @endforelse
    </section>

    <section class="mt-4 hidden overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm xl:block">
        <table class="w-full table-fixed text-left text-sm">
            <thead class="bg-slate-50 text-xs font-black uppercase tracking-wide text-slate-500"><tr><th class="w-[17%] px-5 py-4">Số phiếu</th><th class="w-[27%] px-5 py-4">Khách hàng / bệnh viện</th><th class="w-[18%] px-5 py-4">Nguồn</th><th class="w-[13%] px-5 py-4">Ngày lập</th><th class="w-[13%] px-5 py-4 text-right">Tổng tiền</th><th class="w-[12%] px-5 py-4">Trạng thái</th></tr></thead>
            <tbody id="issue-desktop-body" class="divide-y divide-slate-100">
                @foreach($issues as $issue)
                    <tr data-issue-row class="hover:bg-slate-50"><td class="px-5 py-4"><a class="font-black text-slate-950" href="{{ route('client.pharma.orders.show',$issue->id) }}">{{ $issue->number }}</a></td><td class="px-5 py-4 font-bold text-slate-800">{{ $issue->recipient_name ?: '—' }}</td><td class="px-5 py-4">{{ $sourceLabels[$issue->issue_source ?? 'normal'] ?? 'Theo bảng giá' }}</td><td class="px-5 py-4">{{ $issue->issue_date?->format('d/m/Y') }}</td><td class="px-5 py-4 text-right font-black">{{ $money($issue->total_value ?? 0) }}</td><td class="px-5 py-4 font-bold">{{ $statusLabels[$issue->status] ?? $issue->status }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </section>

    @if($issues->hasMorePages())
        <div id="issue-load-more-wrap" class="mt-5 text-center"><a id="issue-load-more" href="{{ $issues->nextPageUrl() }}" class="inline-flex min-h-12 items-center justify-center rounded-2xl border border-slate-300 bg-white px-6 text-sm font-black text-slate-800">Xem thêm</a><div id="issue-load-more-sentinel" class="h-1"></div></div>
    @endif

    <div id="issue-filter-backdrop" class="fixed inset-0 z-40 hidden bg-slate-950/55"></div>
    <aside id="issue-filter-sheet" class="fixed inset-x-0 bottom-0 z-50 hidden rounded-t-[2rem] bg-white shadow-2xl lg:inset-0 lg:m-auto lg:h-fit lg:max-h-[calc(100vh-3rem)] lg:w-[34rem] lg:overflow-y-auto lg:rounded-[2rem]" aria-hidden="true">
        <div class="mx-auto mt-2 h-1.5 w-16 rounded-full bg-slate-300"></div>
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <a href="{{ route('client.pharma.orders', array_filter(['q'=>$filters['q']])) }}" class="text-sm font-bold text-rose-600">Xóa lọc</a>
            <h2 class="text-lg font-black text-slate-950">Lọc đơn hàng</h2>
            <button id="issue-filter-close" type="button" class="h-10 w-10 text-2xl text-slate-700" aria-label="Đóng bộ lọc">×</button>
        </div>
        <form method="GET" action="{{ route('client.pharma.orders') }}" class="px-5 pb-[max(1.25rem,env(safe-area-inset-bottom))] pt-5">
            @if($filters['q'])<input type="hidden" name="q" value="{{ $filters['q'] }}">@endif
            <div class="space-y-5">
                <label class="block"><span class="mb-2 block text-sm font-black text-slate-800">Nguồn đơn hàng</span><select name="source" class="h-13 w-full rounded-2xl border border-slate-300 bg-white px-4 text-base"><option value="">Tất cả</option><option value="normal" @selected($filters['source']==='normal')>Theo bảng giá</option><option value="bid" @selected($filters['source']==='bid')>Theo kết quả trúng thầu</option></select></label>
                <label class="block"><span class="mb-2 block text-sm font-black text-slate-800">Trạng thái</span><select name="status" class="h-13 w-full rounded-2xl border border-slate-300 bg-white px-4 text-base"><option value="">Tất cả</option>@foreach($statusLabels as $value=>$label)<option value="{{ $value }}" @selected($filters['status']===$value)>{{ $label }}</option>@endforeach</select></label>
                <div><span class="mb-2 block text-sm font-black text-slate-800">Ngày lập đơn</span><div class="grid grid-cols-2 gap-3"><input type="date" name="from_date" value="{{ $filters['from_date'] }}" class="h-13 min-w-0 rounded-2xl border border-slate-300 px-3"><input type="date" name="to_date" value="{{ $filters['to_date'] }}" class="h-13 min-w-0 rounded-2xl border border-slate-300 px-3"></div></div>
            </div>
            <div class="mt-6 grid grid-cols-2 gap-3 border-t border-slate-100 pt-4"><button id="issue-filter-cancel" type="button" class="h-13 rounded-2xl border border-slate-300 font-black text-slate-700">Hủy</button><button type="submit" class="h-13 rounded-2xl bg-slate-950 font-black text-white">Áp dụng</button></div>
        </form>
    </aside>
    @if($canCreateOrders)
        <a href="{{ route('client.pharma.orders.create') }}" data-create-order class="fixed bottom-24 right-5 z-30 inline-flex h-14 w-14 items-center justify-center rounded-full bg-slate-950 text-3xl font-light text-white shadow-xl transition active:scale-95 lg:bottom-8 lg:right-8" aria-label="Thêm mới đơn hàng">+</a>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const search = document.getElementById('issue-search-input');
    const searchForm = document.getElementById('issue-search-form');
    let timer;
    search?.addEventListener('input', () => { window.clearTimeout(timer); timer = window.setTimeout(() => searchForm.requestSubmit(), 350); });

    const toggle = document.getElementById('issue-filter-toggle');
    const sheet = document.getElementById('issue-filter-sheet');
    const backdrop = document.getElementById('issue-filter-backdrop');
    const closeButtons = [document.getElementById('issue-filter-close'), document.getElementById('issue-filter-cancel'), backdrop];
    const openSheet = () => { sheet?.classList.remove('hidden'); backdrop?.classList.remove('hidden'); sheet?.setAttribute('aria-hidden','false'); toggle?.setAttribute('aria-expanded','true'); document.body.classList.add('overflow-hidden'); };
    const closeSheet = () => { sheet?.classList.add('hidden'); backdrop?.classList.add('hidden'); sheet?.setAttribute('aria-hidden','true'); toggle?.setAttribute('aria-expanded','false'); document.body.classList.remove('overflow-hidden'); };
    toggle?.addEventListener('click', openSheet); closeButtons.forEach((button) => button?.addEventListener('click', closeSheet));

    const more = document.getElementById('issue-load-more');
    const mobile = document.getElementById('issue-mobile-list');
    const desktop = document.getElementById('issue-desktop-body');
    const sentinel = document.getElementById('issue-load-more-sentinel');
    const loadMore = async () => {
        if (!more || more.dataset.loading === '1') return;
        more.dataset.loading='1'; more.textContent='Đang tải...';
        try {
            const response=await fetch(more.href,{headers:{'X-Requested-With':'XMLHttpRequest'}});
            if(!response.ok) throw new Error('load-more');
            const doc=new DOMParser().parseFromString(await response.text(),'text/html');
            doc.querySelectorAll('#issue-mobile-list [data-issue-card]').forEach((node)=>mobile?.appendChild(node));
            doc.querySelectorAll('#issue-desktop-body [data-issue-row]').forEach((node)=>desktop?.appendChild(node));
            const next=doc.getElementById('issue-load-more');
            if(next){more.href=next.href;more.textContent='Xem thêm';more.dataset.loading='0';}else{document.getElementById('issue-load-more-wrap')?.remove();}
        } catch(e){more.textContent='Thử lại';more.dataset.loading='0';}
    };
    more?.addEventListener('click',(event)=>{event.preventDefault();loadMore();});
    if(more && sentinel && 'IntersectionObserver' in window){new IntersectionObserver((entries)=>{if(entries.some((entry)=>entry.isIntersecting))loadMore();},{rootMargin:'320px 0px'}).observe(sentinel);}
});
</script>
@endsection
