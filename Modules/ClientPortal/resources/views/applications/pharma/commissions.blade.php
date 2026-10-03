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
                <a href="{{ route('client.pharma.commissions', array_filter(['source'=>$value==='all' ? null : $value,'from'=>$filters['from'],'to'=>$filters['to'],'q'=>$filters['q'],'manager_user_id'=>$filters['manager_user_id']])) }}" class="whitespace-nowrap rounded-full border px-4 py-2 text-sm font-black transition active:scale-[0.985] motion-reduce:transform-none {{ $filters['source']===$value ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 bg-white text-slate-600' }}">{{ $label }}</a>
            @endforeach
        </nav>

        <form id="commission-filter-form" method="GET" action="{{ route('client.pharma.commissions') }}" class="mt-4 grid min-w-0 gap-3 {{ $canViewTeam ? 'lg:grid-cols-[220px_minmax(0,1fr)_160px_160px_auto]' : 'lg:grid-cols-[minmax(0,1fr)_160px_160px_auto]' }} lg:items-end">
            @if($canViewTeam)
                <label class="min-w-0">
                    <span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Người phụ trách</span>
                    <x-pwa-select-search
                        id="commission-manager-user"
                        name="manager_user_id"
                        :selected="$filters['manager_user_id'] ?? ''"
                        placeholder="Tất cả người phụ trách"
                        search-placeholder="Tìm tên hoặc email..."
                        data-pwa-select-search-submit="change"
                    >
                        <button type="button" data-pwa-select-search-option data-value="" data-label="Tất cả người phụ trách" data-search="tất cả người phụ trách" class="flex w-full items-center rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-700 hover:bg-slate-100">Tất cả người phụ trách</button>
                        @foreach($commissionUsers as $commissionUser)
                            @php
                                $commissionUserLabel = $commissionUser->name.($commissionUser->email ? ' · '.$commissionUser->email : '');
                            @endphp
                            <button type="button" data-pwa-select-search-option data-value="{{ $commissionUser->id }}" data-label="{{ $commissionUserLabel }}" data-search="{{ $commissionUserLabel }}" class="flex w-full items-center rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-700 hover:bg-slate-100">{{ $commissionUserLabel }}</button>
                        @endforeach
                    </x-pwa-select-search>
                </label>
            @endif
            <input type="hidden" name="source" value="{{ $filters['source'] }}">
            <label class="relative min-w-0">
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Tìm kiếm</span>
                <input id="commission-search-input" data-pwa-debounced-search="800" data-pwa-search-region="#commission-results" data-pwa-search-clear="#commission-search-clear" type="search" name="q" value="{{ $filters['q'] }}" autocomplete="off" placeholder="Thuốc, mã thuốc, khách hàng, số phiếu..." class="h-[46px] w-full rounded-2xl border border-slate-300 px-4 pr-11 text-sm text-slate-950 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
                <button id="commission-search-clear" data-pwa-search-clear-button="#commission-search-input" type="button" aria-label="Xóa tìm kiếm hoa hồng" class="absolute bottom-[7px] right-1.5 inline-flex h-8 w-8 items-center justify-center rounded-full text-lg font-bold text-slate-400 hover:bg-slate-100 hover:text-slate-700 {{ $filters['q']==='' ? 'hidden' : '' }}">×</button>
            </label>
            <label class="min-w-0"><span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Từ ngày</span><span class="relative block h-[46px] min-w-0 overflow-hidden rounded-2xl border border-slate-300 bg-white"><span data-commission-date-label="from" class="pointer-events-none flex h-full items-center px-3 pr-11 text-sm font-semibold text-slate-950">{{ \Carbon\Carbon::parse($filters['from'])->format('d/m/Y') }}</span><span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">▾</span><input type="date" name="from" value="{{ $filters['from'] }}" data-commission-date-picker="from" aria-label="Chọn từ ngày" class="absolute inset-0 h-full w-full cursor-pointer opacity-0" style="min-width:100%;max-width:100%;" onchange="window.syncCommissionDate(this)"></span></label>
            <label class="min-w-0"><span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Đến ngày</span><span class="relative block h-[46px] min-w-0 overflow-hidden rounded-2xl border border-slate-300 bg-white"><span data-commission-date-label="to" class="pointer-events-none flex h-full items-center px-3 pr-11 text-sm font-semibold text-slate-950">{{ \Carbon\Carbon::parse($filters['to'])->format('d/m/Y') }}</span><span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">▾</span><input type="date" name="to" value="{{ $filters['to'] }}" data-commission-date-picker="to" aria-label="Chọn đến ngày" class="absolute inset-0 h-full w-full cursor-pointer opacity-0" style="min-width:100%;max-width:100%;" onchange="window.syncCommissionDate(this)"></span></label>
            <a href="{{ route('client.pharma.commissions') }}" class="inline-flex h-[46px] items-center justify-center rounded-2xl border border-slate-300 bg-white px-4 text-sm font-black text-slate-700">Xóa bộ lọc</a>
        </form>
    </section>

    <div id="commission-results" class="space-y-4">
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <table class="w-full border-collapse text-left">
                <thead class="hidden bg-slate-50 text-xs font-black uppercase tracking-wide text-slate-500 md:table-header-group">
                    <tr>
                        <th class="px-4 py-3">Ngày xuất</th>
                        <th class="px-4 py-3">Khách hàng</th>
                        <th class="px-4 py-3">Người phụ trách</th>
                        <th class="px-4 py-3 text-right">Tổng giá trị</th>
                        <th class="px-4 py-3 text-right">Tổng hoa hồng</th>
                    </tr>
                </thead>
                <tbody id="commission-list" class="block divide-y divide-slate-200 md:table-row-group">
                    @forelse($rows as $row)
                        @php
                            $issue=$row->issue;
                            $customer=$issue?->recipientPartner?->name ?: $issue?->recipient_name ?: '—';
                            $manager=$issue?->manager?->name ?: '—';
                            $detailUrl=route('client.pharma.commissions.show',['issue'=>$row->issue_id]);
                        @endphp
                        <tr data-commission-item class="block p-4 transition active:scale-[0.985] md:table-row md:p-0 motion-reduce:transform-none">
                            <td class="block md:table-cell md:px-4 md:py-4">
                                <a href="{{ $detailUrl }}" class="flex items-center justify-between gap-3 md:block">
                                    <span class="text-xs font-bold uppercase tracking-wide text-slate-400 md:hidden">Ngày xuất</span>
                                    <span class="font-black text-slate-950">{{ $issue?->issue_date?->format('d/m/Y') ?: $row->calculated_at?->format('d/m/Y') }}</span>
                                </a>
                            </td>
                            <td class="mt-2 block md:mt-0 md:table-cell md:px-4 md:py-4">
                                <a href="{{ $detailUrl }}" class="flex items-start justify-between gap-3 md:block">
                                    <span class="shrink-0 text-xs font-bold uppercase tracking-wide text-slate-400 md:hidden">Khách hàng</span>
                                    <span class="min-w-0 text-right font-bold text-slate-800 md:text-left">{{ $customer }}</span>
                                </a>
                            </td>
                            <td class="mt-2 block md:mt-0 md:table-cell md:px-4 md:py-4">
                                <a href="{{ $detailUrl }}" class="flex items-center justify-between gap-3 md:block">
                                    <span class="text-xs font-bold uppercase tracking-wide text-slate-400 md:hidden">Người phụ trách</span>
                                    <span class="font-semibold text-slate-700">{{ $manager }}</span>
                                </a>
                            </td>
                            <td class="mt-2 block md:mt-0 md:table-cell md:px-4 md:py-4 md:text-right">
                                <a href="{{ $detailUrl }}" class="flex items-center justify-between gap-3 md:block">
                                    <span class="text-xs font-bold uppercase tracking-wide text-slate-400 md:hidden">Tổng giá trị</span>
                                    <span class="font-black tabular-nums text-slate-950">{{ number_format((float)$row->revenue_amount,0,',','.') }} đ</span>
                                </a>
                            </td>
                            <td class="mt-2 block border-t border-slate-100 pt-3 md:mt-0 md:table-cell md:border-0 md:px-4 md:py-4 md:text-right">
                                <a href="{{ $detailUrl }}" class="flex items-center justify-between gap-3 md:block">
                                    <span class="text-xs font-bold uppercase tracking-wide text-slate-400 md:hidden">Tổng hoa hồng</span>
                                    <span class="font-black tabular-nums {{ (float)$row->commission_amount<0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ number_format((float)$row->commission_amount,0,',','.') }} đ <span class="ml-1 text-slate-400">›</span></span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center"><h2 class="font-black text-slate-800">Chưa có phiếu xuất phù hợp</h2><p class="mt-2 text-sm text-slate-500">Thử thay đổi khoảng ngày, nguồn hoặc từ khóa tìm kiếm.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($rows->hasMorePages())
            <div id="commission-load-more-wrap" class="pt-1 text-center">
                <a id="commission-load-more" data-pwa-load-more data-pwa-load-more-target="#commission-list" data-pwa-load-more-items="#commission-list [data-commission-item]" data-pwa-load-more-wrap="#commission-load-more-wrap" data-pwa-pending-label="Đang tải…" href="{{ $rows->nextPageUrl() }}" class="inline-flex min-h-12 w-full items-center justify-center rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-black text-slate-800 shadow-sm transition active:scale-[0.985] sm:w-auto motion-reduce:transform-none">Xem thêm</a>
                <p class="mt-2 text-xs font-semibold text-slate-400">Đã hiển thị {{ $rows->count() }} / {{ $rows->total() }}</p>
            </div>
        @endif
    </div>
</div>

<script>
window.syncCommissionDate=(input)=>{
    if(!input.value) return;
    const [year,month,day]=input.value.split('-');
    const label=document.querySelector('[data-commission-date-label="'+input.dataset.commissionDatePicker+'"]');
    if(label) label.textContent=day+'/'+month+'/'+year;
    input.form.requestSubmit();
};
</script>
@endsection
