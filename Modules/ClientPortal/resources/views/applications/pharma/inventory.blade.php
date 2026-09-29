@extends('ClientPortal::layouts.application')

@section('title', $featurePresentation['page_title'] ?? 'Tồn kho Pharma')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Workspace Pharma dành cho User')
@section('app-dashboard-route', route('client.pharma.dashboard'))

@section('content')
@php
    $money = fn ($value) => number_format((float) $value, 0, ',', '.').' đ';
    $expiryLabels = ['expired'=>'Đã hết hạn','lt1'=>'< 1 tháng','lt3'=>'< 3 tháng','lt6'=>'< 6 tháng','safe'=>'≥ 6 tháng'];
@endphp
<div class="min-w-0 space-y-4 overflow-x-hidden">
    <section class="rounded-[2rem] bg-slate-950 px-5 py-6 text-white shadow-sm sm:px-7">
        <a href="{{ route('client.pharma.dashboard') }}" class="inline-flex text-sm font-bold text-slate-300 hover:text-white">← Quay về dashboard</a>
        <p class="mt-5 text-xs font-black uppercase tracking-[0.16em] text-slate-400">{{ $featurePresentation['eyebrow'] ?? 'Inventory' }}</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight">{{ $featurePresentation['page_title'] ?? 'Tồn kho Pharma' }}</h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-300">{{ $featurePresentation['page_description'] ?? 'Theo dõi số lượng tồn, giá trị, lô và hạn dùng.' }}</p>
    </section>

    <section class="grid grid-cols-2 gap-3 xl:grid-cols-4">
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-bold text-slate-500">Lô đang còn hàng</p><p class="mt-2 text-2xl font-black text-slate-950">{{ number_format($summary['balance_count']) }}</p></div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-bold text-slate-500">Giá trị tồn</p><p class="mt-2 text-lg font-black text-slate-950 sm:text-2xl">{{ $money($summary['inventory_value']) }}</p></div>
        <a href="{{ route('client.pharma.inventory', ['expiry'=>'lt6']) }}" class="rounded-3xl border border-amber-200 bg-amber-50 p-4 shadow-sm active:scale-[0.985]"><p class="text-xs font-bold text-amber-700">Sắp hết hạn ≤ 6 tháng</p><p class="mt-2 text-2xl font-black text-amber-950">{{ number_format($summary['near_expiry_count']) }}</p></a>
        <a href="{{ route('client.pharma.inventory', ['cost_status'=>'unpriced']) }}" class="rounded-3xl border border-rose-200 bg-rose-50 p-4 shadow-sm active:scale-[0.985]"><p class="text-xs font-bold text-rose-700">Chưa có giá vốn</p><p class="mt-2 text-2xl font-black text-rose-950">{{ number_format($summary['unpriced_count']) }}</p></a>
    </section>

    <form method="GET" action="{{ route('client.pharma.inventory') }}" class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 lg:grid-cols-12">
            <div class="lg:col-span-5">
                <label class="text-xs font-bold text-slate-500">Tìm thuốc</label>
                <div class="mt-1 flex gap-2"><input name="q" value="{{ $filters['q'] }}" placeholder="Tên thuốc / mã thuốc" class="min-w-0 flex-1 rounded-2xl border-slate-300 text-sm"><button class="rounded-2xl bg-slate-950 px-4 text-sm font-bold text-white">Tìm</button></div>
            </div>
            <div class="lg:col-span-7">
                <details class="group" {{ ($filters['expiry'] || $filters['cost_status'] || $filters['sort']) ? 'open' : '' }}>
                    <summary class="cursor-pointer py-2 text-sm font-bold text-slate-700">Bộ lọc nâng cao</summary>
                    <div class="grid gap-2 pt-2 sm:grid-cols-3">
                        <select name="expiry" onchange="this.form.submit()" class="rounded-2xl border-slate-300 text-sm"><option value="">Tất cả hạn dùng</option>@foreach($expiryLabels as $value=>$label)<option value="{{ $value }}" @selected($filters['expiry']===$value)>{{ $label }}</option>@endforeach</select>
                        <select name="cost_status" onchange="this.form.submit()" class="rounded-2xl border-slate-300 text-sm"><option value="">Tất cả giá vốn</option><option value="priced" @selected($filters['cost_status']==='priced')>Có giá vốn</option><option value="unpriced" @selected($filters['cost_status']==='unpriced')>Chưa có giá vốn</option></select>
                        <select name="sort" onchange="this.form.submit()" class="rounded-2xl border-slate-300 text-sm"><option value="">Hạn dùng gần nhất</option><option value="value_desc" @selected($filters['sort']==='value_desc')>Giá trị tồn lớn nhất</option><option value="value_asc" @selected($filters['sort']==='value_asc')>Giá trị tồn nhỏ nhất</option></select>
                    </div>
                </details>
            </div>
        </div>
        @if($filters['q'] || $filters['expiry'] || $filters['cost_status'] || $filters['sort'])
            <a href="{{ route('client.pharma.inventory') }}" class="mt-3 inline-flex text-sm font-bold text-slate-600">Xóa bộ lọc</a>
        @endif
    </form>

    <section class="space-y-3 xl:hidden">
        @forelse($balances as $row)
            @php $expired=$row->expiry_date->isPast(); $near=!$expired && $row->expiry_date->lte(now()->addMonths(6)); @endphp
            <article class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="text-xs font-bold text-slate-400">{{ $row->medicine->medicine_code }}</p><h2 class="mt-1 font-black text-slate-950">{{ $row->medicine->name }}</h2></div><span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold {{ $expired ? 'bg-rose-100 text-rose-700' : ($near ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">{{ $expired ? 'Hết hạn' : ($near ? 'Sắp hết hạn' : 'Còn hạn') }}</span></div>
                <div class="mt-4 grid grid-cols-2 gap-3 text-sm"><div><p class="text-xs text-slate-400">Số lô</p><p class="font-bold text-slate-800">{{ $row->batch_number }}</p></div><div><p class="text-xs text-slate-400">Hạn dùng</p><p class="font-bold text-slate-800">{{ $row->expiry_date->format('d/m/Y') }}</p></div><div><p class="text-xs text-slate-400">Tồn hiện tại</p><p class="text-lg font-black text-slate-950">{{ rtrim(rtrim(number_format((float)$row->quantity_on_hand,3,'.',''),'0'),'.') }}</p></div><div><p class="text-xs text-slate-400">Giá trị tồn</p><p class="font-black text-slate-950">{{ $row->inventory_value !== null ? $money($row->inventory_value) : 'Chưa có giá' }}</p></div></div>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">Không có tồn kho phù hợp bộ lọc.</div>
        @endforelse
    </section>

    <section class="hidden overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm xl:block">
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Mã thuốc</th><th class="px-4 py-3">Tên thuốc</th><th class="px-4 py-3">Số lô</th><th class="px-4 py-3">Hạn dùng</th><th class="px-4 py-3 text-right">Tồn hiện tại</th><th class="px-4 py-3 text-right">Giá vốn TB</th><th class="px-4 py-3 text-right">Giá trị tồn</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($balances as $row)<tr><td class="px-4 py-3 font-bold text-slate-700">{{ $row->medicine->medicine_code }}</td><td class="px-4 py-3 font-bold text-slate-950">{{ $row->medicine->name }}</td><td class="px-4 py-3">{{ $row->batch_number }}</td><td class="px-4 py-3">{{ $row->expiry_date->format('d/m/Y') }}</td><td class="px-4 py-3 text-right font-black">{{ rtrim(rtrim(number_format((float)$row->quantity_on_hand,3,'.',''),'0'),'.') }}</td><td class="px-4 py-3 text-right">{{ $row->average_cost_price !== null ? $money($row->average_cost_price) : '—' }}</td><td class="px-4 py-3 text-right font-black">{{ $row->inventory_value !== null ? $money($row->inventory_value) : '—' }}</td></tr>@empty<tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">Không có tồn kho phù hợp bộ lọc.</td></tr>@endforelse</tbody></table></div>
    </section>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('client.pharma.inventory') }}" class="flex items-center gap-2">@foreach(request()->except(['per_page','page']) as $key=>$value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach<label class="text-sm font-bold text-slate-500">Hiển thị</label><select name="per_page" onchange="this.form.submit()" class="rounded-xl border-slate-300 text-sm">@foreach([25,50,100] as $size)<option value="{{ $size }}" @selected($filters['per_page']===$size)>{{ $size }} / trang</option>@endforeach</select></form>
        <div>{{ $balances->links() }}</div>
    </div>
</div>
@endsection
