@extends('ClientPortal::layouts.application')

@section('title', 'Bảng giá của tôi')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Bảng giá do bạn phụ trách')
@section('app-dashboard-route', route('client.pharma.dashboard'))

@section('content')
<div class="space-y-5">
    @if(session('success'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-800">{{ session('success') }}</div>@endif
    <section class="rounded-[2rem] bg-slate-950 px-5 py-6 text-white shadow-sm sm:px-7">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-300">My Price Lists</p>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-black tracking-tight sm:text-3xl">Bảng giá của tôi</h1>
            <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-bold">{{ number_format($counts['all'] ?? 0, 0, ',', '.') }} bảng giá</span>
        </div>
        <div class="mt-3 flex flex-wrap items-center justify-between gap-3"><p class="max-w-3xl text-sm leading-6 text-slate-300">Chỉ hiển thị các bảng giá bạn là người phụ trách. Tạo, gửi duyệt và phê duyệt được kiểm soát theo quyền nghiệp vụ.</p>@if($canCreate)<a href="{{ route('client.pharma.price-lists.create') }}" class="rounded-2xl bg-white px-4 py-2.5 text-sm font-black text-slate-950">+ Tạo bảng giá</a>@endif</div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <form id="price-list-search-form" method="GET" action="{{ route('client.pharma.price-lists') }}" class="grid gap-3 lg:grid-cols-[minmax(15rem,1fr)_10.5rem_10.5rem_8rem_auto] lg:items-end">
            <label><span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Tìm bảng giá / khách hàng</span><input id="price-list-search-input" type="search" name="q" value="{{ $search }}" autocomplete="off" class="h-[46px] w-full rounded-2xl border border-slate-300 px-4 text-sm" placeholder="Tên, mã bảng giá, khách hàng..."></label>
            <label><span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Từ ngày</span><input type="date" name="from_date" value="{{ $fromDate }}" onchange="this.form.submit()" class="h-[46px] w-full rounded-2xl border border-slate-300 px-3 text-sm"></label>
            <label><span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Đến ngày</span><input type="date" name="to_date" value="{{ $toDate }}" onchange="this.form.submit()" class="h-[46px] w-full rounded-2xl border border-slate-300 px-3 text-sm"></label>
            <label><span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Hiển thị</span><select name="per_page" onchange="this.form.submit()" class="h-[46px] w-full rounded-2xl border border-slate-300 px-3 text-sm">@foreach([25,50,100] as $size)<option value="{{ $size }}" @selected($perPage === $size)>{{ $size }} / trang</option>@endforeach</select></label>
            <a href="{{ route('client.pharma.price-lists', ['from_date' => now()->startOfMonth()->toDateString(), 'to_date' => now()->toDateString(), 'per_page' => 25]) }}" class="flex h-[46px] items-center justify-center rounded-2xl border border-slate-300 px-4 text-sm font-bold text-slate-600 hover:bg-slate-50">Đặt lại</a>
            @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
        </form>
        @php
            $statuses = [
                null => ['label' => 'Tất cả', 'class' => 'slate'],
                'draft' => ['label' => 'Nháp', 'class' => 'amber'],
                'pending_approval' => ['label' => 'Chờ duyệt', 'class' => 'amber'],
                'active' => ['label' => 'Đang hiệu lực', 'class' => 'emerald'],
                'inactive' => ['label' => 'Ngưng', 'class' => 'slate'],
                'archived' => ['label' => 'Lưu trữ', 'class' => 'slate'],
            ];
        @endphp
        <div class="mt-4 flex flex-wrap gap-2">
            @foreach($statuses as $value => $meta)
                @php $normalizedValue = $value === '' ? null : $value; $active = $status === $normalizedValue; $countKey = $normalizedValue ?? 'all'; @endphp
                <a href="{{ route('client.pharma.price-lists', array_filter(['q' => $search, 'per_page' => $perPage, 'status' => $normalizedValue, 'from_date' => $fromDate, 'to_date' => $toDate], fn($v) => $v !== null && $v !== '')) }}"
                   class="rounded-full border px-3.5 py-2 text-xs font-bold {{ $active ? ($meta['class'] === 'emerald' ? 'border-emerald-600 bg-emerald-600 text-white' : ($meta['class'] === 'amber' ? 'border-amber-500 bg-amber-500 text-white' : 'border-slate-700 bg-slate-700 text-white')) : 'border-slate-200 bg-white text-slate-600' }}">
                    @if($active)✓ @endif{{ $meta['label'] }} <span class="ml-1 opacity-70">{{ $counts[$countKey] ?? 0 }}</span>
                </a>
            @endforeach
            @if($search !== '' || $status !== null)<a href="{{ route('client.pharma.price-lists', ['per_page' => $perPage]) }}" class="rounded-full border border-slate-300 px-3.5 py-2 text-xs font-bold text-slate-600">Xóa bộ lọc</a>@endif
        </div>
    </section>

    <div class="grid gap-3 lg:hidden">
        @forelse($priceLists as $priceList)
            @php $customer = $priceList->partner?->name ?? $priceList->officialFacility?->facility_name ?? $priceList->officialFacility?->name ?? 'Bảng giá chung'; @endphp
            <a href="{{ route('client.pharma.price-lists.show', $priceList->id) }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3"><div><p class="font-black text-slate-950">{{ $priceList->name }}</p><p class="mt-1 text-sm text-slate-500">{{ $customer }}</p></div><span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600">{{ $priceList->status }}</span></div>
                <div class="mt-4 grid grid-cols-2 gap-3 text-sm"><div><p class="text-xs font-bold text-slate-400">Sản phẩm</p><p class="mt-1 font-bold">{{ $priceList->items_count }}</p></div><div><p class="text-xs font-bold text-slate-400">Hiệu lực</p><p class="mt-1">{{ $priceList->effective_from?->format('d/m/Y') ?: '—' }} → {{ $priceList->effective_to?->format('d/m/Y') ?: '—' }}</p></div></div>
            </a>
        @empty <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">Bạn chưa có bảng giá nào trong phạm vi quản lý.</div> @endforelse
    </div>

    <section class="hidden overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm lg:block">
        <table class="w-full table-fixed text-left text-sm"><thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500"><tr><th class="w-[26%] px-5 py-3">Bảng giá</th><th class="w-[24%] px-5 py-3">Khách hàng</th><th class="w-[12%] px-5 py-3">Mục đích</th><th class="w-[8%] px-5 py-3 text-center">SP</th><th class="w-[20%] px-5 py-3">Hiệu lực</th><th class="w-[10%] px-5 py-3">Trạng thái</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
        @forelse($priceLists as $priceList)
            @php
                $customer = $priceList->partner?->name ?? $priceList->officialFacility?->facility_name ?? $priceList->officialFacility?->name ?? 'Bảng giá chung';
                $statusLabel = match($priceList->status) { 'draft' => 'Nháp', 'pending_approval' => 'Chờ duyệt', 'active' => 'Đang hiệu lực', 'inactive' => 'Ngưng', 'archived' => 'Lưu trữ', default => $priceList->status };
            @endphp
            <tr class="transition hover:bg-slate-50"><td class="px-5 py-4"><a href="{{ route('client.pharma.price-lists.show', $priceList->id) }}" class="font-black text-slate-950 hover:underline">{{ $priceList->name }}</a><p class="mt-1 text-xs text-slate-400">{{ $priceList->code }}</p>@if($priceList->status === 'draft')<div class="mt-2 flex items-center gap-3 text-xs font-bold"><a href="{{ route('client.pharma.price-lists.edit', $priceList->id) }}" class="text-blue-700 hover:underline">Sửa</a><form method="POST" action="{{ route('client.pharma.price-lists.delete', $priceList->id) }}" onsubmit="return confirm('Xóa bảng giá Nháp này?')">@csrf @method('DELETE')<button class="text-red-600 hover:underline">Xóa</button></form></div>@endif</td><td class="px-5 py-4 text-slate-700">{{ $customer }}</td><td class="px-5 py-4 text-slate-600">{{ $priceList->purpose?->name ?: '—' }}</td><td class="px-5 py-4 text-center font-bold">{{ $priceList->items_count }}</td><td class="px-5 py-4 text-slate-600">{{ $priceList->effective_from?->format('d/m/Y') ?: '—' }} → {{ $priceList->effective_to?->format('d/m/Y') ?: '—' }}</td><td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $priceList->status === 'active' ? 'bg-emerald-100 text-emerald-700' : ($priceList->status === 'draft' ? 'bg-amber-100 text-amber-700' : ($priceList->status === 'pending_approval' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600')) }}">{{ $statusLabel }}</span></td></tr>
        @empty <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">Bạn chưa có bảng giá nào trong phạm vi quản lý.</td></tr> @endforelse
        </tbody></table>
    </section>
    @if($priceLists->hasPages())<div>{{ $priceLists->links() }}</div>@endif
</div>
<script>document.addEventListener('DOMContentLoaded',()=>{const f=document.getElementById('price-list-search-form'),i=document.getElementById('price-list-search-input');if(!f||!i)return;let t;i.addEventListener('input',()=>{clearTimeout(t);t=setTimeout(()=>f.requestSubmit(),350);});});</script>
@endsection
