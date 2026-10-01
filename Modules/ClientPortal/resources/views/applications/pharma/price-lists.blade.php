@extends('ClientPortal::layouts.application')

@section('title', 'Bảng giá của tôi')
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
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-300">My Price Lists</p>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-black tracking-tight sm:text-3xl">Bảng giá của tôi</h1>
            <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-bold">{{ number_format($counts['all'] ?? 0, 0, ',', '.') }} bảng giá</span>
        </div>
        <div class="mt-3 flex flex-wrap items-center justify-between gap-3"><p class="max-w-3xl text-sm leading-6 text-slate-300">Chỉ hiển thị các bảng giá bạn là người phụ trách. Tạo, gửi duyệt và phê duyệt được kiểm soát theo quyền nghiệp vụ.</p>@if($canCreate)<a href="{{ route('client.pharma.price-lists.create') }}" class="rounded-2xl bg-white px-4 py-2.5 text-sm font-black text-slate-950">+ Tạo bảng giá</a>@endif</div>
    </section>

    @php
        $defaultFromDate = now()->startOfMonth()->toDateString();
        $defaultToDate = now()->toDateString();
        $hasAdvancedFilters = ($canApprove && $managerUserId) || $fromDate !== $defaultFromDate || $toDate !== $defaultToDate || $perPage !== 25;
        $hasAnyFilters = $search !== '' || $status !== null || $hasAdvancedFilters;
        $advancedFilterCount = ($canApprove && $managerUserId ? 1 : 0) + ($fromDate !== $defaultFromDate ? 1 : 0) + ($toDate !== $defaultToDate ? 1 : 0) + ($perPage !== 25 ? 1 : 0);
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
                <div class="relative"><span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-400">⌕</span><input id="price-list-search-input" type="search" name="q" value="{{ $search }}" autocomplete="off" class="h-12 w-full rounded-2xl border border-slate-300 pl-10 pr-4 text-sm" placeholder="Tên, mã bảng giá, khách hàng..."></div>
            </label>
            @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif

            <details id="price-list-advanced-filters" class="group mt-3 rounded-2xl border border-slate-200 bg-slate-50/70 lg:open" @if($hasAdvancedFilters) open @endif>
                <summary class="flex min-h-11 cursor-pointer list-none items-center justify-between gap-3 px-4 py-2.5 text-sm font-black text-slate-700">
                    <span>⚙ Bộ lọc nâng cao @if($advancedFilterCount)<span class="ml-1 rounded-full bg-slate-900 px-2 py-0.5 text-[11px] text-white">{{ $advancedFilterCount }}</span>@endif</span>
                    <span class="text-xs font-bold text-slate-400 group-open:rotate-180">⌄</span>
                </summary>
                <div class="grid gap-3 border-t border-slate-200 p-4 {{ $canApprove ? 'lg:grid-cols-[14rem_10.5rem_10.5rem_8rem_auto]' : 'lg:grid-cols-[10.5rem_10.5rem_8rem_auto]' }} lg:items-end">
                    @if($canApprove)
                        <label><span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">User phụ trách</span><select name="manager_user_id" onchange="this.form.submit()" class="h-[46px] w-full rounded-2xl border border-slate-300 bg-white px-3 text-sm"><option value="">Tất cả User</option>@foreach($managerUsers as $managerUser)<option value="{{ $managerUser->id }}" @selected((int)$managerUserId === (int)$managerUser->id)>{{ $managerUser->name }}</option>@endforeach</select></label>
                    @endif
                    <div class="grid grid-cols-2 gap-2 lg:contents">
                        <label><span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Từ ngày</span><input type="date" name="from_date" value="{{ $fromDate }}" onchange="this.form.submit()" class="h-[46px] w-full rounded-2xl border border-slate-300 bg-white px-2 text-sm sm:px-3"></label>
                        <label><span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Đến ngày</span><input type="date" name="to_date" value="{{ $toDate }}" onchange="this.form.submit()" class="h-[46px] w-full rounded-2xl border border-slate-300 bg-white px-2 text-sm sm:px-3"></label>
                    </div>
                    <label><span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Hiển thị</span><select name="per_page" onchange="this.form.submit()" class="h-[46px] w-full rounded-2xl border border-slate-300 bg-white px-3 text-sm">@foreach([25,50,100] as $size)<option value="{{ $size }}" @selected($perPage === $size)>{{ $size }} / trang</option>@endforeach</select></label>
                    @if($hasAnyFilters)<a href="{{ route('client.pharma.price-lists', ['from_date' => $defaultFromDate, 'to_date' => $defaultToDate, 'per_page' => 25]) }}" class="flex min-h-11 items-center justify-center rounded-2xl border border-slate-300 bg-white px-4 text-sm font-bold text-slate-600 hover:bg-slate-50">Đặt lại bộ lọc</a>@endif
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
                        <a href="{{ route('client.pharma.price-lists', array_filter(['q' => $search, 'per_page' => $perPage, 'status' => $normalizedValue, 'from_date' => $fromDate, 'to_date' => $toDate, 'manager_user_id' => $managerUserId], fn($v) => $v !== null && $v !== '')) }}" class="min-h-10 rounded-full border px-3.5 py-2 text-xs font-bold {{ $active ? ($meta['class'] === 'emerald' ? 'border-emerald-600 bg-emerald-600 text-white' : ($meta['class'] === 'amber' ? 'border-amber-500 bg-amber-500 text-white' : 'border-slate-800 bg-slate-800 text-white')) : 'border-slate-200 bg-white text-slate-600' }}">@if($active)✓ @endif{{ $meta['label'] }} <span class="ml-1 opacity-70">{{ $statusCount }}</span></a>
                    @endif
                @endforeach
            </div>
        </div>
    </section>

    <div class="grid gap-2.5 lg:hidden">
        @forelse($priceLists as $priceList)
            @php
                $customer = $priceList->partner?->name ?? $priceList->officialFacility?->facility_name ?? $priceList->officialFacility?->name ?? 'Bảng giá chung';
                $exportShare = $exportShares[(int)$priceList->id] ?? null;
                $statusLabel = match($priceList->status) { 'draft' => 'Nháp', 'pending_approval' => 'Chờ duyệt', 'active' => 'Hiệu lực', 'pending_deactivation' => 'Chờ ngừng', 'rejected' => 'Từ chối', 'inactive' => 'Ngưng', 'archived' => 'Lưu trữ', default => $priceList->status };
                $statusClass = match($priceList->status) { 'active' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'draft' => 'bg-amber-50 text-amber-700 ring-amber-200', 'pending_approval' => 'bg-blue-50 text-blue-700 ring-blue-200', 'rejected' => 'bg-rose-50 text-rose-700 ring-rose-200', default => 'bg-slate-100 text-slate-600 ring-slate-200' };
            @endphp
            <article class="relative rounded-3xl border border-slate-200 bg-white shadow-sm">
                <a href="{{ route('client.pharma.price-lists.show', $priceList->id) }}" class="block rounded-3xl p-4 pr-16">
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
                @if($exportShare)<a href="{{ $exportShare['url'] }}" title="Tải Excel đã xuất" aria-label="Tải Excel {{ $priceList->name }}" class="absolute bottom-3 right-3 z-10 inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 text-base font-black text-emerald-700 shadow-sm" onclick="event.stopPropagation()">↓</a>@else<span class="pointer-events-none absolute bottom-4 right-4 text-xl text-slate-300">›</span>@endif
            </article>
        @empty <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">Bạn chưa có bảng giá nào trong phạm vi quản lý.</div> @endforelse
    </div>

    <section class="hidden overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm lg:block">
        <table class="w-full table-fixed text-left text-sm"><thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Bảng giá</th><th class="px-5 py-3">Khách hàng</th>@if($canApprove)<th class="px-5 py-3">Người phụ trách</th>@endif<th class="px-5 py-3">Mục đích</th><th class="w-[7%] px-5 py-3 text-center">SP</th><th class="px-5 py-3">Hiệu lực</th><th class="w-[10%] px-5 py-3">Trạng thái</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
        @forelse($priceLists as $priceList)
            @php
                $customer = $priceList->partner?->name ?? $priceList->officialFacility?->facility_name ?? $priceList->officialFacility?->name ?? 'Bảng giá chung';
                $exportShare = $exportShares[(int)$priceList->id] ?? null;
                $statusLabel = match($priceList->status) { 'draft' => 'Nháp', 'pending_approval' => 'Chờ duyệt', 'active' => 'Đang hiệu lực', 'pending_deactivation' => 'Chờ ngừng', 'rejected' => 'Từ chối', 'inactive' => 'Ngưng', 'archived' => 'Lưu trữ', default => $priceList->status };
            @endphp
            <tr class="transition hover:bg-slate-50"><td class="px-5 py-4"><a href="{{ route('client.pharma.price-lists.show', $priceList->id) }}" class="font-black text-slate-950 hover:underline">{{ $priceList->name }}</a><div class="mt-1 flex items-center gap-2"><p class="text-xs text-slate-400">{{ $priceList->code }}</p>@if($exportShare)<a href="{{ $exportShare['url'] }}" title="Tải Excel đã xuất" aria-label="Tải Excel {{ $priceList->name }}" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-sm font-black text-emerald-700 hover:bg-emerald-100">↓</a>@endif</div>@if(in_array($priceList->status, ['draft', 'rejected'], true) && $canCreate)<div class="mt-2 flex items-center gap-3 text-xs font-bold"><a href="{{ route('client.pharma.price-lists.edit', $priceList->id) }}" class="text-blue-700 hover:underline">Sửa</a>@if($priceList->status === 'draft')<form method="POST" action="{{ route('client.pharma.price-lists.delete', $priceList->id) }}" onsubmit="return confirm('Xóa bảng giá Nháp này?')">@csrf @method('DELETE')<button class="text-red-600 hover:underline">Xóa</button></form>@endif</div>@endif</td><td class="px-5 py-4 text-slate-700">{{ $customer }}</td>@if($canApprove)<td class="px-5 py-4 text-slate-700">{{ $priceList->manager?->name ?: '—' }}</td>@endif<td class="px-5 py-4 text-slate-600">{{ $priceList->purpose?->name ?: '—' }}</td><td class="px-5 py-4 text-center font-bold">{{ $priceList->items_count }}</td><td class="px-5 py-4 text-slate-600">{{ $priceList->effective_from?->format('d/m/Y') ?: '—' }} → {{ $priceList->effective_to?->format('d/m/Y') ?: '—' }}</td><td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $priceList->status === 'active' ? 'bg-emerald-100 text-emerald-700' : ($priceList->status === 'draft' ? 'bg-amber-100 text-amber-700' : ($priceList->status === 'pending_approval' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600')) }}">{{ $statusLabel }}</span></td></tr>
        @empty <tr><td colspan="{{ $canApprove ? 7 : 6 }}" class="px-5 py-10 text-center text-slate-500">Bạn chưa có bảng giá nào trong phạm vi quản lý.</td></tr> @endforelse
        </tbody></table>
    </section>
    @if($priceLists->hasPages())<div>{{ $priceLists->links() }}</div>@endif
</div>
<script>document.addEventListener('DOMContentLoaded',()=>{const f=document.getElementById('price-list-search-form'),i=document.getElementById('price-list-search-input'),d=document.getElementById('price-list-advanced-filters');if(!f||!i)return;if(d&&window.matchMedia('(min-width: 1024px)').matches)d.open=true;let t;i.addEventListener('input',()=>{clearTimeout(t);t=setTimeout(()=>f.requestSubmit(),350);});});</script>
@endsection
