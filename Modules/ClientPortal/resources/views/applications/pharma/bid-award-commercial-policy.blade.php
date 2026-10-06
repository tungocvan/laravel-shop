@extends('ClientPortal::layouts.application')
@section('title','Thiết lập chính sách kinh doanh')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)
@section('content')
<div class="mx-auto w-full max-w-5xl space-y-4 px-1 pb-24 sm:px-3 lg:px-4">
<header class="flex min-h-16 items-center gap-3 border-b border-slate-200 bg-white pb-4"><a href="{{ route('client.pharma.bid-awards.show',$scope) }}" aria-label="Quay lại kết quả trúng thầu" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-lg font-black text-slate-700 shadow-sm">←</a><div class="min-w-0"><h1 class="truncate text-lg font-black text-slate-950">Chính sách kinh doanh</h1><p class="truncate text-xs text-slate-500">{{ $award->bidding_notice_code }}</p></div></header>
<section class="rounded-[28px] bg-slate-950 p-5 text-white sm:p-6"><p class="text-[11px] font-black uppercase tracking-[.18em] text-slate-300">Commercial Policy</p><h1 class="mt-1 text-2xl font-black">Thiết lập chính sách kinh doanh</h1><p class="mt-2 text-sm text-slate-300">Đã mở vì KQLCNT này có phân bổ số lượng đang hiệu lực.</p></section>
@if(session('success'))<div class="rounded-2xl bg-emerald-50 p-4 text-sm font-bold text-emerald-700">{{ session('success') }}</div>@endif
@if($errors->any())<div class="rounded-2xl bg-rose-50 p-4 text-sm text-rose-700">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('client.pharma.bid-awards.commercial-policy.store',$scope) }}" class="space-y-3">@csrf
<div class="relative"><input type="search" data-policy-product-search data-pwa-local-filter data-pwa-local-filter-items="[data-policy-product-card]" data-pwa-local-filter-clear="[data-policy-product-clear]" data-pwa-local-filter-empty="[data-policy-product-empty]" placeholder="Tìm sản phẩm..." autocomplete="off" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 pr-11 text-sm"><button type="button" data-policy-product-clear class="absolute right-1 top-1 hidden h-9 w-9 rounded-lg text-slate-400" aria-label="Xóa tìm sản phẩm">×</button></div><p data-policy-product-empty class="hidden rounded-xl bg-slate-50 p-3 text-sm text-slate-500">Không có sản phẩm phù hợp.</p>
@foreach($products as $product)
@php($policy=$policies->get($product->id))
<label data-policy-product-card data-name="{{ str($product->medicine_name.' '.$product->active_ingredient.' '.$product->concentration)->lower() }}" class="block rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><span class="block font-black">{{ $product->medicine_name }}</span><span class="mt-1 block text-xs text-slate-500">{{ $product->active_ingredient }} {{ $product->concentration }}</span><span class="mt-3 block text-xs font-bold text-slate-600">Chính sách (%)</span><input inputmode="decimal" name="percentages[{{ $product->id }}]" value="{{ old('percentages.'.$product->id,$policy ? rtrim(rtrim(number_format((float)$policy->commission_percentage,4,'.',''),'0'),'.') : '') }}" placeholder="0 - 100" class="mt-1.5 h-12 w-full rounded-xl border border-slate-200 px-3 text-base"></label>
@endforeach
<div class="sticky bottom-3 z-20 rounded-2xl bg-white/95 p-2 shadow-xl ring-1 ring-slate-200 backdrop-blur"><div class="flex flex-col gap-2 sm:flex-row sm:justify-end">@if($commercialPolicyReady)<a href="{{ route('client.pharma.bid-awards.manager-assignment',$scope) }}" class="inline-flex min-h-12 items-center justify-center rounded-2xl border border-slate-300 bg-white px-5 text-sm font-black text-slate-700">Phân công User quản lý</a>@else<div aria-disabled="true" class="inline-flex min-h-12 items-center justify-center rounded-2xl border border-slate-200 bg-slate-100 px-5 text-center text-sm font-black text-slate-400">Phân công User quản lý · lưu CSKD trước</div>@endif<button class="min-h-12 rounded-2xl bg-slate-950 px-5 text-sm font-black text-white">Lưu chính sách & tiếp tục</button></div></div>
</form></div>

@endsection
