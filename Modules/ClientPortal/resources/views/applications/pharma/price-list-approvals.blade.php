@extends('ClientPortal::layouts.application')
@section('title', 'Duyệt bảng giá')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Hàng chờ phê duyệt')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', '1')
@section('hide-mobile-navigation', '1')
@section('content')
<div class="mx-auto max-w-7xl space-y-5">
@if(session('success'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-800">{{ session('success') }}</div>@endif
<div class="flex items-center justify-between gap-3"><a href="{{ route('client.pharma.price-lists') }}" class="text-sm font-bold text-slate-600">← Bảng giá của tôi</a></div>
<section class="rounded-[2rem] bg-slate-950 px-6 py-6 text-white"><p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-300">Approval Queue</p><h1 class="mt-2 text-3xl font-black">Hàng chờ phê duyệt</h1><p class="mt-2 text-sm text-slate-300">Chỉ gồm bảng giá khách hàng đã được User gửi duyệt.</p></section>
<form id="approval-search-form" method="GET" class="rounded-3xl border border-slate-200 bg-white p-4"><div class="relative"><input id="approval-search-input" type="search" name="q" value="{{ $search }}" autocomplete="off" data-pwa-debounced-search="350" data-pwa-search-region="#approval-results-region" data-pwa-search-clear="#approval-search-clear" class="h-11 w-full rounded-2xl border border-slate-300 px-4 pr-11" placeholder="Tên, mã bảng giá, khách hàng..."><x-native-touch id="approval-search-clear" type="button" data-pwa-search-clear-button="#approval-search-input" class="{{ $search === '' ? 'hidden ' : '' }}absolute right-2 top-1.5 inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-500" aria-label="Xóa tìm kiếm">×</x-native-touch></div></form>
<div id="approval-results-region">
<section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm"><div class="overflow-x-auto"><table class="w-full min-w-[900px] text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Bảng giá</th><th class="px-5 py-3">Khách hàng</th><th class="px-5 py-3">Người gửi</th><th class="px-5 py-3">Bảng giá gốc</th><th class="px-5 py-3 text-center">SP</th><th class="px-5 py-3">Gửi lúc</th></tr></thead><tbody id="approval-results" class="divide-y divide-slate-100">
@forelse($priceLists as $list)<tr data-approval-item class="hover:bg-slate-50"><td class="px-5 py-4"><a href="{{ route('client.pharma.price-list-approvals.show',$list->id) }}" class="font-black text-slate-950 hover:underline">{{ $list->name }}</a><p class="text-xs text-slate-400">{{ $list->code }}</p></td><td class="px-5 py-4">{{ $list->partner?->name ?: '—' }}</td><td class="px-5 py-4">{{ $list->submitter?->name ?? $list->manager?->name ?? '—' }}</td><td class="px-5 py-4">{{ $list->sourcePriceList?->name ?: '—' }}</td><td class="px-5 py-4 text-center font-bold">{{ $list->items_count }}</td><td class="px-5 py-4">{{ $list->submitted_at?->format('H:i d/m/Y') ?: '—' }}</td></tr>
@empty<tr><td colspan="6" class="p-10 text-center text-slate-500">Không có bảng giá nào đang chờ phê duyệt.</td></tr>@endforelse
</tbody></table></div></section>
@if($priceLists->hasMorePages())<div id="approval-load-more-wrap" class="pt-4 text-center"><a data-pwa-load-more data-pwa-load-more-target="#approval-results" data-pwa-load-more-items="[data-approval-item]" data-pwa-load-more-wrap="#approval-load-more-wrap" href="{{ $priceLists->nextPageUrl() }}" class="inline-flex min-h-11 items-center justify-center rounded-2xl border border-slate-300 bg-white px-6 text-sm font-black text-slate-700">Xem thêm</a></div>@endif
</div>
</div>
@endsection