@extends('ClientPortal::layouts.application')

@section('title', 'Tạo bảng giá')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Lập bảng giá cho khách hàng')
@section('app-dashboard-route', route('client.pharma.dashboard'))

@section('content')
<div class="mx-auto max-w-7xl space-y-5">
    <div class="flex items-center justify-between gap-3">
        <a href="{{ route('client.pharma.price-lists') }}" class="text-sm font-bold text-slate-600">← Bảng giá của tôi</a>
        <span class="rounded-full bg-amber-100 px-3 py-1.5 text-xs font-bold text-amber-800">Lưu ở trạng thái Nháp</span>
    </div>

    <section class="rounded-[2rem] bg-slate-950 px-5 py-6 text-white shadow-sm sm:px-7">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-300">Create Price List</p>
        <h1 class="mt-2 text-2xl font-black sm:text-3xl">Tạo bảng giá cho khách hàng</h1>
        <p class="mt-2 text-sm text-slate-300">Bạn là người phụ trách bảng giá này. Sau khi kiểm tra, bảng giá Nháp có thể được gửi sang hàng chờ phê duyệt.</p>
    </section>

    @if($errors->any())<div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>@endif

    <form method="POST" action="{{ route('client.pharma.price-lists.store') }}" class="space-y-5">
        @csrf
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-5"><p class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">01 · Khách hàng & mục đích</p><h2 class="mt-1 text-lg font-black text-slate-950">Thông tin bảng giá</h2></div>
            <div class="grid gap-4 lg:grid-cols-2">
                <label><span class="mb-1.5 block text-xs font-bold text-slate-500">Tên bảng giá *</span><input name="name" value="{{ old('name') }}" required maxlength="255" class="h-12 w-full rounded-2xl border border-slate-300 px-4" placeholder="VD: Bảng giá BV An Bình Q4/2026"></label>
                <label><span class="mb-1.5 block text-xs font-bold text-slate-500">Khách hàng *</span><select name="partner_id" required class="h-12 w-full rounded-2xl border border-slate-300 px-4"><option value="">Chọn khách hàng</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" @selected((string)old('partner_id') === (string)$customer->id)>{{ $customer->name }}@if($customer->tax_code) · {{ $customer->tax_code }}@endif</option>@endforeach</select></label>
                <label><span class="mb-1.5 block text-xs font-bold text-slate-500">Mục đích *</span><select name="purpose_id" required class="h-12 w-full rounded-2xl border border-slate-300 px-4"><option value="">Chọn mục đích</option>@foreach($purposes as $purpose)<option value="{{ $purpose->id }}" @selected((string)old('purpose_id') === (string)$purpose->id)>{{ $purpose->name }}</option>@endforeach</select></label>
                <div class="grid grid-cols-2 gap-3"><label><span class="mb-1.5 block text-xs font-bold text-slate-500">Hiệu lực từ *</span><input type="date" name="effective_from" value="{{ old('effective_from', now()->toDateString()) }}" required class="h-12 w-full rounded-2xl border border-slate-300 px-3"></label><label><span class="mb-1.5 block text-xs font-bold text-slate-500">Đến *</span><input type="date" name="effective_to" value="{{ old('effective_to', now()->addMonth()->toDateString()) }}" required class="h-12 w-full rounded-2xl border border-slate-300 px-3"></label></div>
            </div>
            <label class="mt-4 block"><span class="mb-1.5 block text-xs font-bold text-slate-500">Ghi chú</span><textarea name="notes" rows="2" maxlength="1000" class="w-full rounded-2xl border border-slate-300 px-4 py-3">{{ old('notes') }}</textarea></label>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-5">
                <p class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">02 · Sản phẩm & giá</p>
                <div class="mt-2 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between"><div><h2 class="text-lg font-black text-slate-950">Chọn thuốc cho bảng giá</h2><p class="mt-1 text-sm text-slate-500">Giá bán CT không được vượt Giá kê khai.</p></div><label class="w-full lg:w-80"><span class="mb-1 block text-xs font-bold text-slate-500">Lọc nhanh sản phẩm</span><input id="product-filter" type="search" class="h-11 w-full rounded-2xl border border-slate-300 px-4 text-sm" placeholder="Tên thuốc, hoạt chất, SĐK..."></label></div>
            </div>
            <div class="max-h-[620px] overflow-auto">
                <table class="w-full min-w-[850px] text-left text-sm">
                    <thead class="sticky top-0 bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500"><tr><th class="w-12 px-4 py-3">Chọn</th><th class="px-4 py-3">Thuốc</th><th class="px-4 py-3">Hoạt chất</th><th class="px-4 py-3 text-right">Giá kê khai</th><th class="w-48 px-4 py-3">Giá bán CT *</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                    @foreach($products as $product)
                        @php $medicine = $product->medicine; $declared = $medicine?->declared_price; @endphp
                        <tr class="product-row" data-search="{{ mb_strtolower(($medicine?->name ?? '').' '.($medicine?->active_ingredients ?? '').' '.($medicine?->registration_number ?? '').' '.$product->sku) }}">
                            <td class="px-4 py-3"><input type="checkbox" name="selected[{{ $product->id }}]" value="1" @checked(old('selected.'.$product->id)) class="h-5 w-5 rounded border-slate-300"></td>
                            <td class="px-4 py-3"><p class="font-black text-slate-900">{{ $medicine?->name }}</p><p class="mt-1 text-xs text-slate-400">{{ $product->sku }} · {{ $medicine?->packaging_specification }}</p></td>
                            <td class="px-4 py-3 text-slate-600">{{ $medicine?->active_ingredients ?: '—' }}</td>
                            <td class="px-4 py-3 text-right font-bold tabular-nums">{{ $declared !== null ? number_format((float)$declared,0,',','.') : '—' }}</td>
                            <td class="px-4 py-3"><input type="number" min="0" step="1" name="company_price[{{ $product->id }}]" value="{{ old('company_price.'.$product->id, $declared !== null ? (int)$declared : '') }}" class="h-10 w-full rounded-xl border border-slate-300 px-3 text-right font-bold tabular-nums" placeholder="0"></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <div class="sticky bottom-4 flex justify-end"><button type="submit" class="rounded-2xl bg-slate-950 px-6 py-3.5 text-sm font-black text-white shadow-lg">Lưu bảng giá Nháp</button></div>
    </form>
</div>
<script>document.addEventListener('DOMContentLoaded',()=>{const input=document.getElementById('product-filter');if(!input)return;input.addEventListener('input',()=>{const q=input.value.trim().toLowerCase();document.querySelectorAll('.product-row').forEach(row=>row.classList.toggle('hidden',q!==''&&!row.dataset.search.includes(q)));});});</script>
@endsection
