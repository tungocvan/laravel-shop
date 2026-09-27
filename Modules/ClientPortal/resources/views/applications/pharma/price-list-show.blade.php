@extends('ClientPortal::layouts.application')

@section('title', 'Chi tiết bảng giá')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Bảng giá do bạn phụ trách')
@section('app-dashboard-route', route('client.pharma.dashboard'))

@section('content')
<div class="mx-auto max-w-6xl space-y-5">
    <a href="{{ route('client.pharma.price-lists') }}" class="inline-flex items-center gap-2 text-sm font-bold text-slate-600 hover:text-slate-950">← Bảng giá của tôi</a>
    @php $customer = $priceList->partner?->name ?? $priceList->officialFacility?->facility_name ?? $priceList->officialFacility?->name ?? 'Bảng giá chung'; @endphp
    <section class="rounded-[2rem] bg-slate-950 px-5 py-6 text-white shadow-sm sm:px-7">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"><div><p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-300">Price List</p><h1 class="mt-2 text-2xl font-black sm:text-3xl">{{ $priceList->name }}</h1><p class="mt-2 text-sm text-slate-300">{{ $customer }} · {{ $priceList->purpose?->name ?: 'Chưa có mục đích' }}</p></div><span class="w-fit rounded-full bg-white/10 px-3 py-1.5 text-xs font-bold">{{ $priceList->status }}</span></div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach([['Mã bảng giá',$priceList->code],['Số sản phẩm',$priceList->items_count],['Hiệu lực từ',$priceList->effective_from?->format('d/m/Y')],['Hiệu lực đến',$priceList->effective_to?->format('d/m/Y')]] as [$label,$value])
                <div><p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ $label }}</p><p class="mt-1.5 font-bold text-slate-800">{{ $value ?: '—' }}</p></div>
            @endforeach
        </div>
        @if($priceList->notes)<p class="mt-5 rounded-2xl bg-slate-50 p-4 text-sm leading-6 text-slate-600">{{ $priceList->notes }}</p>@endif
    </section>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4"><p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">Sản phẩm</p><h2 class="mt-1 text-lg font-black text-slate-950">{{ $priceList->items_count }} sản phẩm trong bảng giá</h2></div>
        <div class="divide-y divide-slate-100">
            @forelse($priceList->items as $item)
                <article class="grid gap-4 px-5 py-4 sm:grid-cols-2 lg:grid-cols-5">
                    <div class="lg:col-span-2"><p class="font-black text-slate-900">{{ $item->variant?->medicine?->name ?? $item->variant?->sku ?? 'SKU #'.$item->medicine_variant_id }}</p><p class="mt-1 text-xs text-slate-400">{{ $item->variant?->sku }}@if($item->package) · {{ $item->package->name ?? $item->package->packaging_specification ?? '' }}@endif</p></div>
                    <div><p class="text-xs font-bold text-slate-400">Giá kê khai</p><p class="mt-1 font-bold tabular-nums">{{ $item->declared_price_snapshot !== null ? number_format((float)$item->declared_price_snapshot,0,',','.') : '—' }}</p></div>
                    <div><p class="text-xs font-bold text-slate-400">Giá bán CT</p><p class="mt-1 font-black tabular-nums text-slate-950">{{ $item->company_sale_price !== null ? number_format((float)$item->company_sale_price,0,',','.') : '—' }}</p></div>
                    <div><p class="text-xs font-bold text-slate-400">Giá thu / Giá HĐ</p><p class="mt-1 text-sm tabular-nums text-slate-700">{{ $item->actual_receivable_price !== null ? number_format((float)$item->actual_receivable_price,0,',','.') : '—' }} / {{ $item->invoice_price !== null ? number_format((float)$item->invoice_price,0,',','.') : '—' }}</p></div>
                </article>
            @empty <p class="px-5 py-8 text-center text-sm text-slate-500">Bảng giá chưa có sản phẩm.</p> @endforelse
        </div>
    </section>
</div>
@endsection
