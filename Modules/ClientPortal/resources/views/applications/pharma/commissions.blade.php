@extends('ClientPortal::layouts.application')

@section('title', $featurePresentation['page_title'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Commission Ledger · chỉ đọc')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)

@section('content')
<form id="commission-export-form" method="POST" action="{{ route('client.pharma.commissions.export') }}" class="mb-4 space-y-3">
    @csrf
    @foreach(['source','from','to','partner_id','medicine_id','manager_user_id'] as $key) @if(filled($filters[$key] ?? null))<input type="hidden" name="{{ $key }}" value="{{ $filters[$key] }}">@endif @endforeach
    <div class="flex flex-wrap items-center justify-between gap-2">
        <a href="{{ route('client.pharma.dashboard') }}" class="inline-flex min-h-11 items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition active:scale-[0.985] motion-reduce:transform-none">← Không gian làm việc Pharma</a>
        <button id="commission-export-excel" type="submit" class="inline-flex min-h-11 items-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-black text-emerald-800 shadow-sm transition active:scale-[0.985] motion-reduce:transform-none">Xuất Excel</button>
    </div>
    <div id="commission-selected-inputs"></div>
</form>
@if($recentExports->isNotEmpty())
<section class="mb-4 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm" data-commission-export-panel>
    <button type="button" class="flex min-h-14 w-full items-center justify-between gap-3 px-4 py-3 text-left" data-commission-export-toggle aria-expanded="false">
        <span class="min-w-0"><span class="block font-black text-slate-900">File đã xuất <span class="text-slate-400">({{ $recentExports->count() }})</span></span><span class="mt-0.5 block text-xs font-semibold text-slate-500">Excel riêng của tài khoản bạn</span></span>
        <span class="text-lg font-black text-slate-500 transition-transform" data-commission-export-chevron>⌄</span>
    </button>
    <div class="hidden border-t border-slate-100 p-4 pt-3" data-commission-export-content>
        <div class="space-y-2">
            @foreach($recentExports as $export)
            @php
                $exportSource=$export->filters['source'] ?? 'all';
                $exportSourceLabel=$exportSource==='bid' ? 'Trúng thầu' : ($exportSource==='price_list' ? 'Bảng giá' : 'Tất cả');
            @endphp
            <div class="rounded-2xl border {{ $activeExport?->id===$export->id ? 'border-emerald-300 bg-emerald-50/50' : 'border-slate-200' }} p-3">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-black text-slate-800">{{ $export->download_name }}</p>
                        <p class="mt-1 text-xs font-semibold text-slate-500">{{ $export->generated_at?->format('d/m/Y H:i') }} · {{ number_format($export->row_count) }} dòng</p>
                        <p class="mt-1 text-xs font-semibold text-slate-500"><span class="font-black text-slate-600">Nguồn:</span> {{ $exportSourceLabel }}</p>
                    </div>
                    <div class="flex shrink-0 flex-wrap gap-2"><a href="{{ route('client.pharma.commissions.exports.download',$export) }}" class="min-h-10 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-black text-slate-700">Tải</a><a href="{{ route('client.pharma.commissions.exports.print',$export) }}" target="_blank" rel="noopener" class="min-h-10 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-black text-slate-700">In</a><button type="button" data-commission-share-url="{{ route('client.pharma.commissions.exports.download',$export) }}" data-commission-share-name="{{ $export->download_name }}" class="min-h-10 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-black text-emerald-800">Chia sẻ</button><form method="POST" action="{{ route('client.pharma.commissions.exports.destroy',$export) }}" onsubmit="return confirm('Xóa file Excel này khỏi máy chủ?')">@csrf @method('DELETE')<button type="submit" class="min-h-10 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-black text-rose-700">Xóa</button></form></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<div data-commission-workspace class="min-w-0 space-y-4 overflow-x-hidden">
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
                || !empty($filters['medicine_id'])
                || $filters['from']!==now()->startOfMonth()->toDateString()
                || $filters['to']!==now()->toDateString();
        @endphp
        <nav class="flex items-center gap-2 overflow-x-auto pb-1" aria-label="Nguồn hoa hồng">
            @foreach($sources as $value=>$label)
                <a href="{{ route('client.pharma.commissions', array_filter(['source'=>$value==='all' ? null : $value,'from'=>$filters['from'],'to'=>$filters['to'],'partner_id'=>$filters['partner_id'],'medicine_id'=>$filters['medicine_id'],'manager_user_id'=>$filters['manager_user_id']])) }}" class="whitespace-nowrap rounded-full border px-4 py-2 text-sm font-black transition active:scale-[0.985] motion-reduce:transform-none {{ $filters['source']===$value ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 bg-white text-slate-600' }}">{{ $label }}</a>
            @endforeach
            @if($hasCommissionFilters)
                <a href="{{ route('client.pharma.commissions') }}" aria-label="Xóa bộ lọc" title="Xóa bộ lọc" class="ml-1 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-lg font-black text-slate-500 transition hover:bg-slate-100 active:scale-[0.96] motion-reduce:transform-none">↺</a>
            @endif
        </nav>

        <form id="commission-filter-form" method="GET" action="{{ route('client.pharma.commissions') }}" class="mt-4 min-w-0 space-y-3">
            <input type="hidden" name="source" value="{{ $filters['source'] }}">

            <div class="grid min-w-0 gap-3 md:grid-cols-2 {{ $canViewTeam ? 'xl:grid-cols-3' : '' }}">
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
                    <span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Sản phẩm</span>
                    <x-pwa-select-search id="commission-medicine" name="medicine_id" :selected="$filters['medicine_id'] ?? ''" placeholder="Tất cả sản phẩm" search-placeholder="Tìm thuốc hoặc mã thuốc..." data-pwa-select-search-submit="change">
                        <button type="button" data-pwa-select-search-option data-value="" data-label="Tất cả sản phẩm" data-search="tất cả sản phẩm" class="flex w-full items-center rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-700 hover:bg-slate-100">Tất cả sản phẩm</button>
                        @foreach($commissionMedicines as $commissionMedicine)
                            @php
                                $commissionMedicineLabel=$commissionMedicine->name.($commissionMedicine->medicine_code ? ' · '.$commissionMedicine->medicine_code : '');
                            @endphp
                            <button type="button" data-pwa-select-search-option data-value="{{ $commissionMedicine->id }}" data-label="{{ $commissionMedicineLabel }}" data-search="{{ $commissionMedicineLabel }}" class="flex w-full items-center rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-700 hover:bg-slate-100">{{ $commissionMedicineLabel }}</button>
                        @endforeach
                    </x-pwa-select-search>
                </label>

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

            <div class="grid min-w-0 grid-cols-1 items-end gap-3 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]">
                <label class="min-w-0 text-xs font-bold uppercase tracking-wide text-slate-500">
                    Từ ngày
                    <input type="date" name="from" value="{{ $filters['from'] }}" aria-label="Từ ngày" class="mt-1.5 block h-10 min-w-0 w-full max-w-full box-border rounded-xl border border-slate-300 bg-white px-2.5 text-sm font-medium normal-case tracking-normal text-slate-950">
                </label>
                <label class="min-w-0 text-xs font-bold uppercase tracking-wide text-slate-500">
                    Đến ngày
                    <input type="date" name="to" value="{{ $filters['to'] }}" aria-label="Đến ngày" class="mt-1.5 block h-10 min-w-0 w-full max-w-full box-border rounded-xl border border-slate-300 bg-white px-2.5 text-sm font-medium normal-case tracking-normal text-slate-950">
                </label>
                <button type="submit" data-commission-date-apply class="inline-flex h-10 w-full items-center justify-center rounded-xl bg-slate-950 px-4 text-sm font-bold text-white transition active:scale-[0.985] motion-reduce:transform-none md:w-auto md:shrink-0 md:px-5">
                    Áp dụng
                </button>
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
                    $sourceLabel=$row->commission_source_type==='bid' ? 'Trúng thầu' : ($row->commission_source_type==='price_list' ? 'Bảng giá' : 'Hỗn hợp');
                    $detailUrl=route('client.pharma.commissions.show',['issue'=>$row->issue_id]);
                @endphp
                <div data-commission-item class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="mb-2 flex items-center justify-between gap-3"><label class="inline-flex min-h-9 items-center gap-2 text-xs font-bold text-slate-500"><input type="checkbox" class="commission-row-checkbox h-5 w-5 rounded border-slate-300" value="{{ $row->issue_id }}" aria-label="Chọn phiếu {{ $issue?->number }}"><span>Chọn</span></label><span class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ $issue?->issue_date?->format('d/m/Y') ?: $row->calculated_at?->format('d/m/Y') }}</span></div>
                    <a href="{{ $detailUrl }}" class="block transition active:scale-[0.985] motion-reduce:transform-none">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0"><h2 class="mt-1 truncate font-black text-slate-950">{{ $customer }}</h2><p class="mt-1 text-sm font-semibold text-slate-500">{{ $manager }}</p><p class="mt-1 text-xs font-bold text-slate-500"><span class="uppercase text-slate-400">Nguồn:</span> {{ $sourceLabel }}</p></div>
                        <span class="shrink-0 text-xl font-black text-slate-300">›</span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-3 border-t border-slate-100 pt-3">
                        <div><p class="text-[11px] font-bold uppercase text-slate-400">Tổng giá trị</p><p class="mt-1 font-black tabular-nums text-slate-950">{{ number_format((float)$row->revenue_amount,0,',','.') }} đ</p></div>
                        <div class="text-right"><p class="text-[11px] font-bold uppercase text-slate-400">Tổng hoa hồng</p><p class="mt-1 font-black tabular-nums {{ (float)$row->commission_amount<0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ number_format((float)$row->commission_amount,0,',','.') }} đ</p></div>
                    </div>
                </a></div>
            @empty
                <div class="rounded-3xl border border-slate-200 bg-white px-5 py-10 text-center"><h2 class="font-black text-slate-800">Chưa có phiếu xuất phù hợp</h2><p class="mt-2 text-sm text-slate-500">Thử thay đổi sản phẩm, khách hàng, khoảng ngày hoặc nguồn hoa hồng.</p></div>
            @endforelse
        </div>

        <div class="hidden overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm md:block">
            <table class="w-full table-fixed border-collapse text-left">
                <thead class="bg-slate-50 text-xs font-black uppercase tracking-wide text-slate-500">
                    <tr><th class="w-[6%] px-2 py-3 text-center"><input data-commission-select-all-desktop type="checkbox" class="h-4 w-4 rounded border-slate-300" aria-label="Chọn tất cả phiếu đang hiển thị"></th><th class="w-[13%] px-4 py-3">Ngày xuất</th><th class="w-[30%] px-4 py-3">Khách hàng</th><th class="w-[17%] px-4 py-3">Người phụ trách</th><th class="w-[13%] px-4 py-3">Nguồn</th><th class="w-[16%] px-4 py-3 text-right">Tổng giá trị</th><th class="w-[16%] px-4 py-3 text-right">Tổng hoa hồng</th></tr>
                </thead>
                <tbody id="commission-list" class="divide-y divide-slate-100">
                    @forelse($rows as $row)
                        @php
                            $issue=$row->issue;
                            $customer=$issue?->recipientPartner?->name ?: $issue?->recipient_name ?: '—';
                            $manager=$issue?->manager?->name ?: '—';
                            $sourceLabel=$row->commission_source_type==='bid' ? 'Trúng thầu' : ($row->commission_source_type==='price_list' ? 'Bảng giá' : 'Hỗn hợp');
                            $detailUrl=route('client.pharma.commissions.show',['issue'=>$row->issue_id]);
                        @endphp
                        <tr data-commission-item class="group transition hover:bg-slate-50">
                            <td class="px-2 py-4 text-center"><input type="checkbox" class="commission-row-checkbox h-4 w-4 rounded border-slate-300" value="{{ $row->issue_id }}" aria-label="Chọn phiếu {{ $issue?->number }}"></td>
                            <td class="px-4 py-4"><a href="{{ $detailUrl }}" class="block font-black text-slate-950">{{ $issue?->issue_date?->format('d/m/Y') ?: $row->calculated_at?->format('d/m/Y') }}</a></td>
                            <td class="px-4 py-4"><a href="{{ $detailUrl }}" class="block truncate font-bold text-slate-800">{{ $customer }}</a></td>
                            <td class="px-4 py-4"><a href="{{ $detailUrl }}" class="block truncate font-semibold text-slate-600">{{ $manager }}</a></td>
                            <td class="px-4 py-4"><a href="{{ $detailUrl }}" class="block font-bold text-slate-700">{{ $sourceLabel }}</a></td>
                            <td class="px-4 py-4 text-right"><a href="{{ $detailUrl }}" class="block font-black tabular-nums text-slate-950">{{ number_format((float)$row->revenue_amount,0,',','.') }} đ</a></td>
                            <td class="px-4 py-4 text-right"><a href="{{ $detailUrl }}" class="block font-black tabular-nums {{ (float)$row->commission_amount<0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ number_format((float)$row->commission_amount,0,',','.') }} đ <span class="text-slate-300">›</span></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-10 text-center"><h2 class="font-black text-slate-800">Chưa có phiếu xuất phù hợp</h2><p class="mt-2 text-sm text-slate-500">Thử thay đổi khách hàng, khoảng ngày hoặc nguồn hoa hồng.</p></td></tr>
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


@endsection
