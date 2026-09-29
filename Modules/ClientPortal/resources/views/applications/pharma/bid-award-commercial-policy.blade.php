@extends('ClientPortal::layouts.application')
@section('title','Thiết lập chính sách kinh doanh')
@section('content')
<div class="mx-auto w-full max-w-5xl space-y-4 px-3 py-4 sm:px-5 lg:px-6">
<a href="{{ route('client.pharma.bid-awards.show',$scope) }}" class="inline-flex min-h-11 items-center text-sm font-bold text-slate-600">← Quay lại kết quả trúng thầu</a>
<section class="rounded-[28px] bg-slate-950 p-5 text-white sm:p-6"><p class="text-[11px] font-black uppercase tracking-[.18em] text-slate-300">Commercial Policy</p><h1 class="mt-1 text-2xl font-black">Thiết lập chính sách kinh doanh</h1><p class="mt-2 text-sm text-slate-300">Đã mở vì KQLCNT này có phân bổ số lượng đang hiệu lực.</p></section>
@if(session('success'))<div class="rounded-2xl bg-emerald-50 p-4 text-sm font-bold text-emerald-700">{{ session('success') }}</div>@endif
@if($errors->any())<div class="rounded-2xl bg-rose-50 p-4 text-sm text-rose-700">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('client.pharma.bid-awards.commercial-policy.store',$scope) }}" class="space-y-3">@csrf
@foreach($products as $product)
@php($policy=$policies->get($product->id))
<label class="block rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><span class="block font-black">{{ $product->medicine_name }}</span><span class="mt-1 block text-xs text-slate-500">{{ $product->active_ingredient }} {{ $product->concentration }}</span><span class="mt-3 block text-xs font-bold text-slate-600">Chính sách (%)</span><input inputmode="decimal" name="percentages[{{ $product->id }}]" value="{{ old('percentages.'.$product->id,$policy?->commission_percentage) }}" placeholder="0 - 100" class="mt-1.5 h-12 w-full rounded-xl border border-slate-200 px-3 text-base"></label>
@endforeach
<div class="sticky bottom-3 flex justify-end"><button class="min-h-12 rounded-2xl bg-slate-950 px-5 text-sm font-black text-white shadow-lg">Lưu chính sách</button></div>
</form></div>
@endsection
