@extends('ClientPortal::layouts.application')

@section('title', $featurePresentation['page_title'] ?? 'Bảng giá của tôi')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Bảng giá do bạn phụ trách')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)

@section('content')
<div class="space-y-5">
    <div class="flex min-h-11 items-center gap-3">
        <a href="{{ route('client.pharma.dashboard') }}" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-xl font-black text-slate-700 shadow-sm" aria-label="Quay lại Không gian làm việc Pharma">←</a>
        <div class="min-w-0"><p class="font-black text-slate-950">Bảng giá</p><p class="text-xs text-slate-500">Không gian làm việc Pharma</p></div>
    </div>
    @if(session('success'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-800">{{ session('success') }}</div>@endif
    <section class="rounded-[2rem] bg-slate-950 px-5 py-6 text-white shadow-sm sm:px-7">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-300">{{ $featurePresentation['eyebrow'] }}</p>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-black tracking-tight sm:text-3xl">{{ $featurePresentation['page_title'] }}</h1>
            <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-bold">{{ number_format($counts['all'] ?? 0, 0, ',', '.') }} bảng giá</span>
        </div>
        <div class="mt-3 flex flex-wrap items-center justify-between gap-3"><p class="max-w-3xl text-sm leading-6 text-slate-300">{{ $featurePresentation['page_description'] }}</p>@if($canCreate)<a href="{{ route('client.pharma.price-lists.create') }}" class="rounded-2xl bg-white px-4 py-2.5 text-sm font-black text-slate-950">+ Tạo bảng giá</a>@endif</div>
    </section>

    @php
        $defaultFromDate = now()->startOfMonth()->toDateString();
        $defaultToDate = now()->toDateString();
        $hasAdvancedFilters = ($canApprove && $managerUserId) || $fromDate !== $defaultFromDate || $toDate !== $defaultToDate;
        $hasAnyFilters = $search !== '' || $status !== null || $hasAdvancedFilters;
        $advancedFilterCount = ($canApprove && $managerUserId ? 1 : 0) + ($fromDate !== $defaultFromDate ? 1 : 0) + ($toDate !== $defaultToDate ? 1 : 0);
        $statuses = [
            null => ['label' => 'Tất cả', 'class' => 'slate'],
            'active' => ['label' => 'Đang hiệu lực', 'class' => 'emerald'],
            'draft' => ['label' => 'Nháp', 'class' => 'amber'],
            'pending_approval' => ['label' => 'Chờ duyệt', 'class' => 'amber'],
            'rejected' => ['label' => 'Từ chối', 'class' => 'rose'],
            'pending_deactivation' => ['label' => 'Chờ ngừng', 'class' => 'violet'],
            'inactive' => ['label' => 'Ngưng', 'class' => 'slate'],
            'archived' => ['label' => 'Lưu trữ', 'class' => 'slate'],
        ];
    @endphp
    <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <form id="price-list-search-form" method="GET" action="{{ route('client.pharma.price-lists') }}">
            <label class="block">
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Tìm bảng giá / khách hàng</span>
                <div class="relative"><span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-400">⌕</span><input id="price-list-search-input" type="search" name="q" value="{{ $search }}" autocomplete="off" data-pwa-debounced-search="350" data-pwa-search-region="#price-list-results-region" data-pwa-search-clear="#price-list-search-clear" class="h-12 w-full rounded-2xl border border-slate-300 pl-10 pr-11 text-sm" placeholder="Tên, mã bảng giá, khách hàng..."><x-native-touch id="price-list-search-clear" type="button" data-pwa-search-clear-button="#price-list-search-input" class="{{ $search === '' ? 'hidden ' : '' }}absolute right-2 top-2 inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-500" aria-label="Xóa tìm kiếm">×</x-native-touch></div>
            </label>
            @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif

            <details id="price-list-advanced-filters" class="group mt-3 rounded-2xl border border-slate-200 bg-slate-50/70 lg:open" @if($hasAdvancedFilters) open @endif>
                <summary class="flex min-h-11 cursor-pointer list-none items-center justify-between gap-3 px-4 py-2.5 text-sm font-black text-slate-700">
                    <span>⚙ Bộ lọc nâng cao @if($advancedFilterCount)<span class="ml-1 rounded-full bg-slate-900 px-2 py-0.5 text-[11px] text-white">{{ $advancedFilterCount }}</span>@endif</span>
                    <span class="text-xs font-bold text-slate-400 group-open:rotate-180">⌄</span>
                </summary>
                <div class="grid gap-3 border-t border-slate-200 p-4 {{ $canApprove ? 'lg:grid-cols-[14rem_10.5rem_10.5rem_auto]' : 'lg:grid-cols-[10.5rem_10.5rem_auto]' }} lg:items-end">
                    @if($canApprove)
                        <label><span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">User phụ trách</span><x-pwa-select-search id="price-list-manager-user" name="manager_user_id" :selected="$managerUserId ?? ''" placeholder="Tất cả User" search-placeholder="Tìm User..." data-pwa-select-search-submit="change"><button type="button" data-pwa-select-search-option data-value="" data-label="Tất cả User" data-search="tất cả user" class="flex w-full items-center rounded-xl px-3 py-2.5 text-left text-sm font-semibold hover:bg-slate-100">Tất cả User</button>@foreach($managerUsers as $managerUser)<button type="button" data-pwa-select-search-option data-value="{{ $managerUser->id }}" data-label="{{ $managerUser->name }}" data-search="{{ mb_strtolower($managerUser->name.' '.($managerUser->email ?? '')) }}" class="flex w-full items-center rounded-xl px-3 py-2.5 text-left text-sm font-semibold hover:bg-slate-100">{{ $managerUser->name }}</button>@endforeach</x-pwa-select-search></label>
                    @endif
                    <div class="grid grid-cols-2 gap-2 lg:contents">
                        @foreach([['from_date','Từ ngày',$fromDate],['to_date','Đến ngày',$toDate]] as [$dateName,$dateLabel,$dateIso])
                            <x-pwa-date :name="$dateName" :label="$dateLabel" :value="$dateIso" :min="$dateName === 'to_date' ? $fromDate : null" :max="$dateName === 'from_date' ? $toDate : null" :aria-label="$dateLabel" onchange="this.form?.requestSubmit()" />
                        @endforeach
                    </div>
                    @if($hasAnyFilters)<a href="{{ route('client.pharma.price-lists', ['from_date' => $defaultFromDate, 'to_date' => $defaultToDate]) }}" class="flex min-h-11 items-center justify-center rounded-2xl border border-slate-300 bg-white px-4 text-sm font-bold text-slate-600 hover:bg-slate-50">Đặt lại bộ lọc</a>@endif
                </div>
            </details>
        </form>

        <div class="-mx-4 mt-4 overflow-x-auto px-4 pb-1 sm:-mx-5 sm:px-5" data-status-rail>
            <div class="flex w-max flex-nowrap gap-2 whitespace-nowrap">
                @foreach($statuses as $value => $meta)
                    @php
                        $normalizedValue = $value === '' ? null : $value;
                        $active = $status === $normalizedValue;
                        $countKey = $normalizedValue ?? 'all';
                        $statusCount = $normalizedValue === 'pending_approval' && $canApprove ? ($pendingApprovalCount ?? 0) : ($counts[$countKey] ?? 0);
                        $disabled = !$active && $statusCount < 1;
                    @endphp
                    @if($disabled)
                        <span aria-disabled="true" class="min-h-10 cursor-not-allowed select-none rounded-full border border-slate-100 bg-slate-50 px-3.5 py-2 text-xs font-bold text-slate-300">{{ $meta['label'] }} <span class="ml-1 opacity-70">{{ $statusCount }}</span></span>
                    @elseif($normalizedValue === 'pending_approval' && $canApprove)
                        <a href="{{ route('client.pharma.price-list-approvals') }}" title="Yêu cầu cần tôi duyệt" class="min-h-10 rounded-full border border-blue-600 bg-blue-600 px-3.5 py-2 text-xs font-bold text-white">Chờ duyệt <span class="ml-1 opacity-80">{{ $statusCount }}</span></a>
                    @else
                        <a href="{{ route('client.pharma.price-lists', array_filter(['q' => $search, 'status' => $normalizedValue, 'from_date' => $fromDate, 'to_date' => $toDate, 'manager_user_id' => $managerUserId], fn($v) => $v !== null && $v !== '')) }}" class="min-h-10 rounded-full border px-3.5 py-2 text-xs font-bold {{ $active ? ($meta['class'] === 'emerald' ? 'border-emerald-600 bg-emerald-600 text-white' : ($meta['class'] === 'amber' ? 'border-amber-500 bg-amber-500 text-white' : 'border-slate-800 bg-slate-800 text-white')) : 'border-slate-200 bg-white text-slate-600' }}">@if($active)✓ @endif{{ $meta['label'] }} <span class="ml-1 opacity-70">{{ $statusCount }}</span></a>
                    @endif
                @endforeach
            </div>
        </div>
    </section>

    <div id="price-list-results-region">
    <div id="price-list-mobile-results" class="grid min-w-0 w-full gap-2.5 px-0.5 lg:hidden">
        @forelse($priceLists as $priceList)
            @php
                $customer = $priceList->partner?->name ?? $priceList->officialFacility?->facility_name ?? $priceList->officialFacility?->name ?? 'Bảng giá chung';
                $exportShare = $exportShares[(int)$priceList->id] ?? null;
                $statusLabel = match($priceList->status) { 'draft' => 'Nháp', 'pending_approval' => 'Chờ duyệt', 'active' => 'Hiệu lực', 'pending_deactivation' => 'Chờ ngừng', 'rejected' => 'Từ chối', 'inactive' => 'Ngưng', 'archived' => 'Lưu trữ', default => $priceList->status };
                $statusClass = match($priceList->status) { 'active' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'draft' => 'bg-amber-50 text-amber-700 ring-amber-200', 'pending_approval' => 'bg-blue-50 text-blue-700 ring-blue-200', 'rejected' => 'bg-rose-50 text-rose-700 ring-rose-200', default => 'bg-slate-100 text-slate-600 ring-slate-200' };
            @endphp
            <article data-price-list-card class="relative min-w-0 w-full max-w-full overflow-visible rounded-3xl border border-slate-200 bg-white shadow-sm">
                <a href="{{ route('client.pharma.price-lists.show', $priceList->id) }}" class="block min-w-0 max-w-full rounded-3xl p-4 pr-[4.25rem]">
                    <div class="flex min-w-0 items-start gap-2">
                        <div class="min-w-0 flex-1"><h2 class="truncate font-black leading-5 text-slate-950">{{ $priceList->name }}</h2>@if($customer)<p class="mt-1 line-clamp-2 text-xs leading-5 text-slate-500">{{ $customer }}</p>@endif</div>
                        <span class="shrink-0 rounded-full px-2 py-1 text-[10px] font-black ring-1 {{ $statusClass }}">{{ $statusLabel }}</span>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-600">
                        @if($priceList->items_count !== null)<span class="font-bold text-slate-700">{{ $priceList->items_count }} sản phẩm</span>@endif
                        @if($priceList->items_count !== null && ($priceList->effective_from || $priceList->effective_to))<span class="text-slate-300">·</span>@endif
                        @if($priceList->effective_from || $priceList->effective_to)<span class="tabular-nums">@if($priceList->effective_from){{ $priceList->effective_from->format('d/m/Y') }}@endif @if($priceList->effective_from && $priceList->effective_to)→@endif @if($priceList->effective_to){{ $priceList->effective_to->format('d/m/Y') }}@endif</span>@endif
                    </div>
                </a>
                <details class="absolute bottom-3 right-3 z-20 max-w-[calc(100%-1.5rem)]" data-price-list-actions>
                    <summary aria-label="Mở menu thao tác {{ $priceList->name }}" class="inline-flex h-11 w-11 cursor-pointer list-none items-center justify-center rounded-2xl border border-slate-200 bg-white text-sm font-black tracking-widest text-slate-700 shadow-sm">•••</summary>
                    <div class="absolute bottom-12 right-0 z-[100] w-56 max-w-[calc(100vw-3rem)] overflow-hidden rounded-2xl border border-slate-200 bg-white p-1.5 text-left shadow-xl">
                        <a href="{{ route('client.pharma.price-lists.show',$priceList->id) }}" class="block rounded-xl px-3 py-2.5 text-xs font-bold text-slate-700">Chi tiết bảng giá</a>
                        @if(in_array($priceList->status,['draft','rejected'],true) && $canCreate)<a href="{{ route('client.pharma.price-lists.edit',$priceList->id) }}" class="block rounded-xl px-3 py-2.5 text-xs font-bold text-slate-700">Sửa thông tin</a>@endif
                        @if(in_array($priceList->status,['draft','rejected'],true))<form method="POST" action="{{ route('client.pharma.price-lists.submit',$priceList->id) }}">@csrf<button class="block w-full rounded-xl px-3 py-2.5 text-left text-xs font-bold text-blue-700">Gửi duyệt</button></form>@endif
                        @if($priceList->status==='pending_approval' && $canApprove)<form method="POST" action="{{ route('client.pharma.price-list-approvals.approve',$priceList->id) }}" onsubmit="return confirm('Phê duyệt và kích hoạt bảng giá này?')">@csrf<button class="block w-full rounded-xl px-3 py-2.5 text-left text-xs font-bold text-emerald-700">Phê duyệt & kích hoạt</button></form>@endif
                        @if($priceList->status==='pending_deactivation' && $canApprove)<form method="POST" action="{{ route('client.pharma.price-lists.deactivation.approve',$priceList->id) }}" onsubmit="return confirm('Chấp nhận yêu cầu và ngừng kích hoạt bảng giá này?')">@csrf<button class="block w-full rounded-xl px-3 py-2.5 text-left text-xs font-bold text-rose-700">Chấp nhận ngừng kích hoạt</button></form>@endif
                        @if($priceList->status==='inactive' && $canApprove)<form method="POST" action="{{ route('client.pharma.price-lists.activate',$priceList->id) }}" onsubmit="return confirm('Kích hoạt trở lại bảng giá này?')">@csrf<button class="block w-full rounded-xl px-3 py-2.5 text-left text-xs font-bold text-emerald-700">Kích hoạt trở lại</button></form>@endif
                        @if($priceList->status==='inactive' && $canApprove)<form method="POST" action="{{ route('client.pharma.price-lists.delete',$priceList->id) }}" onsubmit="return confirm('Xóa vĩnh viễn bảng giá Ngưng này? Toàn bộ file Excel và PDF đã xuất cũng sẽ bị xóa.')">@csrf @method('DELETE')<button class="block w-full rounded-xl px-3 py-2.5 text-left text-xs font-bold text-red-700">Xóa bảng giá</button></form>@endif
                        @if($priceList->status==='active')
                            <form method="POST" action="{{ route('client.pharma.price-lists.export-share',$priceList->id) }}">@csrf<button class="block w-full rounded-xl px-3 py-2.5 text-left text-xs font-bold text-slate-700">Xuất Excel mới</button></form>
                            @if($exportShare)
                                <a href="{{ $exportShare['url'] }}" data-pwa-file-handoff data-file-name="{{ $exportShare['download_name'] ?? ($priceList->code.'.xlsx') }}" class="block rounded-xl px-3 py-2.5 text-xs font-bold text-emerald-700">Tải Excel đã xuất</a>
                                @if(!empty($exportShare['pdf_url']))<a href="{{ $exportShare['pdf_url'] }}" data-pwa-file-handoff data-file-name="{{ preg_replace('/\.xlsx$/i','.pdf',$exportShare['download_name'] ?? ($priceList->code.'.xlsx')) }}" class="block rounded-xl px-3 py-2.5 text-xs font-bold text-violet-700">Tải PDF</a>
                                @else<form method="POST" action="{{ route('client.pharma.price-lists.share.pdf.queue',(int)$exportShare['share_id']) }}">@csrf<button class="block w-full rounded-xl px-3 py-2.5 text-left text-xs font-bold text-violet-700">Chuyển sang PDF</button></form>@endif
                            @endif
                            <button type="button" data-price-list-deactivation-open data-price-list-id="{{ $priceList->id }}" data-price-list-name="{{ $priceList->name }}" data-price-list-action="{{ $canApprove ? route('client.pharma.price-lists.deactivate',$priceList->id) : route('client.pharma.price-lists.deactivation.request',$priceList->id) }}" data-price-list-mode="{{ $canApprove ? 'direct' : 'request' }}" class="block w-full rounded-xl px-3 py-2.5 text-left text-xs font-bold text-rose-700">Ngừng kích hoạt…</button>
                        @endif
                    </div>
                </details>
            </article>
        @empty <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">Bạn chưa có bảng giá nào trong phạm vi quản lý.</div> @endforelse
    </div>

    <section class="hidden overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm lg:block">
        <table class="w-full table-fixed text-left text-sm"><thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Bảng giá</th><th class="px-5 py-3">Khách hàng</th>@if($canApprove)<th class="px-5 py-3">Người phụ trách</th>@endif<th class="px-5 py-3">Mục đích</th><th class="w-[7%] px-5 py-3 text-center">SP</th><th class="px-5 py-3">Hiệu lực</th><th class="w-[12%] px-4 py-3">Trạng thái</th><th class="w-20 whitespace-nowrap px-4 py-3 text-right">Thao tác</th></tr></thead>
        <tbody id="price-list-desktop-results" class="divide-y divide-slate-100">
        @forelse($priceLists as $priceList)
            @php
                $customer = $priceList->partner?->name ?? $priceList->officialFacility?->facility_name ?? $priceList->officialFacility?->name ?? 'Bảng giá chung';
                $exportShare = $exportShares[(int)$priceList->id] ?? null;
                $statusLabel = match($priceList->status) { 'draft' => 'Nháp', 'pending_approval' => 'Chờ duyệt', 'active' => 'Đang hiệu lực', 'pending_deactivation' => 'Chờ ngừng', 'rejected' => 'Từ chối', 'inactive' => 'Ngưng', 'archived' => 'Lưu trữ', default => $priceList->status };
            @endphp
            <tr data-price-list-row class="transition hover:bg-slate-50"><td class="px-5 py-4"><a href="{{ route('client.pharma.price-lists.show', $priceList->id) }}" class="font-black text-slate-950 hover:underline">{{ $priceList->name }}</a><div class="mt-1 flex items-center gap-2"><p class="text-xs text-slate-400">{{ $priceList->code }}</p></div>@if(in_array($priceList->status, ['draft', 'rejected'], true) && $canCreate)<div class="mt-2 flex items-center gap-3 text-xs font-bold"><a href="{{ route('client.pharma.price-lists.edit', $priceList->id) }}" class="text-blue-700 hover:underline">Sửa</a>@if($priceList->status === 'draft')<form method="POST" action="{{ route('client.pharma.price-lists.delete', $priceList->id) }}" onsubmit="return confirm('Xóa bảng giá Nháp này?')">@csrf @method('DELETE')<button class="text-red-600 hover:underline">Xóa</button></form>@endif</div>@endif</td><td class="px-5 py-4 text-slate-700">{{ $customer }}</td>@if($canApprove)<td class="px-5 py-4 text-slate-700">{{ $priceList->manager?->name ?: '—' }}</td>@endif<td class="px-5 py-4 text-slate-600">{{ $priceList->purpose?->name ?: '—' }}</td><td class="px-5 py-4 text-center font-bold">{{ $priceList->items_count }}</td><td class="px-5 py-4 text-slate-600">{{ $priceList->effective_from?->format('d/m/Y') ?: '—' }} → {{ $priceList->effective_to?->format('d/m/Y') ?: '—' }}</td><td class="px-5 py-4"><span class="whitespace-nowrap rounded-full px-2 py-1 text-[11px] font-bold {{ $priceList->status === 'active' ? 'bg-emerald-100 text-emerald-700' : ($priceList->status === 'draft' ? 'bg-amber-100 text-amber-700' : ($priceList->status === 'pending_approval' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600')) }}">{{ $statusLabel }}</span></td><td class="px-5 py-4 text-right">
                <details class="relative inline-block text-left" data-price-list-actions>
                    <summary class="inline-flex min-h-9 cursor-pointer list-none items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-black text-slate-700 hover:bg-slate-50" aria-label="Mở menu thao tác">•••</summary>
                    <div class="absolute right-0 z-[100] mt-2 w-56 overflow-hidden rounded-2xl border border-slate-200 bg-white p-1.5 text-left shadow-xl">
                        <a href="{{ route('client.pharma.price-lists.show',$priceList->id) }}" class="block rounded-xl px-3 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50">Chi tiết bảng giá</a>
                        @if(in_array($priceList->status,['draft','rejected'],true) && $canCreate)
                            <a href="{{ route('client.pharma.price-lists.edit',$priceList->id) }}" class="block rounded-xl px-3 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50">Sửa thông tin</a>
                        @endif
                        @if(in_array($priceList->status,['draft','rejected'],true))
                            <form method="POST" action="{{ route('client.pharma.price-lists.submit',$priceList->id) }}">@csrf<button class="block w-full rounded-xl px-3 py-2.5 text-left text-xs font-bold text-blue-700 hover:bg-blue-50">Gửi duyệt</button></form>
                        @endif
                        @if($priceList->status==='pending_approval' && $canApprove)
                            <form method="POST" action="{{ route('client.pharma.price-list-approvals.approve',$priceList->id) }}" onsubmit="return confirm('Phê duyệt và kích hoạt bảng giá này?')">@csrf<button class="block w-full rounded-xl px-3 py-2.5 text-left text-xs font-bold text-emerald-700 hover:bg-emerald-50">Phê duyệt & kích hoạt</button></form>
                        @endif
                        @if($priceList->status==='pending_deactivation' && $canApprove)
                            <form method="POST" action="{{ route('client.pharma.price-lists.deactivation.approve',$priceList->id) }}" onsubmit="return confirm('Chấp nhận yêu cầu và ngừng kích hoạt bảng giá này?')">@csrf<button class="block w-full rounded-xl px-3 py-2.5 text-left text-xs font-bold text-rose-700 hover:bg-rose-50">Chấp nhận ngừng kích hoạt</button></form>
                        @endif
                        @if($priceList->status==='inactive' && $canApprove)
                            <form method="POST" action="{{ route('client.pharma.price-lists.activate',$priceList->id) }}" onsubmit="return confirm('Kích hoạt trở lại bảng giá này?')">@csrf<button class="block w-full rounded-xl px-3 py-2.5 text-left text-xs font-bold text-emerald-700 hover:bg-emerald-50">Kích hoạt trở lại</button></form>
                        @endif
                        @if($priceList->status==='inactive' && $canApprove)
                            <form method="POST" action="{{ route('client.pharma.price-lists.delete',$priceList->id) }}" onsubmit="return confirm('Xóa vĩnh viễn bảng giá Ngưng này? Toàn bộ file Excel và PDF đã xuất cũng sẽ bị xóa.')">@csrf @method('DELETE')<button class="block w-full rounded-xl px-3 py-2.5 text-left text-xs font-bold text-red-700 hover:bg-red-50">Xóa bảng giá</button></form>
                        @endif
                        @if($priceList->status==='active')
                            <form method="POST" action="{{ route('client.pharma.price-lists.export-share',$priceList->id) }}">@csrf<button class="block w-full rounded-xl px-3 py-2.5 text-left text-xs font-bold text-slate-700 hover:bg-slate-50">Xuất Excel mới</button></form>
                            @if($exportShare)
                                <a href="{{ $exportShare['url'] }}" data-pwa-file-handoff data-file-name="{{ $exportShare['download_name'] ?? ($priceList->code.'.xlsx') }}" class="block rounded-xl px-3 py-2.5 text-xs font-bold text-emerald-700 hover:bg-emerald-50">Tải Excel đã xuất</a>
                                @if(!empty($exportShare['pdf_url']))
                                    <a href="{{ $exportShare['pdf_url'] }}" data-pwa-file-handoff data-file-name="{{ preg_replace('/\.xlsx$/i','.pdf',$exportShare['download_name'] ?? ($priceList->code.'.xlsx')) }}" class="block rounded-xl px-3 py-2.5 text-xs font-bold text-violet-700 hover:bg-violet-50">Tải PDF</a>
                                @else
                                    <form method="POST" action="{{ route('client.pharma.price-lists.share.pdf.queue',(int)$exportShare['share_id']) }}">@csrf<button class="block w-full rounded-xl px-3 py-2.5 text-left text-xs font-bold text-violet-700 hover:bg-violet-50">Chuyển sang PDF</button></form>
                                @endif
                            @endif
                            <button type="button" data-price-list-deactivation-open data-price-list-id="{{ $priceList->id }}" data-price-list-name="{{ $priceList->name }}" data-price-list-action="{{ $canApprove ? route('client.pharma.price-lists.deactivate',$priceList->id) : route('client.pharma.price-lists.deactivation.request',$priceList->id) }}" data-price-list-mode="{{ $canApprove ? 'direct' : 'request' }}" class="block w-full rounded-xl px-3 py-2.5 text-left text-xs font-bold text-rose-700 hover:bg-rose-50">Ngừng kích hoạt…</button>
                        @endif
                    </div>
                </details>
            </td></tr>
        @empty <tr><td colspan="{{ $canApprove ? 8 : 7 }}" class="px-5 py-10 text-center text-slate-500">Bạn chưa có bảng giá nào trong phạm vi quản lý.</td></tr> @endforelse
        </tbody></table>
    </section>
    @if($priceLists->hasMorePages())<div id="price-list-load-more-wrap" class="pt-1 text-center"><a data-pwa-load-more data-pwa-load-more-targets="#price-list-mobile-results::[data-price-list-card]|#price-list-desktop-results::[data-price-list-row]" data-pwa-load-more-wrap="#price-list-load-more-wrap" href="{{ $priceLists->nextPageUrl() }}" class="inline-flex min-h-11 items-center justify-center rounded-2xl border border-slate-300 bg-white px-6 text-sm font-black text-slate-700">Xem thêm</a></div>@endif
    </div>
</div>

<dialog id="price-list-deactivation-dialog" class="mb-0 mt-auto w-full max-w-[520px] rounded-t-[28px] border-0 p-0 shadow-2xl backdrop:bg-slate-950/55 sm:m-auto sm:w-[min(92vw,520px)] sm:rounded-[28px]">
    <form method="POST" data-price-list-deactivation-form class="p-5 sm:p-6">
        @csrf
        <p class="text-lg font-black text-slate-950">Ngừng kích hoạt bảng giá</p>
        <p class="mt-2 text-sm leading-6 text-slate-600" data-price-list-deactivation-message>Nhập lý do để tiếp tục.</p>
        <label class="mt-4 block"><span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Lý do</span><textarea name="deactivation_reason" required maxlength="1000" rows="4" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" placeholder="Nhập lý do ngừng kích hoạt..."></textarea></label>
        <div class="mt-5 flex gap-2"><button type="button" data-price-list-deactivation-cancel class="min-h-11 flex-1 rounded-2xl border border-slate-300 px-4 py-3 text-sm font-black text-slate-700">Đóng</button><button type="submit" class="min-h-11 flex-1 rounded-2xl bg-rose-700 px-4 py-3 text-sm font-black text-white" data-price-list-deactivation-submit>Xác nhận</button></div>
    </form>
</dialog>

<dialog id="price-list-pwa-file-handoff" class="mb-0 mt-auto w-full max-w-[560px] rounded-t-[28px] border-0 p-0 shadow-2xl backdrop:bg-slate-950/55 sm:m-auto sm:w-[min(92vw,560px)] sm:rounded-[28px]">
    <div class="p-5 sm:p-6">
        <p class="text-lg font-black text-slate-950" data-file-title>Chuẩn bị tệp</p>
        <p class="mt-2 text-sm leading-6 text-slate-600" data-file-message>Đang chuẩn bị tệp trong PWA. Màn hình hiện tại sẽ được giữ nguyên.</p>
        <div class="mt-5 flex gap-2">
            <button type="button" data-file-cancel class="min-h-11 flex-1 rounded-2xl border border-slate-300 px-4 py-3 text-sm font-black text-slate-700">Đóng</button>
            <button type="button" data-file-share disabled class="min-h-11 flex-1 rounded-2xl bg-slate-950 px-4 py-3 text-sm font-black text-white disabled:cursor-not-allowed disabled:opacity-40">Mở / chia sẻ tệp</button>
        </div>
    </div>
</dialog>

<script>
document.addEventListener('DOMContentLoaded',()=>{
    const d=document.getElementById('price-list-advanced-filters');
    if(d&&window.matchMedia('(min-width: 1024px)').matches)d.open=true;

    const isInstalledPwa=()=>window.matchMedia('(display-mode: standalone)').matches||window.navigator.standalone===true;
    const dialog=document.getElementById('price-list-pwa-file-handoff');
    let preparedPwaFile=null;
    const canNativeSharePreparedFile=()=>{if(!preparedPwaFile||!navigator.share)return false;const payload={files:[preparedPwaFile]};return !navigator.canShare||navigator.canShare(payload)};
    const downloadPreparedPwaFile=()=>{
        if(!preparedPwaFile)return;
        const objectUrl=URL.createObjectURL(preparedPwaFile),download=document.createElement('a');
        download.href=objectUrl;download.download=preparedPwaFile.name;download.hidden=true;document.body.appendChild(download);download.click();download.remove();
        setTimeout(()=>URL.revokeObjectURL(objectUrl),30000);
    };
    const preparePwaFile=async(event,anchor)=>{
        if(!isInstalledPwa())return;
        event.preventDefault();
        event.stopPropagation();
        if(!dialog)return;
        const title=dialog.querySelector('[data-file-title]'),message=dialog.querySelector('[data-file-message]'),share=dialog.querySelector('[data-file-share]');
        preparedPwaFile=null;share.disabled=true;share.textContent='Mở / chia sẻ tệp';title.textContent='Chuẩn bị tệp';message.textContent='Đang chuẩn bị tệp trong PWA. Màn hình hiện tại sẽ được giữ nguyên.';dialog.showModal();
        try{
            const response=await fetch(anchor.href,{credentials:'same-origin',cache:'no-store',headers:{'X-PWA-File-Handoff':'1'}});
            if(!response.ok)throw new Error('download failed');
            const blob=await response.blob(),name=anchor.dataset.fileName||'bang-gia.xlsx';
            preparedPwaFile=new File([blob],name,{type:blob.type||'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
            title.textContent='Tệp đã sẵn sàng';share.disabled=false;
            if(canNativeSharePreparedFile()){
                message.textContent='Chọn “Mở / chia sẻ tệp” để bàn giao sang Files, Excel hoặc ứng dụng phù hợp. Màn hình PWA vẫn được giữ nguyên.';
            }else{
                share.textContent='Lưu tệp';message.textContent='Chọn “Lưu tệp” để tải Excel xuống mà không thay thế màn hình PWA.';
            }
        }catch(_){title.textContent='Không thể chuẩn bị tệp';message.textContent='Không tải được tệp trong phiên hiện tại. PWA vẫn giữ nguyên màn hình để bạn có thể thử lại.'}
    };
    document.querySelectorAll('[data-pwa-file-handoff]').forEach(anchor=>anchor.addEventListener('click',event=>preparePwaFile(event,anchor)));
    const deactivationDialog=document.getElementById('price-list-deactivation-dialog'),deactivationForm=deactivationDialog?.querySelector('[data-price-list-deactivation-form]'),deactivationMessage=deactivationDialog?.querySelector('[data-price-list-deactivation-message]');
    document.querySelectorAll('[data-price-list-deactivation-open]').forEach(button=>button.addEventListener('click',()=>{
        if(!deactivationDialog||!deactivationForm)return;
        const name=button.dataset.priceListName||'bảng giá',mode=button.dataset.priceListMode;
        deactivationForm.action=button.dataset.priceListAction||'';
        if(deactivationMessage)deactivationMessage.textContent=mode==='direct' ? `Bạn đang ngừng kích hoạt trực tiếp “${name}”. Vui lòng nhập lý do.` : `Yêu cầu ngừng kích hoạt “${name}” sẽ được gửi cho người có quyền phê duyệt.`;
        deactivationForm.querySelector('[name="deactivation_reason"]')?.focus();
        deactivationDialog.showModal();
    }));
    deactivationDialog?.querySelector('[data-price-list-deactivation-cancel]')?.addEventListener('click',()=>deactivationDialog.close());
    document.querySelectorAll('[data-price-list-actions]').forEach(menu=>{
        menu.addEventListener('toggle',()=>{
            if(!menu.open)return;
            document.querySelectorAll('[data-price-list-actions][open]').forEach(other=>{if(other!==menu)other.removeAttribute('open');});
        });
    });
    document.addEventListener('click',event=>{
        document.querySelectorAll('[data-price-list-actions][open]').forEach(menu=>{if(!menu.contains(event.target))menu.removeAttribute('open');});
    });

    dialog?.querySelector('[data-file-cancel]')?.addEventListener('click',()=>dialog.close());
    dialog?.querySelector('[data-file-share]')?.addEventListener('click',async()=>{
        if(!preparedPwaFile)return;
        if(canNativeSharePreparedFile()){
            try{await navigator.share({files:[preparedPwaFile],title:'Bảng giá'});dialog.close();return}catch(error){if(error?.name==='AbortError')return}
        }
        downloadPreparedPwaFile();
        const message=dialog.querySelector('[data-file-message]');if(message)message.textContent='Đã chuyển tệp sang trình tải xuống. Màn hình PWA vẫn được giữ nguyên.';
        const button=dialog.querySelector('[data-file-share]');if(button)button.textContent='Lưu lại tệp';
    });
});
</script>
@endsection
