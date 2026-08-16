@php($currentKqlcntSynced = isset($syncedKqlcntNotifyNos[$kqlcnt['notify_no'] ?? '']))

<div class="fixed inset-0 flex items-center justify-center overflow-hidden bg-gray-950/70 p-3 sm:p-5"
     style="z-index: 9999; position: fixed; inset: 0;"
     wire:click.self="closeKqlcnt">
    <div class="w-full max-w-7xl overflow-hidden rounded-2xl bg-white shadow-2xl"
         style="display:flex; flex-direction:column; height:calc(100vh - 24px); max-height:calc(100vh - 24px); min-height:0;">
        <div class="flex items-start justify-between gap-4 border-b border-gray-100 bg-white px-5 py-4 sm:px-6"
             style="flex:0 0 auto;">
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

        <div class="px-5 py-5 sm:px-6"
             style="min-height:0; flex:1 1 auto; overflow-y:auto; overscroll-behavior:contain;">
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

            <section class="mt-6"
                     x-data="{
                        q: '',
                        limit: 20,
                        winners: @js($kqlcnt['all_winners'] ?? []),
                        contractorCode: @js($kqlcnt['contractor_code'] ?? null),
                        filtered() {
                            const term = this.q.trim().toLocaleLowerCase('vi');
                            if (!term) return this.winners;
                            return this.winners.filter((winner) => {
                                const haystack = [
                                    winner.contractorName || '',
                                    winner.contractorCode || '',
                                    winner.contractorAddress || '',
                                    ...(winner.contracts || []),
                                ].join(' ').toLocaleLowerCase('vi');
                                return haystack.includes(term);
                            });
                        }
                     }"
                     x-effect="q; limit = 20">
                <div class="mb-3 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h4 class="text-base font-bold text-gray-900">Các đơn vị trúng thầu của TBMT</h4>
                            <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                                {{ number_format(count($kqlcnt['all_winners'] ?? []), 0, ',', '.') }} đơn vị
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">Được tổng hợp từ dữ liệu hợp đồng KQLCNT; danh sách được tìm kiếm và giới hạn hiển thị để Modal luôn nhẹ.</p>
                    </div>

                    @if (!empty($kqlcnt['all_winners']))
                        <div class="w-full lg:w-96">
                            <x-search x-model.debounce.250ms="q" placeholder="Tìm tên, mã nhà thầu, hợp đồng..." />
                        </div>
                    @endif
                </div>

                @if (!empty($kqlcnt['all_winners']))
                    <div class="mb-3 flex items-center justify-between text-xs text-gray-500">
                        <div>
                            Tìm thấy <strong class="text-gray-700" x-text="filtered().length"></strong> / {{ number_format(count($kqlcnt['all_winners']), 0, ',', '.') }} đơn vị
                        </div>
                        <div>Hiển thị tối đa <strong class="text-gray-700" x-text="Math.min(limit, filtered().length)"></strong> kết quả</div>
                    </div>

                    <div class="grid gap-3 lg:grid-cols-2">
                        <template x-for="winner in filtered().slice(0, limit)" :key="(winner.contractorCode || winner.contractorName) + JSON.stringify(winner.contracts || [])">
                            <div class="rounded-xl border p-4"
                                 :class="winner.contractorCode === contractorCode ? 'border-emerald-300 bg-emerald-50/60' : 'border-gray-200 bg-white'">
                                <div class="font-semibold text-gray-900" x-text="winner.contractorName || '—'"></div>
                                <div class="mt-1 text-xs text-gray-500" x-text="winner.contractorCode || '—'"></div>
                                <div class="mt-2 text-sm text-gray-600" x-show="winner.contractorAddress" x-text="winner.contractorAddress"></div>
                                <div class="mt-2 text-xs text-gray-500" x-show="(winner.contracts || []).length">
                                    Hợp đồng: <span x-text="(winner.contracts || []).join(', ')"></span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="mt-4 flex flex-col items-center justify-center gap-2" x-show="filtered().length > limit">
                        <button type="button"
                                @click="limit += 20"
                                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Xem thêm 20 đơn vị
                        </button>
                        <div class="text-xs text-gray-400">
                            Đang hiển thị <span x-text="Math.min(limit, filtered().length)"></span> / <span x-text="filtered().length"></span>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-5 text-center text-sm text-gray-500"
                         x-show="filtered().length === 0">
                        Không tìm thấy đơn vị trúng thầu phù hợp với từ khóa.
                    </div>
                @else
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        API hợp đồng hiện chưa trả dữ liệu đơn vị trúng thầu cho TBMT này.
                    </div>
                @endif
            </section>

            <section class="mt-6">
                <div class="mb-3">
                    <h4 class="text-base font-bold text-gray-900">Đơn vị trúng thầu / Hợp đồng của nhà thầu đang xem</h4>
                    <p class="text-sm text-gray-500">Chỉ hiển thị hợp đồng có mã nhà thầu khớp đúng nhà thầu đang xem.</p>
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

        <div class="flex items-center justify-between gap-3 border-t border-gray-100 bg-white px-5 py-3 sm:px-6"
             style="flex:0 0 auto; min-height:58px; position:relative; z-index:2;">
            <div class="text-xs text-gray-500">Dùng nút Đóng hoặc dấu × để thoát cửa sổ.</div>
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
