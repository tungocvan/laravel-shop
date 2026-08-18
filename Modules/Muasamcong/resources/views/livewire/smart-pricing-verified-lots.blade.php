<section wire:init="syncIfNeeded" class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50/50 p-4">
    <div class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h4 class="text-base font-bold text-gray-900">Danh mục tự xác minh từ Smart Pricing</h4>
                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">TBMT ↔ Thuốc ↔ Nhà thầu</span>
                @if ($total > 0)
                    <span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-emerald-700 shadow-sm">{{ number_format($total, 0, ',', '.') }} thuốc</span>
                @endif
            </div>
            <p class="mt-1 max-w-4xl text-sm text-gray-600">
                Nguồn này chỉ nhận những record Smart Pricing có <code>maTbmt</code> đúng TBMT đang xem và <code>winningCode</code> chứa chính mã nhà thầu hiện tại. Không ghép winner theo vị trí giữa các mảng khác nhau.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @if ($total > 0)
                <a href="{{ route('muasamcong.contractors.manual-lots.show', ['contractorCode' => $contractorCode, 'notifyNo' => $notifyNo]) }}"
                   target="_blank"
                   class="inline-flex items-center justify-center rounded-xl border border-emerald-300 bg-white px-4 py-2.5 text-sm font-semibold text-emerald-700 shadow-sm hover:bg-emerald-100">
                    Xem danh mục
                </a>
                <a href="{{ route('muasamcong.contractors.manual-lots.download', ['contractorCode' => $contractorCode, 'notifyNo' => $notifyNo]) }}"
                   class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                    Tải Excel
                </a>
            @endif
            <button type="button" wire:click="sync" wire:loading.attr="disabled" wire:target="sync,syncIfNeeded"
                    class="inline-flex items-center justify-center rounded-xl border border-emerald-300 bg-white px-4 py-2.5 text-sm font-semibold text-emerald-700 shadow-sm hover:bg-emerald-100 disabled:opacity-50">
                <span wire:loading.remove wire:target="sync,syncIfNeeded">Đồng bộ Smart Pricing</span>
                <span wire:loading wire:target="sync,syncIfNeeded">Đang quét toàn bộ trang...</span>
            </button>
        </div>
    </div>

    @if ($error)
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $error }}</div>
    @endif
    @if ($notice)
        <div class="mt-4 rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm text-emerald-800">{{ $notice }}</div>
    @endif

    <div wire:loading.flex wire:target="sync,syncIfNeeded" class="mt-4 items-center gap-3 rounded-xl border border-emerald-200 bg-white px-4 py-4 text-sm text-emerald-800">
        <span class="h-4 w-4 animate-spin rounded-full border-2 border-emerald-200 border-t-emerald-600"></span>
        Đang duyệt Smart Pricing theo từng trang để xác minh thuốc của {{ $contractorName ?: $contractorCode }}...
    </div>

    @if ($total > 0)
        <div class="mt-4 grid gap-3 md:grid-cols-3">
            <div class="rounded-xl border border-emerald-200 bg-white p-3">
                <div class="text-xs font-semibold uppercase text-gray-500">Thuốc xác minh</div>
                <div class="mt-1 text-xl font-bold text-emerald-700">{{ number_format($total, 0, ',', '.') }}</div>
            </div>
            <div class="rounded-xl border border-emerald-200 bg-white p-3">
                <div class="text-xs font-semibold uppercase text-gray-500">Tổng số lượng</div>
                <div class="mt-1 text-xl font-bold text-gray-900">{{ number_format($quantity, 0, ',', '.') }}</div>
            </div>
            <div class="rounded-xl border border-emerald-200 bg-white p-3">
                <div class="text-xs font-semibold uppercase text-gray-500">Giá trị theo đơn giá trúng</div>
                <div class="mt-1 text-xl font-bold text-gray-900">{{ number_format($amount, 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="mt-4 overflow-x-auto rounded-xl border border-gray-200 bg-white">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">Thuốc</th>
                    <th class="px-4 py-3">Hoạt chất</th>
                    <th class="px-4 py-3">Nhà thầu trúng</th>
                    <th class="px-4 py-3 text-right">Số lượng</th>
                    <th class="px-4 py-3 text-right">Đơn giá trúng</th>
                    <th class="px-4 py-3 text-right">Thành tiền</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                @foreach ($rows as $row)
                    @php($raw = is_array($row->raw_payload) ? $row->raw_payload : [])
                    @php($quantityValue = is_numeric($row->quantity) ? (float) $row->quantity : null)
                    @php($unitPrice = is_numeric($row->lot_price) ? (float) $row->lot_price : null)
                    <tr class="align-top hover:bg-emerald-50/40">
                        <td class="min-w-64 px-4 py-3">
                            <div class="font-semibold text-gray-900">{{ $row->medicine_name ?: $row->lot_name ?: '—' }}</div>
                            @if (!empty($raw['medicine_group']))<div class="mt-1 text-xs text-gray-500">Nhóm: {{ $raw['medicine_group'] }}</div>@endif
                            @if (!empty($raw['manufacturer']))<div class="mt-1 text-xs text-gray-500">NSX: {{ $raw['manufacturer'] }}</div>@endif
                        </td>
                        <td class="min-w-56 px-4 py-3 text-gray-700">
                            {{ $row->active_ingredient ?: '—' }}
                            @if (!empty($raw['concentration']))<div class="mt-1 text-xs text-gray-500">{{ $raw['concentration'] }}</div>@endif
                            @if (!empty($raw['route']))<div class="mt-1 text-xs text-gray-500">{{ $raw['route'] }}</div>@endif
                        </td>
                        <td class="min-w-72 px-4 py-3">
                            <div class="font-medium text-emerald-800">{{ $raw['contractor_name'] ?? $contractorName ?: $contractorCode }}</div>
                            <div class="mt-1 text-xs text-gray-500">{{ $contractorCode }}</div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ $quantityValue !== null ? number_format($quantityValue, 0, ',', '.') : '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ $unitPrice !== null ? number_format($unitPrice, 0, ',', '.') : '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums">{{ $row->plan_amount !== null ? number_format((float) $row->plan_amount, 0, ',', '.') : '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        @if ($total > 20)
            <div class="mt-3 text-xs text-gray-500">Đang xem trước 20/{{ number_format($total, 0, ',', '.') }} thuốc. Dùng <strong>Xem danh mục</strong> hoặc <strong>Tải Excel</strong> để xem toàn bộ dữ liệu đã lưu.</div>
        @endif
    @elseif ($checked && !$error)
        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Chưa có thuốc Smart Pricing nào có <code>winningCode</code> khớp nhà thầu này. Bạn vẫn có thể chọn thủ công từ HSMT bên dưới.
        </div>
    @endif
</section>
