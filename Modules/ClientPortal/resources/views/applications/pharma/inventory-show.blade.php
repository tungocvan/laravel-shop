@extends('ClientPortal::layouts.application')

@section('title', 'Thẻ kho · '.($balance->medicine?->name ?? 'Pharma'))
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Workspace Pharma dành cho User')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)

@section('content')
@php
    $money = fn ($value) => $value === null ? '—' : number_format((float) $value, 0, ',', '.').' đ';
    $quantity = fn ($value) => rtrim(rtrim(number_format((float) $value, 3, '.', ''), '0'), '.');
    $movementLabels = ['opening'=>'Tồn đầu kỳ','receipt'=>'Nhập kho','receipt_reversal'=>'Hoàn tác nhập','issue'=>'Xuất kho','issue_reversal'=>'Hoàn tác xuất'];
@endphp
<div class="min-w-0 space-y-4 overflow-x-hidden pb-8">
    <section class="rounded-[1.75rem] bg-slate-950 px-5 py-5 text-white shadow-sm sm:px-7 sm:py-6">
        <a href="{{ route('client.pharma.inventory') }}" class="inline-flex min-h-11 items-center rounded-xl px-1 text-sm font-bold text-slate-300 hover:text-white">← Tồn kho</a>
        <p class="mt-2 text-[11px] font-black uppercase tracking-[0.16em] text-slate-400">Inventory · Stock card</p>
        <h1 class="mt-1.5 text-2xl font-black tracking-tight sm:text-3xl">Thẻ kho · {{ $balance->medicine?->name }}</h1>
        <p class="mt-1.5 text-sm leading-6 text-slate-300">{{ $balance->medicine?->active_ingredients ?: 'Chưa có hoạt chất' }}</p>
    </section>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-bold text-slate-500">Tổng tồn hiện tại</p><p class="mt-2 text-xl font-black text-slate-950">{{ $quantity($total_quantity_on_hand) }} {{ $balance->medicine?->unit }}</p></div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-bold text-slate-500">Số lô đang theo dõi</p><p class="mt-2 text-xl font-black text-slate-950">{{ number_format($balances->count()) }}</p></div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-bold text-slate-500">Quy cách</p><p class="mt-2 text-sm font-bold leading-5 text-slate-800">{{ $balance->medicine?->packaging_specification ?: '—' }}</p></div>
        @if($canViewCosts)
            <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm"><p class="text-xs font-bold text-emerald-700">Giá trị tồn</p><p class="mt-2 text-xl font-black text-emerald-950">{{ $money($inventory_value) }}</p><p class="mt-1 text-xs text-emerald-700">Theo giá vốn hiệu lực từng lô</p></div>
        @else
            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 shadow-sm"><p class="text-xs font-bold text-slate-500">Đơn vị</p><p class="mt-2 text-xl font-black text-slate-950">{{ $balance->medicine?->unit ?: '—' }}</p></div>
        @endif
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-wrap items-end justify-between gap-2">
            <div><p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">Lots</p><h2 class="mt-1 text-lg font-black text-slate-950">Tồn theo lô / hạn dùng</h2></div>
            <p class="text-xs text-slate-500">{{ number_format($balances->count()) }} lô</p>
        </div>
        <div class="mt-4 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($balances as $lot)
                <div class="min-w-0 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="text-xs font-bold text-slate-500">Lô</p><p class="truncate font-black text-slate-950">{{ $lot->batch_number }}</p></div><strong class="whitespace-nowrap text-slate-950">{{ $quantity($lot->quantity_on_hand) }} {{ $balance->medicine?->unit }}</strong></div>
                    <p class="mt-2 text-xs text-slate-500">HSD {{ $lot->expiry_date?->format('d/m/Y') }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-wrap items-end justify-between gap-2">
            <div><p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">Stock ledger</p><h2 class="mt-1 text-lg font-black text-slate-950">Thẻ kho sản phẩm</h2><p class="mt-1 text-xs text-slate-500">Toàn bộ nhập, xuất, hoàn tác và tồn đầu kỳ của sản phẩm theo thời gian.</p></div>
            <p class="text-xs text-slate-500">{{ number_format($movements->count()) }} giao dịch</p>
        </div>

        <div class="mt-4 space-y-3 xl:hidden">
            @forelse($movements as $movement)
                @php $incoming=$movement['quantity_delta'] > 0; $outgoing=$movement['quantity_delta'] < 0; @endphp
                <article class="rounded-3xl border border-slate-200 bg-slate-50/70 p-4">
                    <div class="flex items-start justify-between gap-3"><div><p class="font-black text-slate-950">{{ $movementLabels[$movement['type']] ?? $movement['type'] }}</p><p class="mt-1 text-xs text-slate-500">{{ $movement['created_at']?->format('d/m/Y H:i') }}</p></div><span class="whitespace-nowrap rounded-full px-2.5 py-1 text-sm font-black {{ $incoming ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">{{ $incoming ? '+' : '' }}{{ $quantity($movement['quantity_delta']) }}</span></div>
                    <div class="mt-3 grid grid-cols-2 gap-2 border-t border-slate-200 pt-3 text-xs"><div><span class="font-bold text-slate-500">Lô</span><strong class="mt-1 block text-slate-800">{{ $movement['batch_number'] }}</strong></div><div><span class="font-bold text-slate-500">HSD</span><strong class="mt-1 block text-slate-800">{{ $movement['expiry_date']?->format('d/m/Y') }}</strong></div><div><span class="font-bold text-slate-500">Nhập</span><strong class="mt-1 block text-emerald-700">{{ $incoming ? $quantity($movement['quantity_delta']) : '—' }}</strong></div><div><span class="font-bold text-slate-500">Xuất</span><strong class="mt-1 block text-rose-700">{{ $outgoing ? $quantity(abs($movement['quantity_delta'])) : '—' }}</strong></div></div>
                    @if($movement['source'])<div class="mt-3">@if($movement['source']['kind']==='receipt' && auth('web')->user()?->can('client.pharma.inventory.receipts'))<a href="{{ route('client.pharma.inventory.receipts.show',$movement['source']['id']) }}" class="inline-flex min-h-11 items-center rounded-2xl border border-slate-300 bg-white px-4 text-sm font-black text-slate-700">Phiếu nhập {{ $movement['source']['number'] }} ›</a>@elseif($movement['source']['kind']==='issue' && auth('web')->user()?->can('client.pharma.orders'))<a href="{{ route('client.pharma.orders.show',$movement['source']['id']) }}" class="inline-flex min-h-11 items-center rounded-2xl border border-slate-300 bg-white px-4 text-sm font-black text-slate-700">Phiếu xuất {{ $movement['source']['number'] }} ›</a>@else<span class="text-xs font-bold text-slate-500">{{ $movement['source']['number'] }}</span>@endif</div>@endif
                </article>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 p-7 text-center text-sm text-slate-500">Sản phẩm chưa có giao dịch kho.</div>
            @endforelse
        </div>

        <div class="mt-4 hidden overflow-x-auto rounded-2xl border border-slate-200 xl:block">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Thời điểm</th><th class="px-4 py-3">Chứng từ</th><th class="px-4 py-3">Nghiệp vụ</th><th class="px-4 py-3">Lô / HSD</th><th class="px-4 py-3 text-right">Nhập</th><th class="px-4 py-3 text-right">Xuất</th><th class="px-4 py-3 text-right">Tồn lô sau GD</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($movements as $movement)
                    @php $incoming=$movement['quantity_delta'] > 0; $outgoing=$movement['quantity_delta'] < 0; @endphp
                    <tr><td class="whitespace-nowrap px-4 py-3">{{ $movement['created_at']?->format('d/m/Y H:i') }}</td><td class="px-4 py-3">@if($movement['source'])@if($movement['source']['kind']==='receipt' && auth('web')->user()?->can('client.pharma.inventory.receipts'))<a class="font-bold hover:underline" href="{{ route('client.pharma.inventory.receipts.show',$movement['source']['id']) }}">{{ $movement['source']['number'] }}</a>@elseif($movement['source']['kind']==='issue' && auth('web')->user()?->can('client.pharma.orders'))<a class="font-bold hover:underline" href="{{ route('client.pharma.orders.show',$movement['source']['id']) }}">{{ $movement['source']['number'] }}</a>@else{{ $movement['source']['number'] }}@endif @else—@endif</td><td class="px-4 py-3 font-bold">{{ $movementLabels[$movement['type']] ?? $movement['type'] }}</td><td class="px-4 py-3"><strong>{{ $movement['batch_number'] }}</strong><span class="block text-xs text-slate-500">{{ $movement['expiry_date']?->format('d/m/Y') }}</span></td><td class="px-4 py-3 text-right font-black text-emerald-700">{{ $incoming ? $quantity($movement['quantity_delta']) : '—' }}</td><td class="px-4 py-3 text-right font-black text-rose-700">{{ $outgoing ? $quantity(abs($movement['quantity_delta'])) : '—' }}</td><td class="px-4 py-3 text-right font-black">{{ $quantity($movement['balance_after']) }}</td></tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">Sản phẩm chưa có giao dịch kho.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <a href="{{ route('client.pharma.inventory') }}" class="inline-flex min-h-11 items-center rounded-2xl border border-slate-300 bg-white px-4 text-sm font-black text-slate-700 shadow-sm">← Quay về tồn kho</a>
</div>
@endsection
