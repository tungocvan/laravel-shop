@extends('Admin::layouts.master')
@section('title', 'Dữ liệu KQLCNT đã đồng bộ')
@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Mua sắm công</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Dữ liệu chi tiết KQLCNT</h1>
            <p class="mt-1 text-sm text-gray-500">Kho dữ liệu canonical phục vụ quản trị, tra cứu và thống kê kết quả lựa chọn nhà thầu.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @include('Muasamcong::partials.dashboard-return-link')
            <button form="award-export-form" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">Xuất Excel</button>
        </div>
    </div>

    <form id="award-filter-form" method="GET" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-3 border-b border-gray-100 pb-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-bold text-gray-900">Bộ lọc dữ liệu</h2>
                <p class="mt-1 text-xs text-gray-500">Chọn TBMT, nhà thầu hoặc thuốc để lọc ngay. Hoạt chất hỗ trợ tìm theo một phần tên.</p>
            </div>
            @if(collect($filters)->filter(fn ($value) => $value !== '')->isNotEmpty())
                <a href="{{ route('muasamcong.kqlcnt-awards.index') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">Xóa bộ lọc</a>
            @endif
        </div>

        <div class="mt-5 grid gap-4 lg:grid-cols-4">
            <label class="block text-xs font-semibold text-gray-700">
                Mã TBMT
                <select id="filter-notify-no" name="notify_no" class="kqlcnt-search-select mt-1.5 w-full">
                    <option value="">Tất cả Mã TBMT</option>
                    @foreach($notifyOptions as $value)
                        <option value="{{ $value }}" @selected($filters['notify_no'] === $value)>{{ $value }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block text-xs font-semibold text-gray-700">
                Nhà thầu
                <select id="filter-contractor" name="contractor_code" class="kqlcnt-search-select mt-1.5 w-full">
                    <option value="">Tất cả nhà thầu</option>
                    @foreach($contractorOptions as $option)
                        <option value="{{ $option->contractor_code }}" @selected($filters['contractor_code'] === $option->contractor_code)>
                            {{ $option->contractor_name ?: $option->contractor_code }} — {{ $option->contractor_code }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="block text-xs font-semibold text-gray-700">
                Tên thuốc
                <select id="filter-medicine" name="medicine_name" class="kqlcnt-search-select mt-1.5 w-full">
                    <option value="">Tất cả thuốc</option>
                    @foreach($medicineOptions as $value)
                        <option value="{{ $value }}" @selected($filters['medicine_name'] === $value)>{{ $value }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block text-xs font-semibold text-gray-700">
                Hoạt chất
                <div class="mt-1.5 flex rounded-xl shadow-sm">
                    <input name="active_ingredient" value="{{ $filters['active_ingredient'] }}" placeholder="Ví dụ: Pregabalin..." class="min-w-0 flex-1 rounded-l-xl border border-r-0 border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    <button type="submit" class="rounded-r-xl border border-indigo-600 bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700">Tìm</button>
                </div>
            </label>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <label class="block text-xs font-semibold text-gray-700">
                Nguồn dữ liệu
                <input name="source" value="{{ $filters['source'] }}" placeholder="API, import, mixed..." class="mt-1.5 block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
            </label>

            <label class="block text-xs font-semibold text-gray-700">
                Trạng thái
                <select name="active" onchange="this.form.requestSubmit()" class="mt-1.5 block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    <option value="">Tất cả trạng thái</option>
                    <option value="1" @selected($filters['active'] === '1')>Đang hoạt động</option>
                    <option value="0" @selected($filters['active'] === '0')>Ngưng</option>
                </select>
            </label>

            <div class="md:col-span-2 flex items-end justify-end gap-2">
                <button type="submit" class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">Áp dụng điều kiện khác</button>
            </div>
        </div>

        <details class="mt-4 rounded-xl border border-gray-200 bg-gray-50/70" @if($filters['published_from'] || $filters['published_to'] || $filters['synced_from'] || $filters['synced_to']) open @endif>
            <summary class="cursor-pointer select-none px-4 py-3 text-sm font-semibold text-gray-700">Khoảng thời gian <span class="ml-1 text-xs font-normal text-gray-500">(bộ lọc nâng cao)</span></summary>
            <div class="grid gap-4 border-t border-gray-200 px-4 py-4 md:grid-cols-2 xl:grid-cols-4">
                <label class="block text-xs font-semibold text-gray-700">KQLCNT từ
                    <input type="date" name="published_from" value="{{ $filters['published_from'] }}" class="mt-1.5 block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                </label>
                <label class="block text-xs font-semibold text-gray-700">KQLCNT đến
                    <input type="date" name="published_to" value="{{ $filters['published_to'] }}" class="mt-1.5 block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                </label>
                <label class="block text-xs font-semibold text-gray-700">Đồng bộ từ
                    <input type="date" name="synced_from" value="{{ $filters['synced_from'] }}" class="mt-1.5 block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                </label>
                <label class="block text-xs font-semibold text-gray-700">Đồng bộ đến
                    <input type="date" name="synced_to" value="{{ $filters['synced_to'] }}" class="mt-1.5 block w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                </label>
            </div>
        </details>
    </form>

    <form id="award-export-form" method="POST" action="{{ route('muasamcong.kqlcnt-awards.export') }}">
        @csrf
        @foreach($filters as $key => $value)
            @if($value !== '')<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
        @endforeach
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50"><tr><th class="px-3 py-3"><input type="checkbox" onclick="document.querySelectorAll('.award-row').forEach(i=>i.checked=this.checked)"></th><th class="px-3 py-3 text-left">Mã TBMT</th><th class="px-3 py-3 text-left">Nhà thầu</th><th class="px-3 py-3 text-left">Thuốc / Hoạt chất</th><th class="px-3 py-3 text-right">SL</th><th class="px-3 py-3 text-right">Giá trúng</th><th class="px-3 py-3 text-right">Thành tiền</th><th class="px-3 py-3 text-left">Nguồn</th><th class="px-3 py-3 text-left">Đồng bộ</th><th class="px-3 py-3"></th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                    @forelse($items as $item)
                        <tr class="align-top hover:bg-gray-50/70"><td class="px-3 py-3"><input class="award-row" type="checkbox" name="selected_ids[]" value="{{ $item->id }}"></td><td class="px-3 py-3 font-semibold text-gray-900">{{ $item->notify_no }}<div class="mt-1 text-xs font-normal text-gray-500">{{ $item->lot_no ?: 'Chưa có mã lô' }}</div></td><td class="px-3 py-3">{{ $item->contractor_name }}<div class="text-xs text-gray-500">{{ $item->contractor_code }}</div></td><td class="px-3 py-3"><span class="font-medium">{{ $item->medicine_name ?: '—' }}</span><div class="text-xs text-gray-500">{{ $item->active_ingredient ?: '—' }} {{ $item->concentration }}</div></td><td class="px-3 py-3 text-right tabular-nums">{{ is_null($item->quantity) ? '—' : number_format((float) $item->quantity, 0, ',', '.') }}</td><td class="px-3 py-3 text-right tabular-nums">{{ is_null($item->winning_price) ? '—' : number_format((float) $item->winning_price, 0, ',', '.') }}</td><td class="px-3 py-3 text-right tabular-nums">{{ is_null($item->amount) ? '—' : number_format((float) $item->amount, 0, ',', '.') }}</td><td class="px-3 py-3 text-xs">{{ $item->source ?: $item->sync_source ?: '—' }}</td><td class="px-3 py-3 text-xs">{{ optional($item->synced_from_catalog_at)->format('d/m/Y H:i') ?: '—' }}<div class="mt-1 {{ $item->is_active ? 'text-emerald-700' : 'text-gray-500' }}">{{ $item->is_active ? 'Đang hoạt động' : 'Ngưng' }}</div></td><td class="px-3 py-3 text-right"><a href="{{ route('muasamcong.kqlcnt-awards.edit', $item) }}" class="inline-flex rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">Sửa</a></td></tr>
                    @empty
                        <tr><td colspan="10" class="px-4 py-10 text-center text-gray-500">Chưa có dữ liệu KQLCNT canonical phù hợp.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </form>
    <div>{{ $items->links() }}</div>
</div>

@once
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<style>
.kqlcnt-search-select + .ts-wrapper .ts-control{min-height:42px;border:1px solid #d1d5db!important;border-radius:.75rem!important;background:#fff!important;padding:.55rem .8rem!important;box-shadow:0 1px 2px 0 rgb(0 0 0 / .05)!important;font-size:.875rem!important}.kqlcnt-search-select + .ts-wrapper.focus .ts-control{border-color:#6366f1!important;box-shadow:0 0 0 3px rgb(99 102 241 / .12)!important}.kqlcnt-search-select + .ts-wrapper .ts-dropdown{border:1px solid #e5e7eb!important;border-radius:.75rem!important;box-shadow:0 10px 25px rgb(15 23 42 / .12)!important;overflow:hidden}.kqlcnt-search-select + .ts-wrapper .ts-dropdown .active{background:#eef2ff!important;color:#3730a3!important}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('award-filter-form');
    ['filter-notify-no', 'filter-contractor', 'filter-medicine'].forEach(function (id) {
        const element = document.getElementById(id);
        if (!element || typeof TomSelect === 'undefined') return;
        new TomSelect(element, {
            plugins: ['dropdown_input'],
            create: false,
            allowEmptyOption: true,
            maxOptions: 500,
            onChange: function () { form.requestSubmit(); }
        });
    });
});
</script>
@endonce
@endsection
