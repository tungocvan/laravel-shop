<section class="mt-6 rounded-2xl border border-gray-200 bg-gray-50/60 p-4">
    <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h4 class="text-base font-bold text-gray-900">Danh mục mời thầu (HSMT)</h4>
                @if ($hsmt)
                    <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                        {{ number_format($hsmt['total'] ?? 0, 0, ',', '.') }} mặt hàng
                    </span>
                @endif
            </div>
            <p class="mt-1 text-sm text-gray-500">
                Đây là toàn bộ danh mục mời thầu của TBMT, không phải danh mục thuốc đã xác minh thuộc nhà thầu đang xem.
            </p>
        </div>

        @if (!$hsmt)
            <button type="button" wire:click="loadHsmt" wire:loading.attr="disabled" wire:target="loadHsmt"
                    class="inline-flex items-center justify-center rounded-lg border border-indigo-200 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 shadow-sm hover:bg-indigo-50 disabled:opacity-50">
                <span wire:loading.remove wire:target="loadHsmt">Tải danh mục HSMT</span>
                <span wire:loading wire:target="loadHsmt">Đang tải dữ liệu lớn...</span>
            </button>
        @endif
    </div>

    @if ($hsmt)
        <div class="mt-4 grid gap-3 lg:grid-cols-[minmax(0,1fr)_220px_auto] lg:items-end">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Tìm trong danh mục</label>
                <input type="text" wire:model.live.debounce.300ms="hsmtSearch"
                       placeholder="Mã lô, hoạt chất, tên thuốc, mã thuốc..."
                       class="w-full rounded-lg border-gray-300 bg-white text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Nhóm thuốc</label>
                <select wire:model.live="hsmtGroup" class="w-full rounded-lg border-gray-300 bg-white text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Tất cả nhóm</option>
                    @foreach ($hsmtGroups as $group)
                        <option value="{{ $group }}">{{ $group }}</option>
                    @endforeach
                </select>
            </div>
            <div class="text-sm text-gray-600 lg:text-right">
                Hiển thị <strong class="text-gray-900">{{ number_format($hsmtFilteredTotal, 0, ',', '.') }}</strong> kết quả
            </div>
        </div>

        <div class="mt-4 max-h-[55vh] overflow-auto overscroll-contain rounded-xl border border-gray-200 bg-white">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="sticky top-0 z-10 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 shadow-sm">
                <tr>
                    <th class="whitespace-nowrap px-4 py-3">Mã lô</th>
                    <th class="min-w-56 px-4 py-3">Hoạt chất / Thuốc</th>
                    <th class="min-w-40 px-4 py-3">Nồng độ</th>
                    <th class="min-w-40 px-4 py-3">Đường dùng</th>
                    <th class="min-w-36 px-4 py-3">Nhóm thuốc</th>
                    <th class="whitespace-nowrap px-4 py-3 text-right">Số lượng</th>
                    <th class="whitespace-nowrap px-4 py-3 text-right">Giá KH</th>
                    <th class="whitespace-nowrap px-4 py-3 text-right">Giá lô</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                @forelse ($hsmtItems as $item)
                    <tr class="align-top hover:bg-gray-50">
                        <td class="whitespace-nowrap px-4 py-3 font-semibold text-indigo-700">{{ $item['lot_no'] ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $item['medicine_name'] ?: ($item['lot_name'] ?? $item['active_ingredient'] ?? '—') }}</div>
                            @if (!empty($item['active_ingredient']) && $item['active_ingredient'] !== ($item['medicine_name'] ?? null))
                                <div class="mt-1 text-xs text-gray-500">{{ $item['active_ingredient'] }}</div>
                            @endif
                            @if (!empty($item['medicine_code']))
                                <div class="mt-1 text-xs text-gray-400">Mã thuốc: {{ $item['medicine_code'] }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ $item['concentration'] ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-700">
                            <div>{{ $item['route'] ?? '—' }}</div>
                            @if (!empty($item['dosage_form']))
                                <div class="mt-1 text-xs text-gray-500">{{ $item['dosage_form'] }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ $item['medicine_group'] ?? '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-gray-700">
                            {{ is_numeric($item['quantity'] ?? null) ? number_format((float) $item['quantity'], 0, ',', '.') : ($item['quantity'] ?? '—') }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-gray-700">
                            {{ is_numeric($item['price_plan'] ?? null) ? number_format((float) $item['price_plan'], 0, ',', '.') : ($item['price_plan'] ?? '—') }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums font-medium text-gray-900">
                            {{ is_numeric($item['lot_price'] ?? null) ? number_format((float) $item['lot_price'], 0, ',', '.') : ($item['lot_price'] ?? '—') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-5 py-10 text-center text-gray-500">Không có mặt hàng phù hợp với bộ lọc hiện tại.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-xs text-gray-500">
                Trang {{ $hsmtPage }} / {{ $hsmtTotalPages }} · {{ $hsmtPerPage }} dòng/trang
                @if (!empty($hsmt['investor_name']))
                    · Chủ đầu tư HSMT: <span class="font-medium text-gray-700">{{ $hsmt['investor_name'] }}</span>
                @endif
            </div>
            <div class="flex gap-2">
                <button type="button" wire:click="hsmtPreviousPage" @disabled($hsmtPage <= 1)
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40">
                    Trang trước
                </button>
                <button type="button" wire:click="hsmtNextPage" @disabled($hsmtPage >= $hsmtTotalPages)
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40">
                    Trang sau
                </button>
            </div>
        </div>
    @endif
</section>
