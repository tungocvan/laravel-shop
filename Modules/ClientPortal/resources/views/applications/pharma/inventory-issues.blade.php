@extends('ClientPortal::layouts.application')

@section('title', 'Đơn hàng')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Workspace Pharma dành cho User')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-mobile-navigation', true)
@section('hide-application-header', true)

@section('content')
@php
    $money = fn ($value) => number_format((float) $value, 0, ',', '.').' đ';
    $statusLabels = ['draft'=>'Nháp','pending_approval'=>'Chờ duyệt','approved'=>'Đã duyệt','rejected'=>'Từ chối','posted'=>'Đã xuất','cancelled'=>'Đã hủy'];
    $sourceLabels = ['normal'=>'Theo bảng giá','bid'=>'Theo kết quả trúng thầu'];
    $hasFilters = $filters['status'] || $filters['source'] || $filters['from_date'] || $filters['to_date'] || $filters['manager_user_id'];
@endphp

<div class="min-w-0 max-w-full overflow-x-hidden min-h-[calc(100vh-5rem)] bg-slate-50 pb-24 lg:pb-8" data-inventory-issues-workspace>
    <header class="sticky top-0 z-30 -mx-4 border-b border-slate-200 bg-white/95 px-4 py-4 backdrop-blur sm:-mx-6 sm:px-6 lg:static lg:mx-0 lg:rounded-3xl lg:border lg:px-6">
        <div class="relative flex items-center justify-center">
            <a href="{{ route('client.pharma.dashboard') }}" class="absolute left-0 inline-flex h-11 w-11 items-center justify-center rounded-full text-2xl text-slate-900 transition active:scale-95" aria-label="Quay lại">←</a>
            <h1 class="px-12 text-center text-xl font-black tracking-tight text-slate-950 sm:text-2xl">Đơn hàng / Phiếu xuất</h1>
        </div>
    </header>

    <section class="mt-4">
        <div class="flex items-center gap-2">
        <form id="issue-search-form" method="GET" action="{{ route('client.pharma.orders') }}" class="flex min-w-0 flex-1 gap-2 lg:max-w-[620px]">
            @foreach(['status','source','from_date','to_date','manager_user_id'] as $key)
                @if($filters[$key])<input type="hidden" name="{{ $key }}" value="{{ $filters[$key] }}">@endif
            @endforeach
            <label class="relative min-w-0 flex-1">
                <span class="sr-only">Tìm kiếm đơn hàng</span>
                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-xl text-slate-500">⌕</span>
                <input id="issue-search-input" name="q" value="{{ $filters['q'] }}" placeholder="Tìm đơn hàng / khách hàng / bệnh viện" data-pwa-debounced-search="600" data-pwa-search-region="#orders-search-region" data-pwa-search-clear="#issue-search-clear" class="h-14 w-full rounded-2xl border border-slate-300 bg-white pl-12 pr-11 text-[15px] font-medium text-slate-900 placeholder:text-[13px] placeholder:font-normal outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
                <button id="issue-search-clear" type="button" data-pwa-search-clear-button="#issue-search-input" class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full px-2 py-1 text-xl text-slate-500 {{ $filters['q'] ? '' : 'hidden' }}" aria-label="Xóa từ khóa tìm kiếm">×</button>
            </label>
            <button id="issue-filter-toggle" type="button" class="relative inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-slate-300 bg-white text-xl text-slate-700 active:scale-95" aria-controls="issue-filter-sheet" aria-expanded="false">
                ⏷
                @if($hasFilters)<span class="absolute right-2 top-2 h-2.5 w-2.5 rounded-full bg-rose-600"></span>@endif
            </button>
        </form>
        @if($canCreateOrders)
            <a href="{{ route('client.pharma.orders.create') }}" class="ml-auto hidden h-14 shrink-0 items-center justify-center gap-2 rounded-2xl bg-slate-950 px-5 text-sm font-black text-white shadow-sm transition active:scale-[0.985] lg:inline-flex">
                <span class="text-base font-light leading-none">+</span><span>Lập đơn hàng</span>
            </a>
        @endif
        </div>
    </section>

    @php
        $activeStatus = (string) ($filters['status'] ?? '');
        $railStatuses = [''=>'Tất cả','draft'=>'Nháp','pending_approval'=>'Chờ duyệt','approved'=>'Đã duyệt','rejected'=>'Từ chối','posted'=>'Đã xuất','cancelled'=>'Đã hủy'];
    @endphp
    <nav class="mt-3 flex w-full max-w-full snap-x snap-proximity items-center gap-1.5 overflow-x-auto overscroll-x-contain pb-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
         data-order-status-bar aria-label="Trạng thái đơn hàng">
        @foreach($railStatuses as $value=>$label)
            @php($statusCount = $value==='' ? $counts['all'] : ($counts[$value] ?? 0))
            @if($statusCount > 0 || $activeStatus===$value)
                <a href="{{ route('client.pharma.orders', array_filter(['q'=>$filters['q'],'status'=>$value,'source'=>$filters['source'],'from_date'=>$filters['from_date'],'to_date'=>$filters['to_date'],'manager_user_id'=>$filters['manager_user_id']], fn($v)=>$v!=='' && $v!==null)) }}"
                   class="inline-flex h-7 w-fit shrink-0 snap-start items-center whitespace-nowrap rounded-full border px-2.5 text-[11px] font-bold {{ $activeStatus===$value ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 bg-white text-slate-600' }}">
                    {{ $label }} <span class="ml-1 opacity-70">{{ $statusCount }}</span>
                </a>
            @else
                <span aria-disabled="true" data-disabled-status class="inline-flex h-7 w-fit shrink-0 cursor-not-allowed snap-start items-center whitespace-nowrap rounded-full border border-slate-100 bg-slate-50 px-2.5 text-[11px] font-bold text-slate-300">
                    {{ $label }} <span class="ml-1">{{ $statusCount }}</span>
                </span>
            @endif
        @endforeach
    </nav>

    @if($canApproveOrders && ($counts['pending_approval'] ?? 0) > 0)
        <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-900">
            Có {{ number_format($counts['pending_approval']) }} đơn đang chờ phê duyệt. Chọn trạng thái “Chờ duyệt” để xử lý.
        </div>
    @endif

    <div id="orders-search-region">
    <section id="issue-mobile-list" class="mt-4 grid min-w-0 max-w-full grid-cols-1 gap-3 md:grid-cols-2 xl:hidden">
        @forelse($issues as $issue)
            <article data-issue-card class="min-w-0 max-w-full overflow-hidden rounded-3xl border border-slate-200 bg-white p-4 shadow-sm transition active:scale-[0.985] motion-reduce:transform-none">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="break-all text-xs font-black uppercase tracking-wide text-slate-500">{{ $issue->number }}</p>
                        <h2 class="mt-1 line-clamp-2 break-words text-base font-black leading-5 text-slate-950">{{ $issue->recipient_name ?: 'Chưa xác định nơi nhận' }}</h2>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">@if($issue->shortage_note)<button type="button" data-shortage-note="{{ $issue->shortage_note }}" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-amber-200 bg-amber-50 text-sm text-amber-800" aria-label="Xem ghi chú thiếu hàng">📝</button>@endif<span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-black {{ $issue->status==='posted' ? 'bg-emerald-100 text-emerald-800' : ($issue->status==='cancelled' ? 'bg-slate-200 text-slate-600' : 'bg-amber-100 text-amber-800') }}">{{ $statusLabels[$issue->status] ?? $issue->status }}</span></div>
                </div>
                <p class="mt-3 break-words text-sm font-bold text-slate-700">{{ $sourceLabels[$issue->issue_source ?? 'normal'] ?? 'Theo bảng giá' }} · {{ number_format($issue->items_count) }} sản phẩm</p>
                <div class="mt-3 flex items-end justify-between gap-3 border-t border-slate-100 pt-3">
                    <div><p class="text-xs text-slate-500">Ngày lập</p><p class="mt-0.5 text-sm font-bold text-slate-800">{{ $issue->issue_date?->format('d/m/Y') }}</p></div>
                    <div class="text-right"><p class="text-xs text-slate-500">Tổng tiền</p><p class="mt-0.5 text-base font-black text-slate-950">{{ $money($issue->total_value ?? 0) }}</p></div>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3">
                    @if($issue->status === \Modules\Pharma\Models\InventoryIssue::POSTED)
                        @if($orderPdfActions[$issue->id]['ready'] ?? false)
                            <a href="{{ route('client.pharma.orders.pdf',$issue) }}" data-order-pdf-download class="inline-flex min-h-10 items-center rounded-xl border border-slate-300 bg-white px-3 text-xs font-black text-slate-700">↓ PDF</a>
                            <a href="{{ route('client.pharma.orders.print',$issue) }}" target="_blank" rel="noopener" class="inline-flex min-h-10 items-center rounded-xl border border-slate-300 bg-white px-3 text-xs font-black text-slate-700">In</a>
                            @if($orderPdfActions[$issue->id]['share'] ?? null)
                                <button type="button" data-copy-order-share="{{ $orderPdfActions[$issue->id]['share']['url'] }}" class="inline-flex min-h-10 items-center rounded-xl border border-slate-300 bg-white px-3 text-xs font-black text-slate-700">Sao chép link</button>
                                <form method="POST" action="{{ route('client.pharma.orders.share.revoke',[$issue,$orderPdfActions[$issue->id]['share']['id']]) }}">@csrf @method('DELETE')<button class="inline-flex min-h-10 items-center rounded-xl border border-rose-200 bg-rose-50 px-3 text-xs font-black text-rose-700 transition active:scale-[0.985] motion-reduce:transform-none">Thu hồi</button></form>
                            @else
                                <form method="POST" action="{{ route('client.pharma.orders.share',$issue) }}">@csrf<button class="inline-flex min-h-10 items-center rounded-xl border border-slate-300 bg-white px-3 text-xs font-black text-slate-700">Chia sẻ</button></form>
                            @endif
                        @else
                            <form method="POST" action="{{ route('client.pharma.orders.pdf.export',$issue) }}">@csrf<button class="inline-flex min-h-10 items-center rounded-xl bg-slate-950 px-3 text-xs font-black text-white">Xuất PDF</button></form>
                        @endif
                    @endif
                    <a href="{{ route('client.pharma.orders.show',$issue->id) }}" class="ml-auto inline-flex min-h-10 items-center text-xs font-black text-slate-600">Chi tiết ›</a>
                </div>
            </article>
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
            <thead class="bg-slate-50 text-xs font-black uppercase tracking-wide text-slate-500"><tr><th class="w-[17%] px-5 py-4">Số phiếu</th><th class="w-[27%] px-5 py-4">Khách hàng / bệnh viện</th><th class="w-[18%] px-5 py-4">Nguồn</th><th class="w-[13%] px-5 py-4">Ngày lập</th><th class="w-[12%] px-5 py-4 text-right">Tổng tiền</th><th class="w-[8%] px-5 py-4 text-center">Ghi chú</th><th class="w-[10%] px-5 py-4">Trạng thái</th><th class="w-[8%] px-5 py-4 text-right"><span class="sr-only">PDF</span></th></tr></thead>
            <tbody id="issue-desktop-body" class="divide-y divide-slate-100">
                @foreach($issues as $issue)
                    <tr data-issue-row class="hover:bg-slate-50"><td class="px-5 py-4"><a class="font-black text-slate-950" href="{{ route('client.pharma.orders.show',$issue->id) }}">{{ $issue->number }}</a></td><td class="px-5 py-4 font-bold text-slate-800">{{ $issue->recipient_name ?: '—' }}</td><td class="px-5 py-4">{{ $sourceLabels[$issue->issue_source ?? 'normal'] ?? 'Theo bảng giá' }}</td><td class="px-5 py-4">{{ $issue->issue_date?->format('d/m/Y') }}</td><td class="px-5 py-4 text-right font-black">{{ $money($issue->total_value ?? 0) }}</td><td class="px-5 py-4 text-center">@if($issue->shortage_note)<button type="button" data-shortage-note="{{ $issue->shortage_note }}" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-amber-200 bg-amber-50 text-base text-amber-800" aria-label="Xem ghi chú thiếu hàng" title="Xem ghi chú thiếu hàng">📝</button>@else<span class="text-slate-300">—</span>@endif</td><td class="px-5 py-4 font-bold">{{ $statusLabels[$issue->status] ?? $issue->status }}</td><td class="px-5 py-4 text-right">@if($issue->status === \Modules\Pharma\Models\InventoryIssue::POSTED) @if($orderPdfActions[$issue->id]['ready'] ?? false)<a href="{{ route('client.pharma.orders.pdf',$issue) }}" data-order-pdf-download class="inline-flex min-h-9 items-center rounded-lg border border-slate-300 px-2.5 text-xs font-black">↓ PDF</a>@else<form method="POST" action="{{ route('client.pharma.orders.pdf.export',$issue) }}">@csrf<button class="rounded-lg border border-slate-300 px-2.5 py-2 text-xs font-black">Xuất PDF</button></form>@endif @else<span class="text-slate-300">—</span>@endif</td></tr>
                @endforeach
            </tbody>
        </table>
    </section>

    @if($issues->hasMorePages())
        <div id="issue-load-more-wrap" class="mt-5 text-center"><a id="issue-load-more" href="{{ $issues->nextPageUrl() }}" data-pwa-load-more data-pwa-load-more-target="#issue-mobile-list" data-pwa-load-more-items="#issue-mobile-list [data-issue-card]" data-pwa-load-more-wrap="#issue-load-more-wrap" data-pwa-pending-label="Đang tải…" class="inline-flex min-h-12 items-center justify-center rounded-2xl border border-slate-300 bg-white px-6 text-sm font-black text-slate-800">Xem thêm</a></div>
    @endif
