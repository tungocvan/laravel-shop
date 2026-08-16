@php($currentKqlcntSynced = isset($syncedKqlcntNotifyNos[$kqlcnt['notify_no'] ?? '']))

<div class="fixed inset-0 z-[120] flex items-center justify-center overflow-hidden bg-gray-950/70 p-3 sm:p-5"
     wire:click.self="closeKqlcnt">
    <div class="flex h-[calc(100dvh-1.5rem)] w-full max-w-7xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl sm:h-[calc(100dvh-2.5rem)]">
        <div class="flex shrink-0 items-start justify-between gap-4 border-b border-gray-100 bg-white px-5 py-4 sm:px-6">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Kết quả lựa chọn nhà thầu</p>
                <h3 class="mt-1 text-xl font-bold text-gray-900">{{ $kqlcnt['notify_no'] ?? '' }}</h3>
                <p class="mt-1 truncate text-sm text-gray-500">{{ $kqlcnt['bid_name'] ?? '—' }}</p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <button wire:click="syncKqlcnt" wire:loading.attr="disabled"
                        class="hidden rounded-lg px-4 py-2 text-sm font-semibold sm:inline-flex {{ $currentKqlcntSynced ? 'border border-emerald-200 bg-emerald-50 text-emerald-700' : 'bg-emerald-600 text-white hover:bg-emerald-700' }} disabled:opacity-50">
                    {{ $currentKqlcntSynced ? 'Đồng bộ lại KQLCNT' : 'Đồng bộ KQLCNT' }}
                </button>
                <button type="button" wire:click="closeKqlcnt"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 bg-white text-2xl leading-none text-gray-500 hover:bg-gray-50 hover:text-gray-900"
                        aria-label="Đóng modal">&times;</button>
            </div>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-5 py-5 sm:px-6">
            <div class="grid gap-4 md:grid-cols-5">
                <div class="rounded-xl border border-gray-200 p-4">
                    <div class="text-xs uppercase text-gray-500">Trạng thái</div>
                    <div class="mt-1 font-semibold text-gray-900">{{ ($kqlcnt['status'] ?? '') === 'PUB_KQLCNT' ? 'Đã công bố KQLCNT' : ($kqlcnt['status'] ?? '—') }}</div>
                </div>
                <div class="rounded-xl border border-gray-200 p-4 md:col-span-2">
                    <div class="text-xs uppercase text-gray-500">Chủ đầu tư</div>
                    <div class="mt-1 font-semibold text-gray-900">{{ $kqlcnt['investor_name'] ?? '—' }}</div>
                    @if (!empty($kqlcnt['investor_code']))
                        <div class="mt-1 text-xs text-gray-500">{{ $kqlcnt['investor_code'] }}</div>
                    @endif
                </div>
                <div class="rounded-xl border border-gray-200 p-4">
                    <div class="text-xs uppercase text-gray-500">Hợp đồng của nhà thầu</div>
                    <div class="mt-1 text-xl font-bold text-gray-900">{{ count($kqlcnt['contracts'] ?? []) }}</div>
                </div>
                <div class="rounded-xl border border-gray-200 p-4">
                    <div class="text-xs uppercase text-gray-500">Đơn vị trúng thầu</div>
                    <div class="mt-1 text-xl font-bold text-gray-900">{{ count($kqlcnt['all_winners'] ?? []) }}</div>
                </div>
            </div>

            <div class="mt-4 rounded-xl border {{ !empty($kqlcnt['current_contractor_won']) ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-amber-200 bg-amber-50 text-amber-800' }} px-4 py-3 text-sm">
                Nhà thầu đang xem: <strong>{{ $contractorName }}</strong>
                <span class="opacity-70">({{ $kqlcnt['contractor_code'] ?? '—' }})</span>
                <span class="ml-2 rounded-full bg-white/70 px-2 py-1 text-xs font-semibold">
                    {{ !empty($kqlcnt['current_contractor_won']) ? 'Có trong danh sách trúng thầu' : 'Chưa thấy trong danh sách trúng thầu' }}
                </span>
                @if ($currentKqlcntSynced)
                    <span class="ml-2 rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">KQLCNT đã đồng bộ</span>
                @endif
            </div>

            <section class="mt-6">
                <div class="mb-3">
                    <h4 class="text-base font-bold text-gray-900">Các đơn vị trúng thầu của TBMT</h4>
                    <p class="text-sm text-gray-500">Được đọc trực tiếp từ contractorPassList của API hợp đồng, không suy diễn từ danh mục HSMT.</p>
                </div>

                @if (!empty($kqlcnt['all_winners']))
                    <div class="grid gap-3 lg:grid-cols-2">
                        @foreach ($kqlcnt['all_winners'] as $winner)
                            <div class="rounded-xl border {{ ($winner['contractorCode'] ?? null) === ($kqlcnt['contractor_code'] ?? null) ? 'border-emerald-300 bg-emerald-50/60' : 'border-gray-200 bg-white' }} p-4">
                                <div class="font-semibold text-gray-900">{{ $winner['contractorName'] ?? '—' }}</div>
                                <div class="mt-1 text-xs text-gray-500">{{ $winner['contractorCode'] ?? '—' }}</div>
                                @if (!empty($winner['contractorAddress']))
                                    <div class="mt-2 text-sm text-gray-600">{{ $winner['contractorAddress'] }}</div>
                                @endif
                                @if (!empty($winner['contracts']))
                                    <div class="mt-2 text-xs text-gray-500">Hợp đồng: {{ implode(', ', $winner['contracts']) }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        API hợp đồng hiện chưa trả contractorPassList có dữ liệu cho TBMT này.
                    </div>
                @endif
            </section>

            <section class="mt-6">
                <div class="mb-3">
                    <h4 class="text-base font-bold text-gray-900">Đơn vị trúng thầu / Hợp đồng của nhà thầu đang xem</h4>
                    <p class="text-sm text-gray-500">Chỉ hiển thị hợp đồng có contractorPassList khớp đúng mã nhà thầu đang xem.</p>
                </div>
                <div class="space-y-3">
                    @forelse (($kqlcnt['contracts'] ?? []) as $contract)
                        @php($winner = $contract['contractorPassListParsed'][0] ?? [])
                        <div class="rounded-xl border border-gray-200 p-4">
                            <div class="grid gap-4 lg:grid-cols-3">
                                <div class="lg:col-span-2">
                                    <div class="text-xs uppercase text-gray-500">Đơn vị trúng thầu</div>
                                    <div class="mt-1 font-semibold text-gray-900">{{ $winner['contractorName'] ?? '—' }}</div>
                                    <div class="mt-1 text-xs text-gray-500">{{ $winner['contractorCode'] ?? '—' }}</div>
                                    @if (!empty($winner['contractorAddress']))
                                        <div class="mt-2 text-sm text-gray-600">{{ $winner['contractorAddress'] }}</div>
                                    @endif
                                </div>
                                <div>
                                    <div class="text-xs uppercase text-gray-500">Hợp đồng / TTK</div>
                                    <div class="mt-1 font-semibold text-gray-900">{{ $contract['contractNo'] ?? '—' }}</div>
                                    <div class="mt-1 text-sm text-gray-600">{{ $contract['contractName'] ?? '—' }}</div>
                                    <div class="mt-1 text-xs text-gray-500">{{ !empty($contract['contractSignDate']) ? \Illuminate\Support\Carbon::parse($contract['contractSignDate'])->format('d/m/Y') : '—' }}</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                            Chưa tìm thấy hợp đồng/KQLCNT có mã nhà thầu {{ $contractorCode }} trong dữ liệu nguồn hiện tại.
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="mt-6">
                <div>
                    <h4 class="text-base font-bold text-gray-900">Danh mục lô / thuốc của nhà thầu</h4>
                    <p class="mt-1 text-sm text-gray-500">Hệ thống chỉ hiển thị lô khi dữ liệu nguồn có khóa xác minh trực tiếp lô ↔ nhà thầu.</p>
                </div>
                @if (!empty($kqlcnt['verified_lots']))
                    <div class="mt-3 overflow-x-auto rounded-xl border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                            <tr><th class="px-4 py-3">Mã lô</th><th class="px-4 py-3">Tên lô / thuốc</th><th class="px-4 py-3">Số lượng</th><th class="px-4 py-3">Đơn giá / Giá</th></tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                            @foreach ($kqlcnt['verified_lots'] as $lot)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-indigo-700">{{ $lot['lotNo'] ?? $lot['lotCode'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-800">{{ $lot['lotName'] ?? $lot['tenThuoc'] ?? $lot['goodsName'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $lot['quantity'] ?? $lot['soLuong'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $lot['unitPrice'] ?? $lot['donGia'] ?? $lot['price'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="mt-3 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
                        Chưa xác định được danh mục lô thuộc nhà thầu này từ dữ liệu nguồn hiện có. Hệ thống không hiển thị toàn bộ lot của TBMT để tránh gán sai thuốc cho nhà thầu.
                    </div>
                @endif
            </section>

            @include('Muasamcong::livewire.partials.hsmt-catalogue')
        </div>

        <div class="flex shrink-0 items-center justify-between gap-3 border-t border-gray-100 bg-white px-5 py-3 sm:px-6">
            <div class="text-xs text-gray-500">Esc chưa được bắt sự kiện; dùng nút Đóng hoặc dấu ×.</div>
            <div class="flex items-center gap-2">
                <button wire:click="syncKqlcnt" wire:loading.attr="disabled"
                        class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 sm:hidden">
                    {{ $currentKqlcntSynced ? 'Đồng bộ lại' : 'Đồng bộ' }}
                </button>
                <button type="button" wire:click="closeKqlcnt"
                        class="rounded-lg bg-gray-900 px-5 py-2 text-sm font-semibold text-white hover:bg-gray-800">Đóng</button>
            </div>
        </div>
    </div>
</div>
