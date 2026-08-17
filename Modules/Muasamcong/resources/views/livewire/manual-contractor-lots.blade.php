<section class="mt-6 rounded-2xl border border-violet-200 bg-violet-50/50 p-4">
    <div class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h4 class="text-base font-bold text-gray-900">Gán lô / thuốc thủ công cho nhà thầu</h4>
                <span class="rounded-full bg-violet-100 px-2.5 py-1 text-xs font-semibold text-violet-700">Người dùng xác nhận</span>
            </div>
            <p class="mt-1 max-w-4xl text-sm text-gray-600">
                Dùng khi dữ liệu KQLCNT chưa có khóa xác minh trực tiếp lô ↔ nhà thầu. Các lô được chọn ở đây là dữ liệu nghiệp vụ do người dùng xác nhận, không được coi là lô trúng thầu do API tự xác minh.
            </p>
        </div>
        @if ($hasSnapshot)
            <div class="flex flex-wrap gap-2">
                <button type="button" wire:click="clearSelection"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Bỏ chọn tất cả
                </button>
                <button type="button" wire:click="saveSelections" wire:loading.attr="disabled" wire:target="saveSelections"
                        class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-violet-700 disabled:opacity-50">
                    <span wire:loading.remove wire:target="saveSelections">Lưu {{ count($selected) }} lô đã chọn</span>
                    <span wire:loading wire:target="saveSelections">Đang lưu...</span>
                </button>
            </div>
        @endif
    </div>

    @if ($error)
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $error }}</div>
    @endif
    @if ($notice)
        <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ $notice }}</div>
    @endif

    @if (!$hasSnapshot)
        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            Chưa có snapshot HSMT của {{ $notifyNo }}. Hãy dùng nút <strong>Tải danh mục HSMT</strong> ở phần Danh mục mời thầu, sau đó mở lại KQLCNT để chọn lô.
        </div>
    @else
        <div class="mt-4 grid gap-3 lg:grid-cols-4">
            <div class="rounded-xl border border-violet-200 bg-white p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Lô đã chọn</div>
                <div class="mt-1 text-2xl font-bold text-violet-700">{{ number_format($totals['count'], 0, ',', '.') }}</div>
            </div>
            <div class="rounded-xl border border-violet-200 bg-white p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tổng số lượng</div>
                <div class="mt-1 text-xl font-bold text-gray-900">{{ number_format($totals['quantity'], 0, ',', '.') }}</div>
            </div>
            <div class="rounded-xl border border-violet-200 bg-white p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tổng KH (SL × Giá KH)</div>
                <div class="mt-1 text-xl font-bold text-gray-900">{{ number_format($totals['plan_amount'], 0, ',', '.') }}</div>
            </div>
            <div class="rounded-xl border border-violet-200 bg-white p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tổng giá lô</div>
                <div class="mt-1 text-xl font-bold text-gray-900">{{ number_format($totals['lot_price'], 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="mt-4">
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Tìm lô / thuốc để gán</label>
            <input type="text" wire:model.live.debounce.300ms="search"
                   placeholder="Ví dụ: Docusate natri, hoạt chất, mã lô, mã thuốc..."
                   class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-100">
            <div class="mt-1 text-xs text-gray-500">Tìm thấy {{ number_format($filteredTotal, 0, ',', '.') }} dòng trong snapshot HSMT.</div>
        </div>

        <div class="mt-4 overflow-x-auto rounded-xl border border-gray-200 bg-white">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="w-12 px-4 py-3"></th>
                    <th class="px-4 py-3">Mã lô</th>
                    <th class="px-4 py-3">Tên lô / thuốc</th>
                    <th class="px-4 py-3 text-right">Số lượng</th>
                    <th class="px-4 py-3 text-right">Giá KH</th>
                    <th class="px-4 py-3 text-right">SL × Giá KH</th>
                    <th class="px-4 py-3 text-right">Giá lô</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                @forelse ($items as $item)
                    @php($quantity = is_numeric($item['quantity'] ?? null) ? (float) $item['quantity'] : null)
                    @php($pricePlan = is_numeric($item['price_plan'] ?? null) ? (float) $item['price_plan'] : null)
                    <tr class="align-top hover:bg-violet-50/40">
                        <td class="px-4 py-3">
                            <input type="checkbox" wire:model.live="selected" value="{{ $item['_lot_key'] }}"
                                   class="rounded border-gray-300 text-violet-600 focus:ring-violet-500">
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 font-semibold text-indigo-700">{{ $item['lot_no'] ?? '—' }}</td>
                        <td class="min-w-72 px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $item['lot_name'] ?: ($item['medicine_name'] ?? $item['active_ingredient'] ?? '—') }}</div>
                            @if (!empty($item['medicine_name']) && $item['medicine_name'] !== ($item['lot_name'] ?? null))
                                <div class="mt-1 text-xs text-gray-500">{{ $item['medicine_name'] }}</div>
                            @endif
                            @if (!empty($item['active_ingredient']))
                                <div class="mt-1 text-xs text-gray-400">{{ $item['active_ingredient'] }}</div>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ $quantity !== null ? number_format($quantity, 0, ',', '.') : '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ $pricePlan !== null ? number_format($pricePlan, 0, ',', '.') : '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums font-medium text-gray-900">{{ $quantity !== null && $pricePlan !== null ? number_format($quantity * $pricePlan, 0, ',', '.') : '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ is_numeric($item['lot_price'] ?? null) ? number_format((float) $item['lot_price'], 0, ',', '.') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-10 text-center text-gray-500">Không tìm thấy lô phù hợp.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($totalPages > 1)
            <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-xs text-gray-500">Trang {{ $page }} / {{ $totalPages }} · lựa chọn được giữ khi chuyển trang.</div>
                <div class="flex gap-2">
                    <button type="button" wire:click="previousPage" @disabled($page <= 1)
                            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 disabled:opacity-40">← Trước</button>
                    <button type="button" wire:click="nextPage" @disabled($page >= $totalPages)
                            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 disabled:opacity-40">Sau →</button>
                </div>
            </div>
        @endif

        <div class="mt-4 rounded-xl border border-violet-200 bg-white px-4 py-3 text-xs text-gray-600">
            <strong>Lưu ý:</strong> các tổng ở trên là tổng theo các lô do người dùng xác nhận từ HSMT. Đây không phải “tổng giá trị trúng thầu” nếu dữ liệu KQLCNT chưa xác minh trực tiếp lot ↔ nhà thầu.
        </div>
    @endif
</section>