</div>

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
                @if($canApproveOrders)
                    <label class="block">
                        <span class="mb-2 block text-sm font-black text-slate-800">Người phụ trách</span>
                        <select name="manager_user_id" data-order-manager-filter class="h-13 w-full rounded-2xl border border-slate-300 bg-white px-4 text-base">
                            <option value="">Tất cả User phụ trách</option>
                            @foreach($managerOptions as $manager)
                                <option value="{{ $manager->id }}" @selected((int)$filters['manager_user_id']===(int)$manager->id)>{{ $manager->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-xs text-slate-500">Mặc định hiển thị đơn hàng của tất cả User trong phạm vi quản trị.</p>
                    </label>
                @endif
                <label class="block"><span class="mb-2 block text-sm font-black text-slate-800">Nguồn đơn hàng</span><select name="source" class="h-13 w-full rounded-2xl border border-slate-300 bg-white px-4 text-base"><option value="">Tất cả</option><option value="normal" @selected($filters['source']==='normal')>Theo bảng giá</option><option value="bid" @selected($filters['source']==='bid')>Theo kết quả trúng thầu</option></select></label>
                <label class="block"><span class="mb-2 block text-sm font-black text-slate-800">Trạng thái</span><select name="status" class="h-13 w-full rounded-2xl border border-slate-300 bg-white px-4 text-base"><option value="">Tất cả</option>@foreach($statusLabels as $value=>$label)<option value="{{ $value }}" @selected($filters['status']===$value)>{{ $label }}</option>@endforeach</select></label>
                <div><span class="mb-2 block text-sm font-black text-slate-800">Ngày lập đơn</span><div class="grid grid-cols-2 gap-3"><input type="date" name="from_date" value="{{ $filters['from_date'] }}" class="h-13 min-w-0 rounded-2xl border border-slate-300 px-3"><input type="date" name="to_date" value="{{ $filters['to_date'] }}" class="h-13 min-w-0 rounded-2xl border border-slate-300 px-3"></div></div>
            </div>
            <div class="mt-6 grid grid-cols-2 gap-3 border-t border-slate-100 pt-4"><button id="issue-filter-cancel" type="button" class="h-13 rounded-2xl border border-slate-300 font-black text-slate-700">Hủy</button><button type="submit" class="h-13 rounded-2xl bg-slate-950 font-black text-white">Áp dụng</button></div>
        </form>
    </aside>
    @if($canCreateOrders)
        <a href="{{ route('client.pharma.orders.create') }}" data-create-order class="order-create-action lg:hidden" aria-label="Lập đơn hàng"><span class="order-create-plus">+</span></a>
    @endif
</div>

<style>
.order-create-action{position:fixed!important;right:18px!important;bottom:calc(86px + env(safe-area-inset-bottom,0px))!important;z-index:45;display:inline-flex;width:44px;height:44px;align-items:center;justify-content:center;border-radius:9999px;background:#020617;color:#fff;box-shadow:0 8px 22px rgb(15 23 42 / .18);text-decoration:none}
.order-create-plus{font-size:20px;font-weight:300;line-height:1}
@media (min-width:1024px){.order-create-action{display:none!important}}
</style>
<dialog id="shortage-note-dialog" class="w-[calc(100%-16px)] max-w-[520px] rounded-[26px] border-0 p-0 shadow-2xl backdrop:bg-slate-950/55">
    <div class="p-4 sm:p-5"><div class="flex items-center justify-between gap-3"><div><p class="text-xs font-black uppercase tracking-wide text-amber-700">Thiếu hàng</p><h2 class="mt-1 text-lg font-black text-slate-950">Ghi chú cung ứng</h2></div><button type="button" data-shortage-close class="h-10 w-10 rounded-full bg-slate-100 text-xl text-slate-700" aria-label="Đóng">×</button></div><p id="shortage-note-content" class="mt-4 whitespace-pre-line rounded-2xl bg-amber-50 p-4 text-sm font-medium leading-6 text-amber-950"></p></div>
</dialog>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const preparedOrderPdfs=new Map();
    const standaloneOrder=window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone===true;
    document.addEventListener('click',async(event)=>{
        const copy=event.target.closest('[data-copy-order-share]');
        if(copy){try{await navigator.clipboard.writeText(copy.dataset.copyOrderShare);const old=copy.textContent;copy.textContent='Đã sao chép';setTimeout(()=>copy.textContent=old,1600);}catch(e){window.prompt('Sao chép link chia sẻ:',copy.dataset.copyOrderShare);}return;}
        const link=event.target.closest('[data-order-pdf-download]');
        if(!link||!standaloneOrder)return;
        event.preventDefault();
        const cached=preparedOrderPdfs.get(link.href);
        if(cached&&navigator.canShare?.({files:[cached]})){await navigator.share({files:[cached],title:'Phiếu xuất kho'});return;}
        const response=await fetch(link.href,{credentials:'same-origin',cache:'no-store'});
        if(!response.ok){window.location.href=link.href;return;}
        const blob=await response.blob(), file=new File([blob],'phieu-xuat-kho.pdf',{type:'application/pdf'});
        preparedOrderPdfs.set(link.href,file);
        if(navigator.canShare?.({files:[file]})){link.textContent='Mở / lưu PDF';return;}
        const objectUrl=URL.createObjectURL(blob);window.open(objectUrl,'_blank','noopener');window.setTimeout(()=>URL.revokeObjectURL(objectUrl),60000);
    });
    const createAction=document.querySelector('[data-create-order]');
    if(createAction && window.matchMedia('(max-width: 1023px)').matches) document.body.appendChild(createAction);
    const shortageDialog=document.getElementById('shortage-note-dialog'), shortageContent=document.getElementById('shortage-note-content');
    const centerShortageDialog=()=>{if(!shortageDialog)return;shortageDialog.style.position='fixed';shortageDialog.style.inset='50% auto auto 50%';shortageDialog.style.margin='0';shortageDialog.style.transform='translate(-50%, -50%)';shortageDialog.style.maxHeight='calc(100dvh - 24px)';};
    document.addEventListener('click',(event)=>{const button=event.target.closest('[data-shortage-note]');if(!button)return;event.preventDefault();event.stopPropagation();if(shortageContent)shortageContent.textContent=button.dataset.shortageNote||'';centerShortageDialog();shortageDialog?.showModal();});
    document.querySelector('[data-shortage-close]')?.addEventListener('click',()=>shortageDialog?.close());
    const toggle = document.getElementById('issue-filter-toggle');
    const sheet = document.getElementById('issue-filter-sheet');
    const backdrop = document.getElementById('issue-filter-backdrop');
    const closeButtons = [document.getElementById('issue-filter-close'), document.getElementById('issue-filter-cancel'), backdrop];
    const openSheet = () => { sheet?.classList.remove('hidden'); backdrop?.classList.remove('hidden'); sheet?.setAttribute('aria-hidden','false'); toggle?.setAttribute('aria-expanded','true'); document.body.classList.add('overflow-hidden'); };
    const closeSheet = () => { sheet?.classList.add('hidden'); backdrop?.classList.add('hidden'); sheet?.setAttribute('aria-hidden','true'); toggle?.setAttribute('aria-expanded','false'); document.body.classList.remove('overflow-hidden'); };
    toggle?.addEventListener('click', openSheet); closeButtons.forEach((button) => button?.addEventListener('click', closeSheet));


});
</script>
@endsection
