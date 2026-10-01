@extends('ClientPortal::layouts.application')

@section('title', $featurePresentation['page_title'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', $featurePresentation['page_title'])
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)

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
        if (! $durationMonths || ! ($result->decision_date ?: $result->published_at)) {
            return null;
        }
        $endDate = \Carbon\Carbon::parse($result->decision_date ?: $result->published_at)->addMonthsNoOverflow($durationMonths)->endOfDay();
        return now()->greaterThanOrEqualTo($endDate) ? 0 : max(1, (int) ceil(now()->diffInDays($endDate) / 30.4375));
    };
@endphp
<div class="min-w-0 space-y-5 overflow-x-hidden">
    <header class="flex min-h-16 items-center gap-3 border-b border-slate-200 bg-white px-1 pb-4">
        <a href="{{ route('client.pharma.dashboard') }}" aria-label="Quay lại Không gian làm việc Pharma" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-lg font-black text-slate-700 shadow-sm">←</a>
        <div class="min-w-0">
            <h1 class="truncate text-lg font-black text-slate-950">{{ $featurePresentation['page_title'] }}</h1>
            <p class="truncate text-xs text-slate-500">{{ $applicationPresentation['name'] ?? $application['name'] }}</p>
        </div>
    </header>
    <section class="rounded-[2rem] bg-slate-950 px-5 py-6 text-white shadow-sm sm:px-7">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-300">{{ $featurePresentation['eyebrow'] }}</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight">{{ $featurePresentation['page_title'] }}</h1>
        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-300">{{ $featurePresentation['page_description'] }}</p>
    </section>

    @php($activeFilterCount = collect($filters)->filter(fn ($value) => $value !== '')->count())
    <form method="GET" class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm" id="bid-award-search-form">
        <div class="relative">
            <input name="q" value="{{ $search }}" placeholder="Tìm TBMT, chủ đầu tư, quyết định, sản phẩm..." autocomplete="off"
                class="h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 pr-12 text-sm outline-none focus:border-slate-400">
            @if($search !== '')
                <a href="{{ route('client.pharma.bid-awards', array_filter($filters)) }}" class="absolute right-2 top-2 flex h-8 w-8 items-center justify-center rounded-full text-slate-500" aria-label="Xóa tìm kiếm">×</a>
            @endif
        </div>
        <details class="mt-3 group" @if($activeFilterCount > 0) open @endif>
            <summary class="flex min-h-11 cursor-pointer list-none items-center justify-between rounded-2xl bg-slate-50 px-4 text-sm font-black text-slate-700">
                <span>Bộ lọc nâng cao @if($activeFilterCount > 0)<span class="ml-1 rounded-full bg-slate-950 px-2 py-0.5 text-xs text-white">{{ $activeFilterCount }}</span>@endif</span>
                <span class="text-xs text-slate-500 group-open:hidden">Hiển thị</span><span class="hidden text-xs text-slate-500 group-open:inline">Thu gọn</span>
            </summary>
            <div class="mt-3 grid gap-3 md:grid-cols-2">
                @foreach([['investor','Chủ đầu tư','Tìm chủ đầu tư...',$filterOptions['investors']],['medicine','Sản phẩm','Tìm sản phẩm...',$filterOptions['medicines']]] as [$filterName,$filterLabel,$filterPlaceholder,$filterItems])
                    <div class="relative" data-bid-filter-combobox>
                        <label for="bid-filter-{{ $filterName }}-search" class="block text-xs font-bold text-slate-600">{{ $filterLabel }}</label>
                        <input type="hidden" name="{{ $filterName }}" value="{{ $filters[$filterName] }}">
                        <div class="relative mt-1.5">
                            <input id="bid-filter-{{ $filterName }}-search" type="search" value="{{ $filters[$filterName] }}" placeholder="{{ $filterPlaceholder }}" autocomplete="off" data-bid-filter-search
                                class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 pr-10 text-sm outline-none focus:border-slate-400">
                            <button type="button" data-bid-filter-clear aria-label="Xóa {{ mb_strtolower($filterLabel) }}" class="{{ $filters[$filterName] === '' ? 'hidden ' : '' }}absolute right-1.5 top-1.5 flex h-8 w-8 items-center justify-center rounded-full text-slate-400">×</button>
                        </div>
                        <div data-bid-filter-options class="absolute z-30 mt-1 hidden max-h-64 w-full overflow-y-auto rounded-2xl border border-slate-200 bg-white p-1.5 shadow-xl">
                            @foreach($filterItems as $option)
                                <button type="button" data-bid-filter-option data-value="{{ $option }}" class="block min-h-10 w-full rounded-xl px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">{{ $option }}</button>
                            @endforeach
                            <p data-bid-filter-empty class="hidden px-3 py-3 text-sm text-slate-500">Không tìm thấy kết quả.</p>
                        </div>
                    </div>
                @endforeach
                <label class="block text-xs font-bold text-slate-600">Giá trị
                    <select name="value_sort" class="mt-1.5 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm">
                        <option value="">Mặc định</option><option value="desc" @selected($filters['value_sort'] === 'desc')>Cao nhất → thấp nhất</option><option value="asc" @selected($filters['value_sort'] === 'asc')>Thấp nhất → cao nhất</option>
                    </select>
                </label>
                <label class="block text-xs font-bold text-slate-600">Thiết lập kinh doanh
                    <select name="business_setup" class="mt-1.5 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm">
                        <option value="">Tất cả</option><option value="commercial_missing" @selected($filters['business_setup'] === 'commercial_missing')>Chưa có CS kinh doanh</option><option value="commercial_ready" @selected($filters['business_setup'] === 'commercial_ready')>Đã có CS kinh doanh</option><option value="allocation_missing" @selected($filters['business_setup'] === 'allocation_missing')>Chưa phân bổ SL</option><option value="allocation_ready" @selected($filters['business_setup'] === 'allocation_ready')>Đã phân bổ SL</option>
                    </select>
                </label>
            </div>
            <div class="mt-3 flex items-center justify-end gap-2">
                @if($activeFilterCount > 0)<a href="{{ route('client.pharma.bid-awards', $search !== '' ? ['q' => $search] : []) }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 px-4 text-sm font-bold text-slate-600">Xóa bộ lọc</a>@endif
                <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-slate-950 px-4 text-sm font-bold text-white">Áp dụng</button>
            </div>
        </details>
    </form>

    <div class="flex items-center justify-between gap-3 px-1">
        <div>
            <h2 class="text-base font-black text-slate-950">Danh sách kết quả trúng thầu</h2>
            <p class="mt-0.5 text-xs text-slate-500">{{ number_format($results->total()) }} kết quả trúng thầu · phần được giao sẽ được đánh dấu riêng</p>
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
                @if((int) $result->my_products_count > 0)
                    <div class="mt-3 inline-flex rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-black text-emerald-700">Trong phạm vi tôi phụ trách</div>
                @endif
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-700">{{ number_format($result->products_count) }} sản phẩm</span>
                    <span class="rounded-full px-3 py-1.5 text-xs font-bold {{ (int)$result->allocated_product_count > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">Phân bổ: {{ (int)$result->allocated_product_count > 0 ? 'Đã thiết lập' : 'Chưa thiết lập' }}</span>
                    <span class="rounded-full px-3 py-1.5 text-xs font-bold {{ (int)$result->commercial_product_count > 0 ? 'bg-sky-50 text-sky-700' : 'bg-slate-100 text-slate-500' }}">CSKD: {{ (int)$result->commercial_product_count > 0 ? 'Đã thiết lập' : 'Chưa thiết lập' }}</span>
                </div>
                <div class="mt-3 flex items-center justify-between gap-3 border-t border-slate-100 pt-3 text-xs">
                    <span class="min-w-0 text-slate-500">Giá trị KQLCNT <strong class="text-slate-800">{{ number_format((float) $result->total_value, 0, ',', '.') }} VNĐ</strong></span>
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
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500 md:col-span-2 xl:col-span-3">Chưa có kết quả trúng thầu phù hợp.</div>
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
    form?.querySelectorAll('select').forEach(select=>select.addEventListener('change',()=>form.requestSubmit()));
    form?.querySelectorAll('[data-bid-filter-combobox]').forEach(box=>{
        const search=box.querySelector('[data-bid-filter-search]'), hidden=box.querySelector('input[type="hidden"]'), options=box.querySelector('[data-bid-filter-options]'), clear=box.querySelector('[data-bid-filter-clear]'), empty=box.querySelector('[data-bid-filter-empty]');
        const rows=[...box.querySelectorAll('[data-bid-filter-option]')];
        const filter=()=>{
            const term=(search.value||'').trim().toLocaleLowerCase('vi');
            let visible=0;
            rows.forEach(row=>{ const show=!term||row.dataset.value.toLocaleLowerCase('vi').includes(term); row.classList.toggle('hidden',!show); if(show) visible++; });
            empty?.classList.toggle('hidden',visible!==0);
            options?.classList.remove('hidden');
        };
        search?.addEventListener('focus',filter);
        search?.addEventListener('input',()=>{ hidden.value=''; clear?.classList.toggle('hidden',search.value===''); filter(); });
        rows.forEach(row=>row.addEventListener('click',()=>{ hidden.value=row.dataset.value; search.value=row.dataset.value; options?.classList.add('hidden'); clear?.classList.remove('hidden'); form.requestSubmit(); }));
        clear?.addEventListener('click',()=>{ hidden.value=''; search.value=''; options?.classList.add('hidden'); clear.classList.add('hidden'); form.requestSubmit(); });
        document.addEventListener('click',event=>{ if(!box.contains(event.target)) options?.classList.add('hidden'); });
    });
    const initialSearch=(input?.value||'').trim();
    let timer;
    input?.addEventListener('input',()=>{
        clearTimeout(timer);
        timer=setTimeout(()=>{
            const nextSearch=(input.value||'').trim();
            if(nextSearch===initialSearch) return;
            form.requestSubmit();
        },750);
    });
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
