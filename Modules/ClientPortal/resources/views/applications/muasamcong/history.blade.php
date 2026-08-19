@extends('ClientPortal::layouts.application')
@section('title', 'Lịch sử hoạt động')
@section('app-name', 'Mua sắm công')
@section('app-dashboard-route', route('client.muasamcong.dashboard'))
@section('content')
<section class="rounded-3xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Hoạt động cá nhân</p>
            <h1 class="mt-1 text-xl font-bold sm:text-2xl">Lịch sử tra cứu & đồng bộ</h1>
            <p class="mt-1 text-sm text-slate-500">Theo dõi các từ khóa đã tra cứu và các yêu cầu đồng bộ của tài khoản này.</p>
        </div>
        <a href="{{ route('client.muasamcong.drug-pricing') }}" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white">Tra cứu mới</a>
    </div>

    <form method="GET" class="mt-5 grid gap-2 sm:grid-cols-[1fr_auto_auto_auto]">
        <input name="q" value="{{ $q }}" placeholder="Tìm theo từ khóa..." class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
        <select name="type" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
            <option value="all" @selected($type==='all')>Tất cả</option>
            <option value="search" @selected($type==='search')>Tra cứu</option>
            <option value="sync" @selected($type==='sync')>Đồng bộ</option>
        </select>
        <select name="status" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
            <option value="">Mọi trạng thái</option>
            <option value="queued" @selected($status==='queued')>Đang chờ</option>
            <option value="processing" @selected($status==='processing')>Đang xử lý</option>
            <option value="completed" @selected($status==='completed')>Hoàn thành</option>
            <option value="failed" @selected($status==='failed')>Lỗi</option>
        </select>
        <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white">Lọc</button>
    </form>
</section>

<section class="mt-4 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
    @forelse($activities as $activity)
        @php
            $isSearch = $activity['type'] === 'search';
            $statusMeta = match($activity['status']) {
                'queued' => ['Đang chờ','bg-slate-100 text-slate-700'],
                'processing' => ['Đang xử lý','bg-amber-50 text-amber-700'],
                'completed' => ['Hoàn thành','bg-emerald-50 text-emerald-700'],
                'failed' => ['Lỗi','bg-red-50 text-red-700'],
                default => [null,''],
            };
        @endphp
        <article class="border-b border-slate-100 p-4 last:border-b-0 sm:p-5">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $isSearch ? 'bg-sky-50 text-sky-700' : 'bg-violet-50 text-violet-700' }}">{{ $isSearch ? 'Tra cứu' : 'Đồng bộ' }}</span>
                        @if(!$isSearch && $statusMeta[0])<span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $statusMeta[1] }}">{{ $statusMeta[0] }}</span>@endif
                    </div>
                    <h2 class="mt-2 truncate font-bold">{{ $activity['keyword'] ?: 'Không có từ khóa' }}</h2>
                    <p class="mt-1 text-xs text-slate-500">{{ optional($activity['occurred_at'])->format('d/m/Y H:i:s') }}</p>
                </div>
                @if($activity['keyword'])
                    <a href="{{ route('client.muasamcong.drug-pricing', ['keyword'=>$activity['keyword']]) }}" class="shrink-0 rounded-xl border border-slate-300 px-3 py-2 text-xs font-bold">Tra cứu lại</a>
                @endif
            </div>

            @if($isSearch)
                <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-600">
                    <span class="rounded-lg bg-slate-50 px-2.5 py-1.5">Đã tải: <strong>{{ number_format($activity['loaded_total']) }}</strong></span>
                    <span class="rounded-lg bg-slate-50 px-2.5 py-1.5">Nguồn báo: <strong>{{ number_format($activity['source_total']) }}</strong></span>
                </div>
            @else
                <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-600">
                    <span class="rounded-lg bg-slate-50 px-2.5 py-1.5">Chọn: <strong>{{ $activity['selected_count'] }}</strong></span>
                    <span class="rounded-lg bg-slate-50 px-2.5 py-1.5">Mới: <strong>{{ $activity['inserted_count'] }}</strong></span>
                    <span class="rounded-lg bg-slate-50 px-2.5 py-1.5">Đã có: <strong>{{ $activity['duplicate_count'] }}</strong></span>
                    <span class="rounded-lg bg-slate-50 px-2.5 py-1.5">Thiếu: <strong>{{ $activity['missing_count'] }}</strong></span>
                </div>
                @if($activity['error_message'])<p class="mt-3 rounded-xl bg-red-50 px-3 py-2 text-sm text-red-700">{{ $activity['error_message'] }}</p>@endif
            @endif
        </article>
    @empty
        <div class="p-10 text-center text-sm text-slate-500">Chưa có hoạt động phù hợp với bộ lọc.</div>
    @endforelse
</section>
@endsection
