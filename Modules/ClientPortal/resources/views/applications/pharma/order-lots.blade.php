@extends('ClientPortal::layouts.application')

@section('title', 'Chọn lô thực xuất')
@section('app-name', $application['name'])
@section('app-subtitle', 'Workspace Pharma dành cho User')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)

@section('content')
<div class="mx-auto max-w-4xl space-y-4 px-3 py-4 sm:px-6">
    <a href="{{ route('client.pharma.orders.show', $issue) }}" class="inline-flex min-h-11 items-center text-sm font-bold text-indigo-700">← Trở về đơn {{ $issue->number }}</a>
    <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
        <h1 class="text-xl font-black text-slate-950">Chọn lô và hạn dùng</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">Phân bổ đúng số lượng đã duyệt cho từng sản phẩm. Lô hết hạn không được sử dụng. Chỉ ghi sổ sau khi kiểm tra toàn bộ phân bổ.</p>
    </div>
    @if($errors->any())
        <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm font-semibold text-rose-800">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif
    <form method="POST" action="{{ route('client.pharma.orders.lots.post', $issue) }}" class="space-y-4" onsubmit="return confirm('Xác nhận ghi sổ theo các lô và số lượng đã chọn?')">
        @csrf
        @forelse($postableItems as $item)
            @php($lots = $balances->get($item->medicine_id, collect()))
            <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 p-4">
                    <h2 class="font-black text-slate-950">{{ $item->medicine?->name ?: 'Sản phẩm #'.$item->medicine_id }}</h2>
                    <p class="mt-1 text-sm text-slate-600">Đã duyệt: <strong>{{ number_format((float) $item->quantity, 3, ',', '.') }}</strong> · {{ $lots->count() }} lô khả dụng</p>
                </div>
                <div class="space-y-3 p-4">
                    @forelse($lots as $lot)
                        <div class="grid grid-cols-1 gap-2 rounded-xl border border-slate-200 p-3 sm:grid-cols-[1fr_140px] sm:items-center">
                            <div class="text-sm">
                                <p class="font-bold text-slate-900">{{ $lot->batch_number }} @if($loop->first)<span class="ml-2 text-xs font-black text-emerald-700">FEFO</span>@endif</p>
                                <p class="text-slate-600">HSD {{ $lot->expiry_date?->format('d/m/Y') }} · Tồn {{ number_format((float) $lot->quantity_on_hand, 3, ',', '.') }}</p>
                            </div>
                            <label class="text-xs font-bold text-slate-700">Số lượng thực xuất
                                <input type="hidden" name="batches[{{ $item->id }}][{{ $lot->id }}][balance_id]" value="{{ $lot->id }}">
                                <input type="number" inputmode="decimal" min="0" max="{{ $lot->quantity_on_hand }}" step="0.001" name="batches[{{ $item->id }}][{{ $lot->id }}][quantity]" value="{{ old('batches.'.$item->id.'.'.$lot->id.'.quantity', 0) }}" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 text-base focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                            </label>
                        </div>
                    @empty
                        <p class="text-sm font-semibold text-amber-800">Không có lô còn hạn và còn tồn.</p>
                    @endforelse
                </div>
            </section>
        @empty
            <p class="rounded-xl bg-amber-50 p-4 text-sm text-amber-900">Không có sản phẩm thực xuất. Các sản phẩm đang chờ cung ứng không được trừ kho.</p>
        @endforelse
        <button type="submit" @disabled($postableItems->isEmpty()) class="min-h-12 w-full rounded-2xl bg-emerald-700 px-4 font-black text-white disabled:cursor-not-allowed disabled:bg-slate-300">Xác nhận lô và ghi sổ</button>
    </form>
</div>
@endsection
