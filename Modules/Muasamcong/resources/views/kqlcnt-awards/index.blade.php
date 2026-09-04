@extends('Admin::layouts.master')
@section('title', 'Dữ liệu KQLCNT đã đồng bộ')
@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div><p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Mua sắm công</p><h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Dữ liệu chi tiết KQLCNT</h1><p class="mt-1 text-sm text-gray-500">Quản trị kho dữ liệu trúng thầu canonical đã đồng bộ từ các Mã TBMT.</p></div>
        <div class="flex flex-wrap gap-2">@include('Muasamcong::partials.dashboard-return-link')<button form="award-export-form" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">Xuất Excel</button></div>
    </div>

    <form method="GET" class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <input name="q" value="{{ $filters['q'] }}" placeholder="TBMT, nhà thầu, thuốc, hoạt chất..." class="rounded-xl border-gray-300 text-sm">
            <input name="source" value="{{ $filters['source'] }}" placeholder="Nguồn dữ liệu" class="rounded-xl border-gray-300 text-sm">
            <select name="active" class="rounded-xl border-gray-300 text-sm"><option value="">Tất cả trạng thái</option><option value="1" @selected($filters['active']==='1')>Đang hoạt động</option><option value="0" @selected($filters['active']==='0')>Ngưng</option></select>
            <div class="flex gap-2"><button class="rounded-xl bg-gray-900 px-4 py-2 text-sm font-semibold text-white">Lọc</button><a href="{{ route('muasamcong.kqlcnt-awards.index') }}" class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Xóa lọc</a></div>
            <label class="text-xs text-gray-600">KQLCNT từ<input type="date" name="published_from" value="{{ $filters['published_from'] }}" class="mt-1 block w-full rounded-xl border-gray-300 text-sm"></label>
            <label class="text-xs text-gray-600">KQLCNT đến<input type="date" name="published_to" value="{{ $filters['published_to'] }}" class="mt-1 block w-full rounded-xl border-gray-300 text-sm"></label>
            <label class="text-xs text-gray-600">Đồng bộ từ<input type="date" name="synced_from" value="{{ $filters['synced_from'] }}" class="mt-1 block w-full rounded-xl border-gray-300 text-sm"></label>
            <label class="text-xs text-gray-600">Đồng bộ đến<input type="date" name="synced_to" value="{{ $filters['synced_to'] }}" class="mt-1 block w-full rounded-xl border-gray-300 text-sm"></label>
        </div>
    </form>

    <form id="award-export-form" method="POST" action="{{ route('muasamcong.kqlcnt-awards.export') }}">@csrf
        @foreach($filters as $key => $value) @if($value !== '')<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif @endforeach
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 text-sm"><thead class="bg-gray-50"><tr><th class="px-3 py-3"><input type="checkbox" onclick="document.querySelectorAll('.award-row').forEach(i=>i.checked=this.checked)"></th><th class="px-3 py-3 text-left">Mã TBMT</th><th class="px-3 py-3 text-left">Nhà thầu</th><th class="px-3 py-3 text-left">Thuốc / Hoạt chất</th><th class="px-3 py-3 text-right">SL</th><th class="px-3 py-3 text-right">Giá trúng</th><th class="px-3 py-3 text-right">Thành tiền</th><th class="px-3 py-3 text-left">Nguồn</th><th class="px-3 py-3 text-left">Đồng bộ</th><th class="px-3 py-3"></th></tr></thead><tbody class="divide-y divide-gray-100">
        @forelse($items as $item)<tr class="align-top"><td class="px-3 py-3"><input class="award-row" type="checkbox" name="selected_ids[]" value="{{ $item->id }}"></td><td class="px-3 py-3 font-semibold text-gray-900">{{ $item->notify_no }}<div class="mt-1 text-xs font-normal text-gray-500">{{ $item->lot_no ?: 'Chưa có mã lô' }}</div></td><td class="px-3 py-3">{{ $item->contractor_name }}<div class="text-xs text-gray-500">{{ $item->contractor_code }}</div></td><td class="px-3 py-3"><span class="font-medium">{{ $item->medicine_name ?: '—' }}</span><div class="text-xs text-gray-500">{{ $item->active_ingredient ?: '—' }} {{ $item->concentration }}</div></td><td class="px-3 py-3 text-right tabular-nums">{{ is_null($item->quantity) ? '—' : number_format((float)$item->quantity, 0, ',', '.') }}</td><td class="px-3 py-3 text-right tabular-nums">{{ is_null($item->winning_price) ? '—' : number_format((float)$item->winning_price, 0, ',', '.') }}</td><td class="px-3 py-3 text-right tabular-nums">{{ is_null($item->amount) ? '—' : number_format((float)$item->amount, 0, ',', '.') }}</td><td class="px-3 py-3 text-xs">{{ $item->source ?: $item->sync_source ?: '—' }}</td><td class="px-3 py-3 text-xs">{{ optional($item->synced_from_catalog_at)->format('d/m/Y H:i') ?: '—' }}<div class="mt-1 {{ $item->is_active ? 'text-emerald-700' : 'text-gray-500' }}">{{ $item->is_active ? 'Đang hoạt động' : 'Ngưng' }}</div></td><td class="px-3 py-3 text-right"><a href="{{ route('muasamcong.kqlcnt-awards.show', $item) }}" class="font-semibold text-blue-600">Chi tiết</a></td></tr>@empty<tr><td colspan="10" class="px-4 py-10 text-center text-gray-500">Chưa có dữ liệu KQLCNT canonical phù hợp.</td></tr>@endforelse
        </tbody></table></div></div>
    </form>
    <div>{{ $items->links() }}</div>
</div>
@endsection
