@extends('ClientPortal::layouts.application')
@section('title', $presentation['page_title'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('content')
<div class="min-w-0 space-y-4 pb-6">
    <header class="flex flex-wrap items-start justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
        <div class="min-w-0">
            <a href="{{ route('client.pharma.dashboard') }}" class="text-xs font-medium text-indigo-700 hover:underline">← Pharma</a>
            <p class="mt-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ $presentation['eyebrow'] }}</p>
            <h1 class="mt-1 text-xl font-extrabold text-slate-950 sm:text-2xl">{{ $presentation['page_title'] }}</h1>
            <p class="mt-1 text-sm text-slate-600">{{ $presentation['page_description'] }}</p>
        </div>
        <div class="rounded-xl bg-indigo-50 px-4 py-3 text-right">
            <p class="text-xs font-medium text-indigo-700">Hồ sơ phù hợp</p>
            <p class="text-2xl font-extrabold tabular-nums text-indigo-950">{{ number_format($items->total(), 0, ',', '.') }}</p>
        </div>
    </header>

    <section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Tổng quan theo dõi thầu">
        @foreach([
            ['label' => 'Tổng hồ sơ trúng thầu', 'value' => number_format($dashboard['total'], 0, ',', '.'), 'unit' => 'hồ sơ'],
            ['label' => 'Hồ sơ đang hoạt động', 'value' => number_format($dashboard['active'], 0, ',', '.'), 'unit' => 'hồ sơ'],
            ['label' => 'Tổng giá trị trúng thầu', 'value' => number_format($dashboard['amount'], 0, ',', '.'), 'unit' => 'đ'],
            ['label' => 'Giá trị đang hoạt động', 'value' => number_format($dashboard['active_amount'], 0, ',', '.'), 'unit' => 'đ'],
        ] as $metric)
            <div class="min-w-0 rounded-2xl border border-slate-200 bg-white p-3 sm:p-4">
                <p class="text-xs font-medium text-slate-600">{{ $metric['label'] }}</p>
                <p class="mt-2 break-all text-lg font-extrabold tabular-nums text-slate-950 sm:text-xl">{{ $metric['value'] }} <span class="text-xs font-medium text-slate-500">{{ $metric['unit'] }}</span></p>
            </div>
        @endforeach
    </section>
    <p class="text-xs text-slate-500">Chỉ số “đang hoạt động” dựa trên trạng thái dữ liệu KQLCNT, chưa xác nhận thời hạn hiệu lực hợp đồng.</p>

    <form method="GET" action="{{ route('client.pharma.kqlcnt-awards.index') }}" class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
        <h2 class="text-base font-bold text-slate-950">Bộ lọc hồ sơ</h2>
        <p class="mt-1 text-xs text-slate-500">Chọn mã TBMT hoặc chủ đầu tư từ danh sách có tìm kiếm.</p>
        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            <div x-data="{ open: false, term: '', selected: @js($filters['notify_no'] ?? ''), options: @js($options['notices']) }" x-init="term = selected" @click.outside="open = false" class="relative min-w-0">
                <label for="kqlcnt-notify" class="block text-xs font-semibold text-slate-700">Mã TBMT</label>
                <input type="hidden" name="notify_no" :value="selected">
                <input id="kqlcnt-notify" type="search" x-model="term" @focus="open = true" @input="selected = ''; open = true" @keydown.escape="open = false" autocomplete="off" placeholder="Tìm và chọn mã TBMT..." class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm">
                <div x-show="open" x-cloak class="absolute z-30 mt-1 max-h-56 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
                    <button type="button" @click="selected = ''; term = ''; open = false" class="block w-full px-3 py-2 text-left text-sm hover:bg-indigo-50">Tất cả mã TBMT</button>
                    <template x-for="option in options.filter(v => v.toLowerCase().includes(term.toLowerCase()))" :key="option">
                        <button type="button" @click="selected = option; term = option; open = false" x-text="option" class="block w-full px-3 py-2 text-left text-sm hover:bg-indigo-50"></button>
                    </template>
                </div>
            </div>
            <div x-data="{ open: false, selected: @js($filters['investor_code'] ?? ''), term: '', options: @js($options['investors']->map(fn ($i) => ['code' => $i->investor_code, 'name' => $i->investor_name])->values()) }" x-init="term = options.find(v => v.code === selected)?.name || ''" @click.outside="open = false" class="relative min-w-0">
                <label for="kqlcnt-investor" class="block text-xs font-semibold text-slate-700">Chủ đầu tư</label>
                <input type="hidden" name="investor_code" :value="selected">
                <input id="kqlcnt-investor" type="search" x-model="term" @focus="open = true" @input="selected = ''; open = true" @keydown.escape="open = false" autocomplete="off" placeholder="Tìm và chọn chủ đầu tư..." class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm">
                <div x-show="open" x-cloak class="absolute z-30 mt-1 max-h-56 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
                    <button type="button" @click="selected = ''; term = ''; open = false" class="block w-full px-3 py-2 text-left text-sm hover:bg-indigo-50">Tất cả chủ đầu tư</button>
                    <template x-for="option in options.filter(v => (v.name || '').toLowerCase().includes(term.toLowerCase()) || (v.code || '').toLowerCase().includes(term.toLowerCase()))" :key="option.code">
                        <button type="button" @click="selected = option.code; term = option.name; open = false" x-text="option.name + ' — ' + option.code" class="block w-full px-3 py-2 text-left text-sm hover:bg-indigo-50"></button>
                    </template>
                </div>
            </div>
            <label class="block text-xs font-semibold text-slate-700">Trạng thái
                <select name="active" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-900">
                    <option value="">Tất cả trạng thái</option>
                    <option value="1" @selected(($filters['active'] ?? '') === '1')>Đang hoạt động</option>
                    <option value="0" @selected(($filters['active'] ?? '') === '0')>Không hoạt động</option>
                </select>
            </label>
            <x-pwa-search name="search" :value="$filters['search'] ?? ''" label="Tìm nhà thầu / TBMT / Chủ đầu tư" placeholder="Nhập từ khóa..." />
            <x-pwa-search name="medicine" :value="$filters['medicine'] ?? ''" label="Tên thuốc / Hoạt chất" placeholder="Nhập tên thuốc hoặc hoạt chất..." />
        </div>
        <div class="mt-4 flex flex-wrap items-center gap-2">
            <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-slate-950 px-5 text-sm font-semibold text-white">Lọc hồ sơ</button>
            <a href="{{ route('client.pharma.kqlcnt-awards.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-700">Xóa lọc</a>
            @if(filled($filters['medicine'] ?? null))
                <span class="text-xs text-slate-500">Kết quả là hồ sơ có chứa thuốc/hoạt chất đã tìm.</span>
            @endif
        </div>
    </form>

    <section class="space-y-3" aria-label="Danh sách hồ sơ trúng thầu">
        <div class="flex items-center justify-between gap-2 px-1">
            <h2 class="text-base font-bold text-slate-900">Hồ sơ kết quả trúng thầu</h2>
            <span class="text-xs text-slate-500">{{ $items->total() }} hồ sơ</span>
        </div>
        @forelse($items as $item)
            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 px-4 py-3 sm:px-5">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Mã TBMT</p>
                        <h3 class="mt-0.5 break-all text-base font-extrabold text-slate-950">{{ $item->notify_no ?: 'Chưa có mã TBMT' }}</h3>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $item->all_active ? 'bg-emerald-50 text-emerald-800' : ($item->any_active ? 'bg-amber-50 text-amber-800' : 'bg-slate-100 text-slate-700') }}">
                        {{ $item->all_active ? 'Đang hoạt động' : ($item->any_active ? 'Trạng thái hỗn hợp' : 'Không hoạt động') }}
                    </span>
                </div>
                <div class="grid gap-3 px-4 py-4 sm:grid-cols-2 sm:px-5 xl:grid-cols-3">
                    <div class="min-w-0">
                        <p class="text-xs text-slate-500">Nhà thầu</p>
                        <p class="mt-1 break-words text-sm font-semibold text-slate-900">{{ $item->contractor_name ?: 'Chưa cập nhật' }}</p>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-slate-500">Chủ đầu tư</p>
                        <p class="mt-1 break-words text-sm font-semibold text-slate-900">{{ $item->investor_name ?: 'Chưa cập nhật' }}</p>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-slate-500">Thời gian thực hiện hợp đồng</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $item->contract_period_text ?: ($item->contract_period ? $item->contract_period.' '.($item->contract_period_unit ?: 'tháng') : 'Chưa cập nhật') }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Số tiền trúng thầu</p>
                        <p class="mt-1 text-lg font-extrabold tabular-nums text-slate-950">{{ number_format((float) $item->winning_amount, 0, ',', '.') }} <span class="text-xs font-semibold text-slate-500">đ</span></p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Số lượng sản phẩm</p>
                        <p class="mt-1 text-lg font-extrabold tabular-nums text-slate-950">{{ number_format((int) $item->products_count, 0, ',', '.') }} <span class="text-xs font-semibold text-slate-500">sản phẩm</span></p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/60 px-4 py-3 sm:px-5">
                    <span class="text-sm font-semibold text-slate-700">Danh mục thuốc trúng thầu</span>
                    <a href="{{ route('client.pharma.kqlcnt-awards.show', $item->id) }}" class="inline-flex min-h-11 items-center rounded-xl bg-indigo-700 px-4 text-sm font-bold text-white hover:bg-indigo-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Xem chi tiết <span aria-hidden="true" class="ml-2">→</span></a>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-600">Không tìm thấy hồ sơ TBMT phù hợp. Hãy thử điều chỉnh từ khóa hoặc trạng thái.</div>
        @endforelse
    </section>
    {{ $items->links() }}
</div>
@endsection
