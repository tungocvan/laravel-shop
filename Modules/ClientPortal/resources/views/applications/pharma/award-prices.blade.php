@extends('ClientPortal::layouts.application')
@section('title', 'Tra cứu thuốc trúng thầu')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('content')
<div class="min-w-0 space-y-4" x-data="{filters: false, detail: null, selected: [], allIds: @js(collect($result['items'] ?? [])->pluck('id')->filter()->map(fn ($id) => (string) $id)->values()->all())}">
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
                <input id="award-keyword" name="keyword" value="{{ $keyword }}" minlength="2" maxlength="200" placeholder="Tên thuốc, hoạt chất, mã TBMT..." class="min-w-0 flex-1 rounded-xl border border-slate-300 px-4 py-3 text-base" required>
                <button type="submit" class="min-h-11 rounded-xl bg-slate-950 px-6 py-3 font-bold text-white">⌕ Tra cứu</button>
            </div>
            <button type="button" @click="filters = !filters" :aria-expanded="filters.toString()" aria-controls="award-filters" class="min-h-10 rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-700">☷ Bộ lọc</button>
            <div id="award-filters" x-show="filters" x-cloak class="flex flex-wrap items-end gap-3">
                <div><label for="award-unit" class="mb-1 block text-xs font-bold text-slate-600">Đơn vị tính (lọc trong trang)</label><input id="award-unit" name="unit" value="{{ $unit }}" placeholder="Ví dụ: Chai" class="rounded-xl border border-slate-300 px-3 py-2 text-sm"></div>
                <button type="submit" class="min-h-10 rounded-xl border border-slate-300 px-4 text-sm font-semibold">Áp dụng</button>
            </div>
        </form>
    </section>
    @if($result !== null)
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-3 text-sm">
            <span class="text-slate-600">Bộ nhớ tra cứu · Dữ liệu được lưu tạm 5 phút</span>
            <a class="min-h-10 rounded-xl border border-slate-300 px-4 py-2 font-semibold text-slate-800" href="{{ route('client.pharma.award-prices', ['keyword' => $keyword, 'page' => $page, 'unit' => $unit, 'fresh' => 1]) }}">↻ Tra cứu dữ liệu mới nhất</a>
        </div>
        @if(!($result['success'] ?? false))
            <div role="alert" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">{{ $result['message'] ?? 'Không thể tra cứu.' }}</div>
        @else
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                @foreach(['Kết quả' => $result['stats']['count'], 'Thấp nhất' => $result['stats']['min'], 'Trung bình' => $result['stats']['avg'], 'Cao nhất' => $result['stats']['max']] as $label => $value)
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold text-slate-500">{{ $label }}</p>
                        <p class="mt-1 text-xl font-black text-slate-950">{{ $value === null ? '—' : ($label === 'Kết quả' ? number_format($value, 0, ',', '.') : number_format($value, 0, ',', '.').' đ') }}</p>
                    </div>
                @endforeach
            </div>
            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-4">
                    <div><h2 class="font-bold text-slate-950">Kết quả “{{ $keyword }}”</h2><p class="text-xs text-slate-500">{{ number_format($result['total'] ?? 0, 0, ',', '.') }} bản ghi từ nguồn · 20 dòng/trang · Thống kê theo trang hiện tại</p></div>
                    @if($canSync)
                        <form id="award-select-form" action="{{ route('client.pharma.award-prices.sync.preview') }}" method="POST">
                            @csrf
                            <input type="hidden" name="keyword" value="{{ $keyword }}">
                            <input type="hidden" name="page" value="{{ $page }}">
                            <template x-for="id in selected" :key="id"><input type="hidden" name="selected[]" :value="id"></template>
                            <button type="submit" :disabled="selected.length === 0" class="min-h-11 rounded-xl bg-emerald-600 px-4 font-bold text-white disabled:cursor-not-allowed disabled:opacity-40">Đồng bộ đã chọn (<span x-text="selected.length"></span>)</button>
                        </form>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[850px] text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-bold uppercase text-slate-500"><tr>
                            <th class="px-3 py-3">@if($canSync)<input type="checkbox" aria-label="Chọn tất cả trên trang" @change="selected = $event.target.checked ? [...allIds] : []">@endif</th>
                            <th class="px-3 py-3">Thuốc</th><th class="px-3 py-3">Hoạt chất</th><th class="px-3 py-3">Giá</th><th class="px-3 py-3">Đơn vị trúng thầu</th><th class="px-3 py-3">TBMT</th><th class="px-3 py-3">Thao tác</th>
                        </tr></thead>
                        <tbody class="divide-y divide-slate-100">
                        @forelse($result['items'] as $item)
                            @php
                                $winning = $item['winningName'] ?? [];
                                $winning = is_array($winning) ? implode(', ', array_filter($winning, 'is_scalar')) : (is_scalar($winning) ? (string) $winning : '');
                                $itemId = is_scalar($item['id'] ?? null) ? (string) $item['id'] : '';
                            @endphp
                            <tr class="align-top hover:bg-slate-50">
                                <td class="px-3 py-4">@if($canSync && $itemId !== '')<input type="checkbox" value="{{ $itemId }}" aria-label="Chọn {{ $item['tenThuoc'] ?? 'thuốc' }}" x-model="selected">@endif</td>
                                <td class="px-3 py-4"><p class="font-bold text-slate-950">{{ $item['tenThuoc'] ?? '—' }}</p><p class="mt-1 text-xs text-slate-500">{{ $item['nongDo'] ?? '—' }} · {{ $item['donViTinh'] ?? '—' }}</p></td>
                                <td class="px-3 py-4">{{ $item['tenHoatChat'] ?? '—' }}</td>
                                <td class="whitespace-nowrap px-3 py-4 font-bold">{{ is_numeric($item['donGia'] ?? null) ? number_format((float) $item['donGia'], 0, ',', '.').' đ' : '—' }}</td>
                                <td class="px-3 py-4">{{ $winning ?: '—' }}</td>
                                <td class="px-3 py-4">{{ $item['maTbmt'] ?? '—' }}</td>
                                <td class="px-3 py-4"><button type="button" @click="detail = @js($item)" aria-label="Xem chi tiết {{ $item['tenThuoc'] ?? 'thuốc' }}" class="min-h-10 rounded-xl border border-slate-300 px-3 font-semibold text-indigo-700">◉ Xem</button></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-5 py-8 text-center text-slate-500">Không tìm thấy kết quả phù hợp.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="flex items-center justify-between gap-2 border-t border-slate-200 p-4 text-sm">
                    <span>Trang {{ $page }}</span>
                    <div class="flex gap-2">
                        @if($page > 1)<a class="rounded-xl border border-slate-300 px-3 py-2" href="{{ route('client.pharma.award-prices', ['keyword' => $keyword, 'page' => $page - 1, 'unit' => $unit]) }}">← Trước</a>@endif
                        @if($page * 20 < ($result['total'] ?? 0) && $page < 100)<a class="rounded-xl border border-slate-300 px-3 py-2" href="{{ route('client.pharma.award-prices', ['keyword' => $keyword, 'page' => $page + 1, 'unit' => $unit]) }}">Sau →</a>@endif
                    </div>
                </div>
            </section>
        @endif
    @endif
    <template x-teleport="body">
        <div x-show="detail !== null" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-3" role="dialog" aria-modal="true" aria-label="Chi tiết kết quả trúng thầu" @keydown.escape.window="detail = null">
            <div class="max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-2xl bg-white p-5 shadow-xl" @click.outside="detail = null">
                <div class="flex items-start justify-between gap-3"><h2 class="text-xl font-black" x-text="detail?.tenThuoc || 'Chi tiết thuốc'"></h2><button type="button" @click="detail = null" class="min-h-10 rounded-xl border px-4" aria-label="Đóng chi tiết">×</button></div>
                <div class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    <template x-for="field in [['Hoạt chất','tenHoatChat'],['Số đăng ký / GPNK','gdklh_GPNK'],['ĐVT','donViTinh'],['Nồng độ','nongDo'],['Dạng bào chế','dangBaoChe'],['Quy cách','quyCachDongGoi'],['Nhà sản xuất','tenCoSoSanXuat'],['Nước sản xuất','nuocSanXuat'],['Giá trúng thầu','donGia'],['Mã TBMT','maTbmt'],['Số quyết định','soQuyetDinh'],['Đơn vị trúng thầu','winningName']]">
                        <div class="rounded-xl bg-slate-50 p-3"><p class="text-xs font-semibold text-slate-500" x-text="field[0]"></p><p class="mt-1 break-words font-medium" x-text="Array.isArray(detail?.[field[1]]) ? detail[field[1]].join(', ') : (detail?.[field[1]] ?? '—')"></p></div>
                    </template>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
