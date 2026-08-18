<section class="mt-6 rounded-2xl border border-violet-200 bg-violet-50/50 p-4">
    <div class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h4 class="text-base font-bold text-gray-900">Chọn lô / thuốc cho nhà thầu</h4>
                <span class="rounded-full bg-violet-100 px-2.5 py-1 text-xs font-semibold text-violet-700">Người dùng xác nhận</span>
            </div>
            <p class="mt-1 max-w-4xl text-sm text-gray-600">Tick trực tiếp các dòng HSMT cần gán. Lựa chọn được giữ khi tìm kiếm, lọc nhóm hoặc chuyển trang và được tách biệt với lô do API xác minh.</p>
        </div>
        @if ($hasSnapshot)
            <div class="flex flex-wrap gap-2">
                <button type="button" wire:click="clearSelection" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Bỏ chọn tất cả</button>
                <button type="button" wire:click="saveSelections" wire:loading.attr="disabled" wire:target="saveSelections" class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-violet-700 disabled:opacity-50">
                    <span wire:loading.remove wire:target="saveSelections">Lưu {{ count($selected) }} lô đã chọn</span>
                    <span wire:loading wire:target="saveSelections">Đang lưu...</span>
                </button>
            </div>
        @endif
    </div>

    @if ($error)<div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $error }}</div>@endif
    @if ($notice)<div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ $notice }}</div>@endif

    @if (($savedSummary['count'] ?? 0) > 0)
        <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h5 class="font-bold text-emerald-900">Danh mục lô / thuốc của nhà thầu đã lưu</h5>
                        <span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-emerald-700 shadow-sm">{{ number_format($savedSummary['count'], 0, ',', '.') }} lô</span>
                    </div>
                    <p class="mt-1 text-sm text-emerald-800">Danh mục đã lưu gồm dữ liệu tự xác minh và các lô do người dùng xác nhận. Có thể mở để xem toàn bộ hoặc tải Excel đầy đủ về máy.</p>
                    <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-emerald-800">
                        <span>Tổng số lượng: <strong>{{ number_format($savedSummary['quantity'], 0, ',', '.') }}</strong></span>
                        <span>Tổng KH / Thành tiền: <strong>{{ number_format($savedSummary['plan_amount'], 0, ',', '.') }}</strong></span>
                        <span>Tổng đơn giá / Giá lô: <strong>{{ number_format($savedSummary['lot_price'], 0, ',', '.') }}</strong></span>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('muasamcong.contractors.manual-lots.show', ['contractorCode' => $contractorCode, 'notifyNo' => $notifyNo]) }}"
                       target="_blank"
                       class="inline-flex items-center justify-center rounded-xl border border-emerald-300 bg-white px-4 py-2.5 text-sm font-semibold text-emerald-700 shadow-sm hover:bg-emerald-100">
                        Xem danh mục
                    </a>
                    <a href="{{ route('muasamcong.contractors.manual-lots.download', ['contractorCode' => $contractorCode, 'notifyNo' => $notifyNo]) }}"
                       class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                        Tải Excel đầy đủ
                    </a>
                </div>
            </div>
        </div>
    @endif

    @if (!$hasSnapshot)
        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">Chưa có snapshot HSMT của {{ $notifyNo }}. Hệ thống sẽ tự kiểm tra dữ liệu đã lưu trên server trước khi gọi API.</div>
    @else
        <div class="mt-4 grid gap-3 lg:grid-cols-4">
            <div class="rounded-xl border border-violet-200 bg-white p-3"><div class="text-xs font-semibold uppercase text-gray-500">Lô đã chọn</div><div class="mt-1 text-xl font-bold text-violet-700">{{ number_format($totals['count'], 0, ',', '.') }}</div></div>
            <div class="rounded-xl border border-violet-200 bg-white p-3"><div class="text-xs font-semibold uppercase text-gray-500">Tổng số lượng</div><div class="mt-1 text-xl font-bold text-gray-900">{{ number_format($totals['quantity'], 0, ',', '.') }}</div></div>
            <div class="rounded-xl border border-violet-200 bg-white p-3"><div class="text-xs font-semibold uppercase text-gray-500">Tổng KH (SL × Giá KH)</div><div class="mt-1 text-xl font-bold text-gray-900">{{ number_format($totals['plan_amount'], 0, ',', '.') }}</div></div>
            <div class="rounded-xl border border-violet-200 bg-white p-3"><div class="text-xs font-semibold uppercase text-gray-500">Tổng giá lô</div><div class="mt-1 text-xl font-bold text-gray-900">{{ number_format($totals['lot_price'], 0, ',', '.') }}</div></div>
        </div>

        <div class="mt-4 grid gap-3 lg:grid-cols-[minmax(0,1fr)_220px_auto] lg:items-end">
            <div wire:key="manual-lot-search-{{ $notifyNo }}-{{ $contractorCode }}">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600">Tìm trong danh mục</label>
                <input type="text"
                       wire:model.live.debounce.500ms="search"
                       x-on:paste.stop
                       x-on:keydown.stop
                       autocomplete="off"
                       placeholder="Ví dụ: Docusate natri, hoạt chất, mã lô, mã thuốc..."
                       class="w-full rounded-xl border border-gray-400 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-violet-500 focus:ring-2 focus:ring-violet-200">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600">Nhóm thuốc</label>
                <select wire:model.live="group" class="w-full rounded-xl border border-gray-400 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm outline-none focus:border-violet-500 focus:ring-2 focus:ring-violet-200">
                    <option value="">Tất cả nhóm</option>
                    @foreach ($groups as $groupName)<option value="{{ $groupName }}">{{ $groupName }}</option>@endforeach
                </select>
            </div>
            <div class="text-sm text-gray-600 lg:pb-2">Tìm thấy <strong class="text-gray-900">{{ number_format($filteredTotal, 0, ',', '.') }}</strong> dòng</div>
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-3 rounded-xl border border-violet-200 bg-white px-4 py-3">
            <button type="button" wire:click="selectCurrentPage" class="text-sm font-semibold text-violet-700 hover:text-violet-900">☑ Chọn tất cả trang này</button>
            <span class="text-xs text-gray-400">|</span>
            <span class="text-sm text-gray-600">Đã chọn <strong class="text-violet-700">{{ count($selected) }}</strong> lô trên các trang</span>
        </div>

        <div class="mt-3 overflow-x-auto rounded-xl border border-gray-200 bg-white">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="w-12 px-4 py-3 text-center">Chọn</th><th class="px-4 py-3">Mã lô</th><th class="px-4 py-3">Hoạt chất / Thuốc</th><th class="px-4 py-3">Nồng độ</th><th class="px-4 py-3">Đường dùng</th><th class="px-4 py-3">Nhóm</th><th class="px-4 py-3 text-right">Số lượng</th><th class="px-4 py-3 text-right">Giá KH</th><th class="px-4 py-3 text-right">SL × Giá KH</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                @forelse ($items as $item)
                    @php($quantity = is_numeric($item['quantity'] ?? null) ? (float) $item['quantity'] : null)
                    @php($pricePlan = is_numeric($item['price_plan'] ?? null) ? (float) $item['price_plan'] : null)
                    <tr wire:key="manual-lot-row-{{ md5($item['_lot_key']) }}" class="align-top hover:bg-violet-50/50">
                        <td class="px-4 py-3 text-center"><input wire:key="manual-lot-checkbox-{{ md5($item['_lot_key']) }}" type="checkbox" wire:model.live="selected" value="{{ $item['_lot_key'] }}" class="h-4 w-4 rounded border-gray-400 text-violet-600 focus:ring-violet-500"></td>
                        <td class="whitespace-nowrap px-4 py-3 font-semibold text-indigo-700">{{ $item['lot_no'] ?? '—' }}</td>
                        <td class="min-w-64 px-4 py-3"><div class="font-medium text-gray-900">{{ $item['medicine_name'] ?: ($item['lot_name'] ?? $item['active_ingredient'] ?? '—') }}</div>@if (!empty($item['active_ingredient']) && $item['active_ingredient'] !== ($item['medicine_name'] ?? null))<div class="mt-1 text-xs text-gray-500">{{ $item['active_ingredient'] }}</div>@endif @if (!empty($item['medicine_code']))<div class="mt-1 text-xs text-gray-400">Mã thuốc: {{ $item['medicine_code'] }}</div>@endif</td>
                        <td class="px-4 py-3 text-gray-700">{{ $item['concentration'] ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $item['route'] ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $item['medicine_group'] ?? '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ $quantity !== null ? number_format($quantity, 0, ',', '.') : '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ $pricePlan !== null ? number_format($pricePlan, 0, ',', '.') : '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right font-medium tabular-nums">{{ $quantity !== null && $pricePlan !== null ? number_format($quantity * $pricePlan, 0, ',', '.') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-5 py-10 text-center text-gray-500">Không tìm thấy lô phù hợp.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-xs text-gray-500">Trang {{ $page }} / {{ $totalPages }} · {{ $perPage }} dòng/trang · lựa chọn được giữ xuyên trang.</div>
            <div class="flex gap-2"><button type="button" wire:click="previousPage" @disabled($page <= 1) class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 disabled:opacity-40">← Trước</button><button type="button" wire:click="nextPage" @disabled($page >= $totalPages) class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 disabled:opacity-40">Sau →</button></div>
        </div>

        <div class="mt-4 rounded-xl border border-violet-200 bg-white px-4 py-3 text-xs text-gray-600"><strong>Lưu ý:</strong> tổng phía trên là tổng theo các lô do người dùng xác nhận từ HSMT, không phải tổng giá trị trúng thầu do API xác minh.</div>
    @endif
</section>
