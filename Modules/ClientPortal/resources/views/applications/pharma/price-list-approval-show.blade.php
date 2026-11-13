@extends('ClientPortal::layouts.application')
@section('title','Phê duyệt bảng giá')
@section('app-name',$applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle','Kiểm tra trước khi kích hoạt')
@section('app-dashboard-route',route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)
@section('content')
@php $customer=$priceList->partner?->name ?? '—'; @endphp
<div class="mx-auto max-w-6xl space-y-5">
<a href="{{ route('client.pharma.price-list-approvals') }}" class="text-sm font-bold text-slate-600">← Hàng chờ phê duyệt</a>
<section class="rounded-[2rem] bg-slate-950 px-6 py-6 text-white"><p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-300">Approval Review</p><h1 class="mt-2 text-3xl font-black">{{ $priceList->name }}</h1><p class="mt-2 text-sm text-slate-300">{{ $customer }} · {{ $priceList->purpose?->name ?: '—' }}</p></section>
@if(session('success'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-800">{{ session('success') }}</div>@endif
@if($errors->any())<div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-bold text-red-700">{{ $errors->first() }}</div>@endif
<section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"><div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">@foreach([['Người phụ trách',$priceList->manager?->name],['Người gửi',$priceList->submitter?->name],['Gửi lúc',$priceList->submitted_at?->format('H:i d/m/Y')],['Sản phẩm',$priceList->items_count],['Bảng giá gốc',$priceList->sourcePriceList?->name],['Hiệu lực từ',$priceList->effective_from?->format('d/m/Y')],['Hiệu lực đến',$priceList->effective_to?->format('d/m/Y')]] as [$label,$value])<div><p class="text-xs font-bold uppercase text-slate-400">{{ $label }}</p><p class="mt-1 font-bold text-slate-800">{{ $value ?: '—' }}</p></div>@endforeach</div></section>
<section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
<div class="border-b p-5"><p class="text-xs font-bold uppercase text-slate-400">Sản phẩm</p><h2 class="mt-1 text-lg font-black">{{ $priceList->items_count }} sản phẩm cần kiểm tra</h2><p class="mt-1 text-sm text-slate-500">Người phê duyệt có thể điều chỉnh Giá Bán (VAT), Giá thu về, Giá xuất hóa đơn hoặc loại sản phẩm không duyệt trước khi kích hoạt. Mọi thay đổi đều được ghi audit.</p></div>
<div class="divide-y">
@foreach($priceList->items as $item)
<article class="grid gap-4 px-5 py-4 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,0.65fr)_minmax(0,3fr)_auto] lg:items-end">
<div><p class="font-black">{{ $item->variant?->medicine?->name ?? $item->variant?->sku }}</p><p class="text-xs text-slate-400">{{ $item->variant?->sku }}</p></div>
<div><p class="text-xs font-bold text-slate-400">Giá kê khai</p><p class="font-bold">{{ $item->declared_price_snapshot!==null?number_format((float)$item->declared_price_snapshot,0,',','.'):'—' }}</p></div>
@if(!$selfApprovalBlocked)
<form method="POST" action="{{ route('client.pharma.price-list-approvals.items.update', [$priceList->id,$item->id]) }}" class="grid grid-cols-1 gap-2 sm:grid-cols-3 sm:items-end">@csrf @method('PUT')<label class="min-w-0"><span class="mb-1 block text-xs font-bold text-slate-400">Giá Bán (VAT)</span><input type="text" inputmode="numeric" data-approval-money name="company_sale_price" value="{{ $item->company_sale_price!==null?number_format((float)$item->company_sale_price,0,',','.') : '' }}" required class="h-11 w-full rounded-xl border border-slate-300 px-3 text-right font-black tabular-nums"></label><label class="min-w-0"><span class="mb-1 block text-xs font-bold text-slate-400">Giá thu về</span><input type="text" inputmode="numeric" data-approval-money name="actual_receivable_price" value="{{ $item->actual_receivable_price!==null?number_format((float)$item->actual_receivable_price,0,',','.') : '' }}" required class="h-11 w-full rounded-xl border border-slate-300 px-3 text-right font-black tabular-nums"></label><label class="min-w-0"><span class="mb-1 block text-xs font-bold text-slate-400">Giá xuất hóa đơn</span><input type="text" inputmode="numeric" data-approval-money name="invoice_price" value="{{ $item->invoice_price!==null?number_format((float)$item->invoice_price,0,',','.') : '' }}" required class="h-11 w-full rounded-xl border border-slate-300 px-3 text-right font-black tabular-nums"></label><button class="h-11 rounded-xl border border-blue-200 px-3 text-xs font-black text-blue-700 sm:col-span-3">Cập nhật 3 giá</button></form>
<form method="POST" action="{{ route('client.pharma.price-list-approvals.items.delete', [$priceList->id,$item->id]) }}" onsubmit="return confirm('Loại sản phẩm này khỏi bảng giá đang duyệt?')">@csrf @method('DELETE')<button class="h-10 rounded-xl border border-red-200 px-3 text-xs font-black text-red-600">Xóa</button></form>
@else
<div class="grid gap-3 sm:grid-cols-3">@foreach([['Giá Bán (VAT)',$item->company_sale_price],['Giá thu về',$item->actual_receivable_price],['Giá xuất hóa đơn',$item->invoice_price]] as [$label,$value])<div><p class="text-xs font-bold text-slate-400">{{ $label }}</p><p class="font-black tabular-nums">{{ $value!==null?number_format((float)$value,0,',','.'):'—' }}</p></div>@endforeach</div><div></div>
@endif
</article>
@endforeach
</div></section>
@if($selfApprovalBlocked)
<section class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5"><p class="font-black text-emerald-950">Bảng giá do bạn tạo/phụ trách</p><p class="mt-1 text-sm text-emerald-800">Với quyền phê duyệt, bạn có thể tự phê duyệt và kích hoạt. Điều chỉnh sản phẩm và thao tác từ chối vẫn dành cho quy trình phê duyệt độc lập.</p><form method="POST" action="{{ route('client.pharma.price-list-approvals.approve',$priceList->id) }}" class="mt-4" onsubmit="return confirm('Phê duyệt và kích hoạt bảng giá của bạn?')">@csrf<button class="rounded-2xl bg-emerald-700 px-5 py-3 text-sm font-black text-white">Phê duyệt & Kích hoạt</button></form></section>
@else
<section class="grid gap-4 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm lg:grid-cols-2"><form method="POST" action="{{ route('client.pharma.price-list-approvals.approve',$priceList->id) }}" onsubmit="return confirm('Phê duyệt và kích hoạt bảng giá này?')">@csrf<p class="font-black">Phê duyệt & kích hoạt</p><p class="mt-1 text-sm text-slate-500">Bảng giá sẽ chuyển ACTIVE và ghi nhận người phê duyệt.</p><button class="mt-4 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-black text-white">Phê duyệt & Kích hoạt</button></form><form method="POST" action="{{ route('client.pharma.price-list-approvals.reject',$priceList->id) }}">@csrf<label class="font-black">Từ chối</label><textarea name="rejection_reason" required maxlength="1000" rows="3" class="mt-2 w-full rounded-2xl border border-slate-300 p-3" placeholder="Nhập lý do từ chối..."></textarea><button class="mt-3 rounded-2xl border border-red-300 px-5 py-3 text-sm font-black text-red-600">Từ chối bảng giá</button></form></section>
@endif
</div>
<script>document.addEventListener('DOMContentLoaded',()=>{const digits=v=>String(v||'').replace(/\D/g,'');const fmt=v=>digits(v).replace(/\B(?=(\d{3})+(?!\d))/g,'.');document.querySelectorAll('[data-approval-money]').forEach(input=>{input.value=fmt(input.value);input.addEventListener('input',()=>input.value=fmt(input.value));input.form?.addEventListener('submit',()=>input.value=digits(input.value));});});</script>
@endsection