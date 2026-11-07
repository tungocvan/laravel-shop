@extends('ClientPortal::layouts.application')
@section('title', 'Thuốc trúng thầu quan tâm')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)
@section('content')
<div class="mx-auto max-w-5xl space-y-4">
    <a href="{{ route('client.pharma.award-prices') }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-slate-700">← Quay lại tra cứu</a>
    <section class="rounded-3xl bg-slate-950 p-5 text-white sm:p-7">
        <h1 class="text-xl font-black sm:text-2xl">Thuốc trúng thầu quan tâm</h1>
        <p class="mt-2 text-sm text-slate-300">Đã lưu trên server theo tài khoản đăng nhập · {{ $saved->total() }} bản ghi</p>
    </section>
    @if(session('success'))<div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm">{{ session('success') }}</div>@endif
    <div class="space-y-3">
        @forelse($saved as $favorite)
            @php
                $item = $favorite->snapshot ?? [];
                $sourceId = $favorite->source_id;
            @endphp
            <article class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4">
                <div class="min-w-0 flex-1">
                    <h2 class="font-bold text-slate-950">{{ $favorite->medicine_name ?: 'Thuốc chưa có tên' }}</h2>
                    <p class="mt-1 text-sm text-slate-600">Nhóm thuốc: {{ $favorite->medicine_group ?: '—' }} · {{ $item['tenHoatChat'] ?? '—' }} · {{ $item['nongDo'] ?? '—' }}</p>
                    <p class="mt-1 text-sm text-slate-600">Giá trúng thầu: {{ is_numeric($item['donGia'] ?? null) ? number_format((float) $item['donGia'], 0, ',', '.').' đ' : '—' }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('client.pharma.award-prices.wishlist.show', ['sourceId' => $sourceId]) }}"
                        class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-xl border border-slate-300 text-indigo-700 focus-visible:ring-2 focus-visible:ring-indigo-500"
                        aria-label="Xem chi tiết {{ $favorite->medicine_name ?: 'thuốc' }}" title="Xem chi tiết">
                        <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-7 10-7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                    </a>
                    <form method="POST" action="{{ route('client.pharma.award-prices.wishlist.toggle') }}">
                        @csrf
                        <input type="hidden" name="keyword" value="{{ $favorite->search_keyword }}">
                        <input type="hidden" name="page" value="{{ $favorite->source_page }}">
                        <input type="hidden" name="source_id" value="{{ $sourceId }}">
                        <button type="submit" class="min-h-11 rounded-xl border border-rose-300 px-4 text-sm font-semibold text-rose-700">Bỏ quan tâm</button>
                    </form>
                </div>
            </article>
        @empty
            <p class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-600">Chưa có thuốc nào trong danh sách quan tâm.</p>
        @endforelse
    </div>
    <div>{{ $saved->links() }}</div>
</div>
@endsection
