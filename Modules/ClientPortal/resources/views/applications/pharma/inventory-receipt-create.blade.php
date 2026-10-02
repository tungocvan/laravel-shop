@extends('ClientPortal::layouts.application')
@section('title','Thêm phiếu nhập kho')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle','Workspace Pharma dành cho User')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)
@section('content')
<div class="min-w-0 space-y-4 pb-8">
<section class="rounded-[1.75rem] bg-slate-950 px-5 py-5 text-white shadow-sm sm:px-7">
<a href="{{ route('client.pharma.inventory.receipts') }}" class="inline-flex min-h-10 items-center text-sm font-bold text-slate-300">← Phiếu nhập kho</a>
<p class="mt-2 text-[11px] font-black uppercase tracking-[0.16em] text-slate-400">Receipt · Draft authoring</p>
<h1 class="mt-1 text-2xl font-black">Thêm phiếu nhập</h1>
<p class="mt-1.5 text-sm text-slate-300">Lưu nháp không làm thay đổi tồn kho. Chỉ phiếu được ghi sổ mới cộng số lượng vào kho.</p>
</section>
@if($errors->any())<section class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-bold text-rose-700">{{ $errors->first() }}</section>@endif
<form method="POST" action="{{ route('client.pharma.inventory.receipts.store') }}" class="space-y-4" id="receipt-draft-form">@csrf
<section class="grid gap-3 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-2">
<label class="text-sm font-bold text-slate-700">Ngày nhập *<input type="date" name="receipt_date" value="{{ old('receipt_date', now()->toDateString()) }}" required class="mt-1.5 h-12 w-full rounded-2xl border border-slate-300 px-3 font-normal"></label>
<label class="text-sm font-bold text-slate-700">Nhà cung cấp *<select name="supplier_id" required class="mt-1.5 h-12 w-full rounded-2xl border border-slate-300 bg-white px-3 font-normal"><option value="">Chọn nhà cung cấp</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((string)old('supplier_id')===(string)$supplier->id)>{{ $supplier->name }}@if($supplier->tax_code) · {{ $supplier->tax_code }}@endif</option>@endforeach</select></label>
<label class="text-sm font-bold text-slate-700">Số hóa đơn<input name="invoice_number" value="{{ old('invoice_number') }}" maxlength="100" class="mt-1.5 h-12 w-full rounded-2xl border border-slate-300 px-3 font-normal"></label>
<label class="text-sm font-bold text-slate-700">Ngày hóa đơn<input type="date" name="invoice_date" value="{{ old('invoice_date') }}" class="mt-1.5 h-12 w-full rounded-2xl border border-slate-300 px-3 font-normal"></label>
</section>
<section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
<div class="flex items-center justify-between gap-3"><div><h2 class="font-black text-slate-950">Hàng nhập</h2><p class="mt-1 text-xs text-slate-500">Thuốc + số lô + hạn dùng không được trùng trong cùng phiếu.</p></div><button type="button" id="add-receipt-item" class="min-h-10 rounded-xl border border-slate-300 px-3 text-sm font-black">+ Thêm dòng</button></div>
<div id="receipt-items" class="mt-4 space-y-3"></div>
</section>
<section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm"><label class="text-sm font-bold text-slate-700">Ghi chú<textarea name="notes" rows="3" maxlength="2000" class="mt-1.5 w-full rounded-2xl border border-slate-300 p-3 font-normal">{{ old('notes') }}</textarea></label></section>
<div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"><a href="{{ route('client.pharma.inventory.receipts') }}" class="inline-flex min-h-12 items-center justify-center rounded-2xl border border-slate-300 bg-white px-5 text-sm font-black">Hủy</a><button class="inline-flex min-h-12 items-center justify-center rounded-2xl bg-slate-950 px-5 text-sm font-black text-white">Lưu nháp</button></div>
</form>
<template id="receipt-item-template"><article class="receipt-item rounded-2xl border border-slate-200 bg-slate-50 p-3"><div class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
<label class="text-xs font-bold text-slate-600 xl:col-span-2">Thuốc *<select data-field="medicine_id" required class="mt-1 h-11 w-full rounded-xl border border-slate-300 bg-white px-2 font-normal"><option value="">Chọn thuốc</option>@foreach($medicines as $medicine)<option value="{{ $medicine->id }}">{{ $medicine->medicine_code }} · {{ $medicine->name }}@if($medicine->unit) · {{ $medicine->unit }}@endif</option>@endforeach</select></label>
<label class="text-xs font-bold text-slate-600">Số lô *<input data-field="batch_number" required maxlength="100" class="mt-1 h-11 w-full rounded-xl border border-slate-300 bg-white px-2 font-normal"></label>
<label class="text-xs font-bold text-slate-600">Hạn dùng *<input type="date" data-field="expiry_date" required class="mt-1 h-11 w-full rounded-xl border border-slate-300 bg-white px-2 font-normal"></label>
<label class="text-xs font-bold text-slate-600">Số lượng *<input type="number" step="0.001" min="0.001" data-field="quantity" required class="mt-1 h-11 w-full rounded-xl border border-slate-300 bg-white px-2 font-normal"></label>
<label class="text-xs font-bold text-slate-600">Giá nhập *<input type="number" step="0.0001" min="0" data-field="unit_price_ex_vat" required class="mt-1 h-11 w-full rounded-xl border border-slate-300 bg-white px-2 font-normal"></label>
</div><div class="mt-2 flex items-end justify-between gap-3"><label class="text-xs font-bold text-slate-600">VAT %<input type="number" step="0.01" min="0" max="100" value="0" data-field="vat_rate" class="ml-2 h-10 w-24 rounded-xl border border-slate-300 bg-white px-2 font-normal"></label><button type="button" class="remove-receipt-item min-h-10 rounded-xl px-3 text-sm font-bold text-rose-600">Xóa dòng</button></div></article></template>
</div>
<script>
document.addEventListener('DOMContentLoaded',()=>{const box=document.getElementById('receipt-items'),tpl=document.getElementById('receipt-item-template'),add=document.getElementById('add-receipt-item');let next=0;const append=()=>{const node=tpl.content.cloneNode(true),article=node.querySelector('.receipt-item'),index=next++;article.querySelectorAll('[data-field]').forEach(el=>el.name='items['+index+']['+el.dataset.field+']');article.querySelector('.remove-receipt-item').addEventListener('click',()=>{if(box.children.length>1)article.remove();});box.appendChild(node);};add.addEventListener('click',append);append();});
</script>
@endsection
