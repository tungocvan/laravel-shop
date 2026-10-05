@extends('ClientPortal::layouts.application')

@section('title', $featurePresentation['page_title'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Commission Ledger · chỉ đọc')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)

@section('content')
<div class="mb-4 flex flex-wrap items-center justify-between gap-2">
    <a href="{{ route('client.pharma.dashboard') }}" class="inline-flex min-h-11 items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition active:scale-[0.985] motion-reduce:transform-none">← Không gian làm việc Pharma</a>
    <a id="commission-export-excel" href="{{ route('client.pharma.commissions.export', array_filter(['source'=>$filters['source'],'from'=>$filters['from'],'to'=>$filters['to'],'partner_id'=>$filters['partner_id'],'manager_user_id'=>$filters['manager_user_id']])) }}" class="inline-flex min-h-11 items-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-black text-emerald-800 shadow-sm transition active:scale-[0.985] motion-reduce:transform-none">Xuất Excel</a>
</div>
<div id="commission-export-ready" class="mb-4 hidden rounded-2xl border border-emerald-200 bg-emerald-50 p-3 text-sm font-semibold text-emerald-900">
    <span data-commission-export-status>File Excel đã sẵn sàng.</span>
    <button type="button" data-commission-export-share class="ml-2 rounded-xl bg-emerald-800 px-3 py-2 font-black text-white transition active:scale-[0.985] motion-reduce:transform-none">Mở / Chia sẻ file</button>
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
        @php
            $hasCommissionFilters=$filters['source']!=='all'
                || !empty($filters['manager_user_id'])
                || !empty($filters['partner_id'])
                || $filters['from']!==now()->startOfMonth()->toDateString()
                || $filters['to']!==now()->toDateString();
        @endphp
        <nav class="flex items-center gap-2 overflow-x-auto pb-1" aria-label="Nguồn hoa hồng">
            @foreach($sources as $value=>$label)
                <a href="{{ route('client.pharma.commissions', array_filter(['source'=>$value==='all' ? null : $value,'from'=>$filters['from'],'to'=>$filters['to'],'partner_id'=>$filters['partner_id'],'manager_user_id'=>$filters['manager_user_id']])) }}" class="whitespace-nowrap rounded-full border px-4 py-2 text-sm font-black transition active:scale-[0.985] motion-reduce:transform-none {{ $filters['source']===$value ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 bg-white text-slate-600' }}">{{ $label }}</a>
            @endforeach
            @if($hasCommissionFilters)
                <a href="{{ route('client.pharma.commissions') }}" aria-label="Xóa bộ lọc" title="Xóa bộ lọc" class="ml-1 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-lg font-black text-slate-500 transition hover:bg-slate-100 active:scale-[0.96] motion-reduce:transform-none">↺</a>
            @endif
        </nav>

        <form id="commission-filter-form" method="GET" action="{{ route('client.pharma.commissions') }}" class="mt-4 min-w-0 space-y-3">
            <input type="hidden" name="source" value="{{ $filters['source'] }}">

            <div class="grid min-w-0 gap-3 {{ $canViewTeam ? 'lg:grid-cols-2' : 'lg:grid-cols-1' }}">
                @if($canViewTeam)
                    <label class="min-w-0">
                        <span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Người phụ trách</span>
                        <x-pwa-select-search id="commission-manager-user" name="manager_user_id" :selected="$filters['manager_user_id'] ?? ''" placeholder="Tất cả người phụ trách" search-placeholder="Tìm tên hoặc email..." data-pwa-select-search-submit="change">
                            <button type="button" data-pwa-select-search-option data-value="" data-label="Tất cả người phụ trách" data-search="tất cả người phụ trách" class="flex w-full items-center rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-700 hover:bg-slate-100">Tất cả người phụ trách</button>
                            @foreach($commissionUsers as $commissionUser)
                                @php
                                    $commissionUserLabel=$commissionUser->name.($commissionUser->email ? ' · '.$commissionUser->email : '');
                                @endphp
                                <button type="button" data-pwa-select-search-option data-value="{{ $commissionUser->id }}" data-label="{{ $commissionUserLabel }}" data-search="{{ $commissionUserLabel }}" class="flex w-full items-center rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-700 hover:bg-slate-100">{{ $commissionUserLabel }}</button>
                            @endforeach
                        </x-pwa-select-search>
                    </label>
                @endif

                <label class="min-w-0">
                    <span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Khách hàng</span>
                    <x-pwa-select-search id="commission-partner" name="partner_id" :selected="$filters['partner_id'] ?? ''" placeholder="Tất cả khách hàng" search-placeholder="Tìm khách hàng..." data-pwa-select-search-submit="change">
                        <button type="button" data-pwa-select-search-option data-value="" data-label="Tất cả khách hàng" data-search="tất cả khách hàng" class="flex w-full items-center rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-700 hover:bg-slate-100">Tất cả khách hàng</button>
                        @foreach($commissionPartners as $commissionPartner)
                            <button type="button" data-pwa-select-search-option data-value="{{ $commissionPartner->id }}" data-label="{{ $commissionPartner->name }}" data-search="{{ $commissionPartner->name }}" class="flex w-full items-center rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-700 hover:bg-slate-100">{{ $commissionPartner->name }}</button>
                        @endforeach
                    </x-pwa-select-search>
                </label>
            </div>

            <div class="grid min-w-0 grid-cols-1 gap-3 md:grid-cols-2">
                <div class="min-w-0">
                    <span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Từ ngày</span>
                    <button type="button" data-commission-date-trigger="from" class="relative flex h-[46px] w-full min-w-0 items-center rounded-2xl border border-slate-300 bg-white px-3 pr-11 text-left text-sm font-semibold text-slate-950 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
                        <span data-commission-date-label="from">{{ \Carbon\Carbon::parse($filters['from'])->format('d/m/Y') }}</span>
                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">▾</span>
                    </button>
                    <input type="date" name="from" value="{{ $filters['from'] }}" data-commission-date-picker="from" tabindex="-1" aria-hidden="true" class="pointer-events-none absolute h-px w-px opacity-0">
                </div>
                <div class="min-w-0">
                    <span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Đến ngày</span>
                    <button type="button" data-commission-date-trigger="to" class="relative flex h-[46px] w-full min-w-0 items-center rounded-2xl border border-slate-300 bg-white px-3 pr-11 text-left text-sm font-semibold text-slate-950 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
                        <span data-commission-date-label="to">{{ \Carbon\Carbon::parse($filters['to'])->format('d/m/Y') }}</span>
                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">▾</span>
                    </button>
                    <input type="date" name="to" value="{{ $filters['to'] }}" data-commission-date-picker="to" tabindex="-1" aria-hidden="true" class="pointer-events-none absolute h-px w-px opacity-0">
                </div>
            </div>
        </form>
    </section>

    <div id="commission-results" class="space-y-4">
        <div id="commission-mobile-list" class="space-y-3 md:hidden">
            @forelse($rows as $row)
                @php
                    $issue=$row->issue;
                    $customer=$issue?->recipientPartner?->name ?: $issue?->recipient_name ?: '—';
                    $manager=$issue?->manager?->name ?: '—';
                    $detailUrl=route('client.pharma.commissions.show',['issue'=>$row->issue_id]);
                @endphp
                <a data-commission-item href="{{ $detailUrl }}" class="block rounded-3xl border border-slate-200 bg-white p-4 shadow-sm transition active:scale-[0.985] motion-reduce:transform-none">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0"><p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ $issue?->issue_date?->format('d/m/Y') ?: $row->calculated_at?->format('d/m/Y') }}</p><h2 class="mt-1 truncate font-black text-slate-950">{{ $customer }}</h2><p class="mt-1 text-sm font-semibold text-slate-500">{{ $manager }}</p></div>
                        <span class="shrink-0 text-xl font-black text-slate-300">›</span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-3 border-t border-slate-100 pt-3">
                        <div><p class="text-[11px] font-bold uppercase text-slate-400">Tổng giá trị</p><p class="mt-1 font-black tabular-nums text-slate-950">{{ number_format((float)$row->revenue_amount,0,',','.') }} đ</p></div>
                        <div class="text-right"><p class="text-[11px] font-bold uppercase text-slate-400">Tổng hoa hồng</p><p class="mt-1 font-black tabular-nums {{ (float)$row->commission_amount<0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ number_format((float)$row->commission_amount,0,',','.') }} đ</p></div>
                    </div>
                </a>
            @empty
                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-10 text-center"><h2 class="font-black text-slate-800">Chưa có phiếu xuất phù hợp</h2><p class="mt-2 text-sm text-slate-500">Thử thay đổi khách hàng, khoảng ngày hoặc nguồn hoa hồng.</p></div>
            @endforelse
        </div>

        <div class="hidden overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm md:block">
            <table class="w-full table-fixed border-collapse text-left">
                <thead class="bg-slate-50 text-xs font-black uppercase tracking-wide text-slate-500">
                    <tr><th class="w-[14%] px-4 py-3">Ngày xuất</th><th class="w-[30%] px-4 py-3">Khách hàng</th><th class="w-[20%] px-4 py-3">Người phụ trách</th><th class="w-[18%] px-4 py-3 text-right">Tổng giá trị</th><th class="w-[18%] px-4 py-3 text-right">Tổng hoa hồng</th></tr>
                </thead>
                <tbody id="commission-list" class="divide-y divide-slate-100">
                    @forelse($rows as $row)
                        @php
                            $issue=$row->issue;
                            $customer=$issue?->recipientPartner?->name ?: $issue?->recipient_name ?: '—';
                            $manager=$issue?->manager?->name ?: '—';
                            $detailUrl=route('client.pharma.commissions.show',['issue'=>$row->issue_id]);
                        @endphp
                        <tr data-commission-item class="group transition hover:bg-slate-50">
                            <td class="px-4 py-4"><a href="{{ $detailUrl }}" class="block font-black text-slate-950">{{ $issue?->issue_date?->format('d/m/Y') ?: $row->calculated_at?->format('d/m/Y') }}</a></td>
                            <td class="px-4 py-4"><a href="{{ $detailUrl }}" class="block truncate font-bold text-slate-800">{{ $customer }}</a></td>
                            <td class="px-4 py-4"><a href="{{ $detailUrl }}" class="block truncate font-semibold text-slate-600">{{ $manager }}</a></td>
                            <td class="px-4 py-4 text-right"><a href="{{ $detailUrl }}" class="block font-black tabular-nums text-slate-950">{{ number_format((float)$row->revenue_amount,0,',','.') }} đ</a></td>
                            <td class="px-4 py-4 text-right"><a href="{{ $detailUrl }}" class="block font-black tabular-nums {{ (float)$row->commission_amount<0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ number_format((float)$row->commission_amount,0,',','.') }} đ <span class="text-slate-300">›</span></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center"><h2 class="font-black text-slate-800">Chưa có phiếu xuất phù hợp</h2><p class="mt-2 text-sm text-slate-500">Thử thay đổi khách hàng, khoảng ngày hoặc nguồn hoa hồng.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($rows->hasMorePages())
            <div id="commission-load-more-wrap" class="pt-1 text-center">
                <a id="commission-load-more" href="{{ $rows->nextPageUrl() }}" class="inline-flex min-h-12 w-full items-center justify-center rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-black text-slate-800 shadow-sm transition active:scale-[0.985] sm:w-auto motion-reduce:transform-none">Xem thêm</a>
                <p class="mt-2 text-xs font-semibold text-slate-400">Đã hiển thị {{ $rows->count() }} / {{ $rows->total() }}</p>
            </div>
        @endif
    </div>
</div>

<script>
(()=>{
    const exportLink=document.getElementById('commission-export-excel');
    const ready=document.getElementById('commission-export-ready');
    const shareButton=ready?.querySelector('[data-commission-export-share]');
    const status=ready?.querySelector('[data-commission-export-status]');
    let preparedFile=null;
    const standalone=window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone===true;
    if(exportLink && standalone){
        exportLink.addEventListener('click',async(event)=>{
            event.preventDefault();
            if(exportLink.getAttribute('aria-busy')==='true') return;
            exportLink.setAttribute('aria-busy','true');
            const original=exportLink.textContent;
            exportLink.textContent='Đang chuẩn bị…';
            try{
                const response=await fetch(exportLink.href,{credentials:'same-origin',cache:'no-store'});
                if(!response.ok) throw new Error('commission-export');
                const blob=await response.blob();
                preparedFile=new File([blob],'pharma-hoa-hong.xlsx',{type:blob.type||'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
                ready?.classList.remove('hidden');
                if(status) status.textContent='File Excel đã sẵn sàng.';
            }catch(error){
                ready?.classList.remove('hidden');
                if(status) status.textContent='Không thể chuẩn bị file. Vui lòng thử lại.';
                preparedFile=null;
            }finally{
                exportLink.textContent=original;
                exportLink.removeAttribute('aria-busy');
            }
        });
        shareButton?.addEventListener('click',async()=>{
            if(!preparedFile) return;
            const payload={files:[preparedFile]};
            if(typeof navigator.share==='function' && (!navigator.canShare || navigator.canShare(payload))){
                await navigator.share(payload);
                return;
            }
            if(status) status.textContent='Thiết bị này chưa hỗ trợ chia sẻ file trực tiếp. Hãy mở trang bằng trình duyệt để tải Excel.';
        });
    }
})();

window.syncCommissionDate=(input)=>{
    if(!input.value) return;
    const [year,month,day]=input.value.split('-');
    const label=document.querySelector('[data-commission-date-label="'+input.dataset.commissionDatePicker+'"]');
    if(label) label.textContent=day+'/'+month+'/'+year;
    input.form.requestSubmit();
};

document.querySelectorAll('[data-commission-date-trigger]').forEach((trigger)=>{
    trigger.addEventListener('click',()=>{
        const input=document.querySelector('[data-commission-date-picker="'+trigger.dataset.commissionDateTrigger+'"]');
        if(!input) return;
        if(typeof input.showPicker==='function') input.showPicker();
        else input.click();
    });
});

document.querySelectorAll('[data-commission-date-picker]').forEach((input)=>{
    input.addEventListener('change',()=>window.syncCommissionDate(input));
});
</script>
@endsection
