@extends('ClientPortal::layouts.application')

@section('title', $issue->number)
@section('app-name', $application['name'])
@section('app-subtitle', 'Workspace Pharma dành cho User')
@section('app-dashboard-route', route('client.pharma.dashboard'))

@section('content')
@php
    $money = fn ($value) => number_format((float) $value, 0, ',', '.').' đ';
    $statusLabels = ['draft'=>'Nháp','posted'=>'Đã xuất','cancelled'=>'Đã hủy'];
    $source = ($issue->issue_source ?? 'normal') === 'bid' ? 'Theo kết quả trúng thầu' : 'Theo bảng giá';
    $total = $issue->items->sum(fn($item)=>(float)$item->quantity*(float)$item->unit_price);
@endphp
<div class="min-h-[calc(100vh-5rem)] bg-slate-50 pb-24 lg:pb-8">
    <header class="sticky top-0 z-30 -mx-4 border-b border-slate-200 bg-white/95 px-4 py-4 backdrop-blur sm:-mx-6 sm:px-6 lg:static lg:mx-0 lg:rounded-3xl lg:border lg:px-6">
        <div class="relative flex items-center justify-center">
            <a href="{{ route('client.pharma.inventory.issues') }}" class="absolute left-0 inline-flex h-11 w-11 items-center justify-center rounded-full text-2xl text-slate-900 active:scale-95" aria-label="Quay lại">←</a>
            <h1 class="px-12 text-center text-xl font-black text-slate-950">Chi tiết đơn hàng</h1>
        </div>
    </header>

    <main class="mx-auto mt-4 max-w-4xl space-y-4">
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div><p class="text-xs font-black uppercase tracking-wide text-slate-500">{{ $issue->number }}</p><h2 class="mt-1 text-xl font-black text-slate-950">{{ $issue->recipient_name ?: 'Chưa xác định nơi nhận' }}</h2></div>
                <span class="rounded-full px-3 py-1 text-xs font-black {{ $issue->status==='posted' ? 'bg-emerald-100 text-emerald-800' : ($issue->status==='cancelled' ? 'bg-slate-200 text-slate-600' : 'bg-amber-100 text-amber-800') }}">{{ $statusLabels[$issue->status] ?? $issue->status }}</span>
            </div>
            <dl class="mt-5 grid grid-cols-2 gap-x-4 gap-y-4 border-t border-slate-100 pt-4 text-sm">
                <div><dt class="text-slate-500">Nguồn đơn hàng</dt><dd class="mt-1 font-black text-slate-900">{{ $source }}</dd></div>
                <div><dt class="text-slate-500">Ngày lập</dt><dd class="mt-1 font-black text-slate-900">{{ $issue->issue_date?->format('d/m/Y') }}</dd></div>
                <div><dt class="text-slate-500">Người phụ trách</dt><dd class="mt-1 font-black text-slate-900">{{ $issue->manager?->name ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">Bảng giá</dt><dd class="mt-1 font-black text-slate-900">{{ $issue->priceList?->name ?: '—' }}</dd></div>
            </dl>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-end justify-between gap-3"><div><p class="text-xs font-black uppercase tracking-wide text-slate-500">Sản phẩm</p><h2 class="mt-1 text-lg font-black text-slate-950">{{ number_format($issue->items_count) }} sản phẩm</h2></div><p class="text-right text-lg font-black text-slate-950">{{ $money($total) }}</p></div>
            <div class="mt-4 divide-y divide-slate-100">
                @foreach($issue->items as $item)
                    <article class="py-4 first:pt-0 last:pb-0">
                        <h3 class="font-black text-slate-950">{{ $item->medicine?->name ?: 'Sản phẩm #'.$item->medicine_id }}</h3>
                        <div class="mt-2 grid grid-cols-2 gap-3 text-sm">
                            <div><p class="text-xs text-slate-500">Số lượng</p><p class="font-bold text-slate-800">{{ rtrim(rtrim(number_format((float)$item->quantity,3,'.',''),'0'),'.') }}</p></div>
                            <div class="text-right"><p class="text-xs text-slate-500">Đơn giá</p><p class="font-bold text-slate-800">{{ $money($item->unit_price) }}</p></div>
                        </div>
                        @if($item->batch_number || $item->expiry_date)
                            <p class="mt-2 text-xs text-slate-500">Lô {{ $item->batch_number ?: '—' }} · HSD {{ $item->expiry_date?->format('d/m/Y') ?: '—' }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>

        @if($issue->notes)
            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-black uppercase tracking-wide text-slate-500">Ghi chú</p><p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $issue->notes }}</p></section>
        @endif
    </main>
</div>
@endsection
