@extends('ClientPortal::layouts.application')
@section('title', 'Tra cứu thuốc trúng thầu')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)
@section('content')
<div class="min-w-0 space-y-4" id="pharma-award-lookup">
    <section class="rounded-3xl bg-slate-950 px-5 py-5 text-white sm:px-7">
        <a href="{{ route('client.pharma.lookups') }}" class="inline-flex min-h-10 items-center text-sm font-semibold text-slate-200">← Tra cứu</a>
        <h1 class="mt-1 text-xl font-black sm:text-2xl">Tra cứu thuốc trúng thầu</h1>
        <p class="mt-1 text-sm text-slate-300">Nguồn công khai · Độc lập ứng dụng Mua sắm công · Không thay thế giá kê khai/giá bán</p>
    </section>
    @if(session('success'))<div role="status" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">{{ session('success') }}</div>@endif
    @if($errors->any())<div role="alert" class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>@endif
    <section class="rounded-3xl border border-slate-200 bg-white p-4 sm:p-5">
        <form action="{{ route('client.pharma.award-prices') }}" method="GET" class="space-y-3">
            <div class="flex flex-col gap-2 sm:flex-row">
                <label for="award-keyword" class="sr-only">Tìm thuốc trúng thầu</label>
                <div class="relative min-w-0 flex-1"><input type="search" id="award-keyword" name="keyword" value="{{ $keyword }}" minlength="2" maxlength="200" placeholder="Tên thuốc, hoạt chất, mã TBMT..." class="w-full rounded-xl border border-slate-300 px-4 py-3 pr-12 text-base" required><button type="button" id="award-clear" aria-label="Xóa từ khóa" class="absolute right-2 top-1/2 hidden min-h-9 min-w-9 -translate-y-1/2 rounded-lg text-lg font-bold text-slate-600">×</button></div>
                <button type="submit" class="min-h-11 rounded-xl bg-slate-950 px-6 py-3 font-bold text-white">⌕ Tra cứu</button>
            </div>
        </form>
    </section>
    @if($result !== null)
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-3 text-sm">
            <span class="text-slate-600">Bộ nhớ tra cứu · Dữ liệu được lưu tạm 5 phút</span>
            <a class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 px-4 py-2 font-semibold text-slate-800" href="{{ route('client.pharma.award-prices', ['keyword' => $keyword, 'page' => $page, 'fresh' => 1]) }}">↻ Tra cứu dữ liệu mới nhất</a>
        </div>
        @if(!($result['success'] ?? false))
            <div role="alert" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">{{ $result['message'] ?? 'Không thể tra cứu.' }}</div>
        @else
            @php
                $groups = collect($result['items'] ?? [])->pluck('nhomThuoc')
                    ->filter(fn ($value) => is_scalar($value) && trim((string) $value) !== '')
                    ->map(fn ($value) => (string) $value)->unique()->sort()->values();
            @endphp
            @if(count($result['items'] ?? []) > 0)
                <details id="award-filters" class="rounded-2xl border border-slate-200 bg-white p-4">
                    <summary class="min-h-10 cursor-pointer text-sm font-bold text-slate-900">☷ Bộ lọc kết quả <span class="font-normal text-slate-500">(trong trang hiện tại)</span></summary>
                    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <label class="text-xs font-semibold text-slate-700">Tên thuốc, hoạt chất, hàm lượng
                            <input id="award-local-search" type="search" placeholder="Tìm trong 20 kết quả..." class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 px-3 text-sm" autocomplete="off">
                        </label>
                        <label class="text-xs font-semibold text-slate-700">Nhóm thuốc
                            <select id="award-group-filter" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 px-3 text-sm">
                                <option value="">Tất cả nhóm</option>
                                @foreach($groups as $group)<option value="{{ $group }}">{{ is_numeric($group) ? 'Nhóm '.$group : $group }}</option>@endforeach
                            </select>
                        </label>
                        <label class="text-xs font-semibold text-slate-700">Giá từ (đ)
                            <input id="award-price-min" type="number" min="0" step="1" inputmode="numeric" placeholder="Tối thiểu" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 px-3 text-sm">
                        </label>
                        <label class="text-xs font-semibold text-slate-700">Giá đến (đ)
                            <input id="award-price-max" type="number" min="0" step="1" inputmode="numeric" placeholder="Tối đa" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 px-3 text-sm">
                        </label>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                        <span id="award-filter-count" role="status" aria-live="polite" class="text-xs text-slate-600">Đang hiển thị {{ count($result['items']) }} / {{ count($result['items']) }} bản ghi của trang {{ $page }}</span>
                        <button type="button" id="award-reset-filters" class="min-h-10 rounded-xl border border-slate-300 px-4 text-sm font-semibold">Xóa bộ lọc</button>
                    </div>
                </details>
            @endif
            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-4">
                    <div>
                        <h2 class="font-bold text-slate-950">Kết quả “{{ $keyword }}”</h2>
                        <p class="text-xs text-slate-500">{{ number_format($result['total'] ?? 0, 0, ',', '.') }} bản ghi từ nguồn · 20 dòng/trang · Bộ lọc chỉ áp dụng trang {{ $page }}</p>
                    </div>
                    @if($canSync)
                        <form id="award-select-form" action="{{ route('client.pharma.award-prices.sync.preview') }}" method="POST">
                            @csrf
                            <input type="hidden" name="keyword" value="{{ $keyword }}">
                            <input type="hidden" name="page" value="{{ $page }}">
                            <button type="submit" class="min-h-11 rounded-xl bg-emerald-600 px-4 font-bold text-white">Đồng bộ đã chọn (<span id="award-selected-count">0</span>)</button>
                        </form>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-bold uppercase text-slate-500">
                            <tr>
                                <th class="px-3 py-3">@if($canSync)<input type="checkbox" id="award-select-all" aria-label="Chọn tất cả kết quả đang hiển thị">@endif</th>
                                <th class="px-3 py-3">Thuốc</th>
                                <th class="px-3 py-3">Hoạt chất</th>
                                <th class="px-3 py-3">Giá</th>
                                <th class="px-3 py-3">Nơi trúng thầu</th>
                                <th class="px-3 py-3">Nhóm thuốc</th>
                                <th class="px-3 py-3">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($result['items'] as $item)
                                @php
                                    $itemId = is_scalar($item['id'] ?? null) ? (string) $item['id'] : '';
                                    $group = is_scalar($item['nhomThuoc'] ?? null) ? (string) $item['nhomThuoc'] : '';
                                    $place = $item['tenCdtBmt'] ?? null;
                                    $place = is_array($place) ? implode(', ', array_filter($place, 'is_scalar')) : (is_scalar($place) ? (string) $place : '');
                                    $isSaved = in_array($itemId, $savedIds, true);
                                @endphp
                                <tr class="award-result-row align-top hover:bg-slate-50"
                                    data-name="{{ $item['tenThuoc'] ?? '' }}"
                                    data-ingredient="{{ $item['tenHoatChat'] ?? '' }}"
                                    data-strength="{{ $item['nongDo'] ?? '' }}"
                                    data-group="{{ $group }}"
                                    data-price="{{ is_numeric($item['donGia'] ?? null) ? $item['donGia'] : '' }}">
                                    <td class="px-3 py-4">
                                        @if($canSync && $itemId !== '')
                                            <input type="checkbox" value="{{ $itemId }}" aria-label="Chọn {{ $item['tenThuoc'] ?? 'thuốc' }}" form="award-select-form" name="selected[]" class="award-row-select">
                                        @endif
                                    </td>
                                    <td class="px-3 py-4"><p class="font-bold text-slate-950">{{ $item['tenThuoc'] ?? '—' }}</p><p class="mt-1 text-xs text-slate-500">{{ $item['nongDo'] ?? '—' }} · {{ $item['donViTinh'] ?? '—' }}</p></td>
                                    <td class="px-3 py-4">{{ $item['tenHoatChat'] ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 font-bold">{{ is_numeric($item['donGia'] ?? null) ? number_format((float) $item['donGia'], 0, ',', '.').' đ' : '—' }}</td>
                                    <td class="px-3 py-4">{{ $place ?: '—' }}</td>
                                    <td class="px-3 py-4">{{ $group !== '' ? (is_numeric($group) ? 'Nhóm '.$group : $group) : '—' }}</td>
                                    <td class="px-3 py-4">
                                        <div class="flex items-center gap-2">
                                            @if($itemId !== '')
                                                <a href="{{ route('client.pharma.award-prices.show', ['sourceId' => $itemId, 'keyword' => $keyword, 'page' => $page]) }}"
                                                    aria-label="Xem chi tiết {{ $item['tenThuoc'] ?? 'thuốc' }}" title="Xem chi tiết"
                                                    class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-xl border border-slate-300 text-indigo-700 focus-visible:ring-2 focus-visible:ring-indigo-500">
                                                    <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                                </a>
                                                <form method="POST" action="{{ route('client.pharma.award-prices.wishlist.toggle') }}">
                                                    @csrf
                                                    <input type="hidden" name="keyword" value="{{ $keyword }}">
                                                    <input type="hidden" name="page" value="{{ $page }}">
                                                    <input type="hidden" name="source_id" value="{{ $itemId }}">
                                                    <button type="submit" title="{{ $isSaved ? 'Bỏ quan tâm' : 'Quan tâm' }}" aria-label="{{ $isSaved ? 'Bỏ quan tâm' : 'Quan tâm' }} {{ $item['tenThuoc'] ?? 'thuốc' }}"
                                                        aria-pressed="{{ $isSaved ? 'true' : 'false' }}"
                                                        class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-xl border {{ $isSaved ? 'border-rose-300 bg-rose-50 text-rose-600' : 'border-slate-300 text-slate-600' }} focus-visible:ring-2 focus-visible:ring-rose-500">
                                                        <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="{{ $isSaved ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/></svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-5 py-8 text-center text-slate-500">Không tìm thấy kết quả phù hợp.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div id="award-no-filter-results" class="hidden border-t border-slate-200 p-5 text-center text-sm text-slate-600">Không có thuốc phù hợp bộ lọc trên trang hiện tại.</div>
                <div class="flex items-center justify-between gap-2 border-t border-slate-200 p-4 text-sm">
                    <span>Trang {{ $page }}</span>
                    <div class="flex gap-2">
                        @if($page > 1)<a class="min-h-10 rounded-xl border border-slate-300 px-3 py-2" href="{{ route('client.pharma.award-prices', ['keyword' => $keyword, 'page' => $page - 1]) }}">← Trước</a>@endif
                        @if($page * 20 < ($result['total'] ?? 0) && $page < 100)<a class="min-h-10 rounded-xl border border-slate-300 px-3 py-2" href="{{ route('client.pharma.award-prices', ['keyword' => $keyword, 'page' => $page + 1]) }}">Sau →</a>@endif
                    </div>
                </div>
            </section>
        @endif
    @endif

</div>
@push('application-scripts')
<script defer src="{{ asset('demo/pharma-award/award-interactions.js') }}"></script>
@endpush
@endsection
