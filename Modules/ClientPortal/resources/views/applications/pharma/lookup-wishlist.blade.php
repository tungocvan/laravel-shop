@extends('ClientPortal::layouts.application')
@section('title', 'Danh sách quan tâm Pharma')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)
@section('content')
<div class="mx-auto max-w-6xl space-y-4">
    <section class="rounded-3xl bg-slate-950 p-5 text-white sm:p-7">
        <a href="{{ route('client.pharma.lookups') }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-slate-200">← Quay về Tra cứu</a>
        <h1 class="mt-3 text-2xl font-black">♡ Danh sách quan tâm Pharma</h1>
        <p class="mt-2 text-sm text-slate-300">Tổng hợp thuốc đã lưu theo tài khoản, gồm cả thuốc trúng thầu hiện có. Chưa tự động đồng bộ vào Danh mục thuốc.</p>
    </section>
    @if(session('success'))<div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    <form method="GET" action="{{ route('client.pharma.lookups.wishlist') }}" class="flex flex-wrap items-end gap-3 rounded-2xl border bg-white p-4">
        <label class="flex-1 text-sm font-semibold text-slate-700">Lọc theo nguồn
            <select name="source" aria-label="Lọc theo nguồn" onchange="this.form.requestSubmit()" class="mt-2 min-h-11 w-full rounded-xl border border-slate-300 px-3">
                <option value="">Tất cả nguồn</option>
                @foreach($sources as $key => $label)
                    <option value="{{ $key }}" @selected($source === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
    </form>
    <p class="text-sm text-slate-600">{{ number_format($saved->total()) }} bản ghi quan tâm</p>
    <div class="space-y-3">
        @forelse($saved as $favorite)
            @php
                $type = $favorite->source_type;
                $snapshot = json_decode($favorite->snapshot ?? '{}', true) ?: [];
                $isAward = $type === 'public_award';
            @endphp
            <article class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold uppercase tracking-wide text-indigo-700">{{ $sources[$type] ?? $type }}</p>
                    <h2 class="mt-1 break-words font-bold text-slate-950">{{ $favorite->medicine_name ?: 'Thuốc chưa có tên' }}</h2>
                    <p class="mt-1 text-sm text-slate-600">Số đăng ký: {{ $favorite->registration_number ?: 'Chưa công bố' }}</p>
                    @unless($isAward)
                        <a href="{{ route('client.pharma.lookups.wishlist.import.preview', ['sourceType' => $type, 'sourceId' => $favorite->source_id]) }}" class="inline-flex min-h-11 items-center rounded-xl bg-indigo-700 px-4 text-sm font-bold text-white">Đồng bộ</a>
                    @endunless
                    @if($isAward)
                        <p class="mt-1 text-sm text-slate-600">Giá trúng thầu: {{ is_numeric($snapshot['donGia'] ?? null) ? number_format((float) $snapshot['donGia'], 0, ',', '.').' đ' : '—' }}</p>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ $isAward ? route('client.pharma.award-prices.wishlist.show', ['sourceId' => $favorite->source_id]) : route('client.pharma.lookups.wishlist.show', ['sourceType' => $type, 'sourceId' => $favorite->source_id]) }}" aria-label="Xem chi tiết {{ $favorite->medicine_name }}" class="inline-flex min-h-11 items-center rounded-xl border border-indigo-300 px-4 text-sm font-bold text-indigo-700">Xem chi tiết</a>
                    @if($isAward)
                        <a href="{{ route('client.pharma.award-prices.wishlist') }}" class="inline-flex min-h-11 items-center rounded-xl border px-4 text-sm font-semibold text-slate-700">Quản lý trúng thầu</a>
                    @else
                        <form method="POST" action="{{ route('client.pharma.lookups.wishlist.remove', ['sourceType' => $type, 'sourceId' => $favorite->source_id]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="min-h-11 rounded-xl border border-rose-300 px-4 text-sm font-bold text-rose-700">Bỏ quan tâm</button>
                        </form>
                    @endif
                </div>
            </article>
        @empty
            <p class="rounded-2xl bg-white p-5 text-sm text-slate-600">Chưa có thuốc quan tâm trong bộ lọc này.</p>
        @endforelse
    </div>
    {{ $saved->links() }}
</div>
@endsection
