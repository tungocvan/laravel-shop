@extends('ClientPortal::layouts.application')

@section('title', $featurePresentation['page_title'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', $featurePresentation['page_title'])
@section('app-dashboard-route', route('client.pharma.dashboard'))

@section('content')
@php
    $fmtQty = fn ($value) => rtrim(rtrim(number_format((float) $value, 4, ',', '.'), '0'), ',');
    $remainingContractMonths = function ($result): ?int {
        $durationMonths = null;
        if ((int) $result->contract_duration_months > 0) {
            $durationMonths = (int) $result->contract_duration_months;
        } elseif ((int) $result->contract_period > 0) {
            $unit = mb_strtolower(trim((string) $result->contract_period_unit));
            $period = (int) $result->contract_period;
            if ($unit === '' || in_array($unit, ['m', 'mo'], true) || str_contains($unit, 'tháng') || str_contains($unit, 'month')) {
                $durationMonths = $period;
            } elseif (in_array($unit, ['y', 'yr'], true) || str_contains($unit, 'năm') || str_contains($unit, 'year')) {
                $durationMonths = $period * 12;
            } elseif (in_array($unit, ['d', 'day', 'days'], true) || str_contains($unit, 'ngày')) {
                $durationMonths = max(1, (int) round($period / 30.4375));
            }
        }
        if (! $durationMonths || ! $result->decision_date) {
            return null;
        }
        $endDate = \Carbon\Carbon::parse($result->decision_date)->addMonthsNoOverflow($durationMonths)->endOfDay();
        return now()->greaterThanOrEqualTo($endDate) ? 0 : max(1, (int) ceil(now()->diffInDays($endDate) / 30.4375));
    };
@endphp
<div class="min-w-0 space-y-5 overflow-x-hidden">
    <section class="rounded-[2rem] bg-slate-950 px-5 py-6 text-white shadow-sm sm:px-7">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-300">{{ $featurePresentation['eyebrow'] }}</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight">{{ $featurePresentation['page_title'] }}</h1>
        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-300">{{ $featurePresentation['page_description'] }}</p>
    </section>

    <form method="GET" class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm" id="bid-award-search-form">
        <div class="relative">
            <input name="q" value="{{ $search }}" placeholder="Tìm TBMT, chủ đầu tư, quyết định, sản phẩm..." autocomplete="off"
                class="h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 pr-12 text-sm outline-none focus:border-slate-400">
            @if($search !== '')
                <a href="{{ route('client.pharma.bid-awards') }}" class="absolute right-2 top-2 flex h-8 w-8 items-center justify-center rounded-full text-slate-500" aria-label="Xóa tìm kiếm">×</a>
            @endif
        </div>
    </form>

    <div class="flex items-center justify-between gap-3 px-1">
        <div>
            <h2 class="text-base font-black text-slate-950">Danh sách kết quả trúng thầu</h2>
            <p class="mt-0.5 text-xs text-slate-500">{{ number_format($results->total()) }} kết quả trong phạm vi bạn phụ trách</p>
        </div>
        @if($search !== '')<span class="shrink-0 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600">Đang lọc</span>@endif
    </div>

    <section id="bid-award-results" class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        @forelse($results as $result)
            <a data-bid-award-item href="{{ route('client.pharma.bid-awards.show', $result->scope_key) }}"
                class="group min-w-0 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition active:scale-[0.985] motion-reduce:transform-none">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ $result->bidding_notice_code ?: ($result->decision_number ?: 'Kết quả trúng thầu') }}</p>
                        <h2 class="mt-1 truncate text-lg font-black text-slate-950">{{ $result->investor_name ?: 'Chưa có tên chủ đầu tư' }}</h2>
                        <p class="mt-2 text-sm text-slate-500">Quyết định: {{ $result->decision_number ?: '—' }} @if($result->decision_date) · {{ \Carbon\Carbon::parse($result->decision_date)->format('d/m/Y') }} @endif</p>
                    </div>
                    <span class="shrink-0 text-slate-400 transition group-hover:translate-x-0.5">→</span>
                </div>
                <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-2xl bg-slate-50 p-3"><strong class="block text-slate-950">{{ number_format($result->products_count) }}</strong><span class="text-xs text-slate-500">Sản phẩm</span></div>
                    <div class="rounded-2xl bg-slate-50 p-3"><strong class="block text-slate-950">{{ number_format($result->hospitals_count) }}</strong><span class="text-xs text-slate-500">Bệnh viện</span></div>
                    <div class="rounded-2xl bg-slate-50 p-3"><strong class="block text-slate-950">{{ $fmtQty($result->allocated_quantity) }}</strong><span class="text-xs text-slate-500">SL phân bổ</span></div>
                </div>
                <div class="mt-3 flex items-center justify-between gap-3 border-t border-slate-100 pt-3 text-xs">
                    <span class="min-w-0 text-slate-500">Giá trị phân bổ <strong class="text-slate-800">{{ number_format((float) $result->allocated_value, 0, ',', '.') }} VNĐ</strong></span>
                    @php($remainingMonths = $remainingContractMonths($result))
                    @if($remainingMonths === 0)
                        <span class="shrink-0 rounded-full bg-rose-50 px-2.5 py-1 font-bold text-rose-700">Hết hiệu lực HĐ</span>
                    @elseif($remainingMonths !== null)
                        <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 font-bold text-slate-600">Còn {{ str_pad((string) $remainingMonths, 2, '0', STR_PAD_LEFT) }} tháng</span>
                    @elseif($result->contract_period_text)
                        <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 font-bold text-slate-600">{{ $result->contract_period_text }}</span>
                    @endif
                </div>
            </a>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500 md:col-span-2 xl:col-span-3">Chưa có kết quả trúng thầu có phân bổ đang hiệu lực trong phạm vi bạn phụ trách.</div>
        @endforelse
    </section>

    @if($results->hasMorePages())
        <div class="text-center">
            <a id="bid-award-load-more" href="{{ $results->nextPageUrl() }}" class="inline-flex min-h-11 items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 text-sm font-bold text-slate-700 shadow-sm">Xem thêm kết quả</a>
        </div>
    @endif
</div>
<script>
(() => {
    const form=document.getElementById('bid-award-search-form'), input=form?.querySelector('input[name="q"]');
    let timer; input?.addEventListener('input',()=>{ clearTimeout(timer); timer=setTimeout(()=>form.requestSubmit(),350); });
    const more=document.getElementById('bid-award-load-more');
    more?.addEventListener('click',async(event)=>{
        event.preventDefault(); more.classList.add('pointer-events-none','opacity-60');
        try {
            const response=await fetch(more.href,{headers:{'X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'});
            const doc=new DOMParser().parseFromString(await response.text(),'text/html');
            doc.querySelectorAll('[data-bid-award-item]').forEach(item=>document.getElementById('bid-award-results').append(item));
            const next=doc.getElementById('bid-award-load-more');
            if(next) more.href=next.href; else more.remove();
        } catch(error) { window.location.href=more.href; }
        finally { more?.classList.remove('pointer-events-none','opacity-60'); }
    });
})();
</script>
@endsection
