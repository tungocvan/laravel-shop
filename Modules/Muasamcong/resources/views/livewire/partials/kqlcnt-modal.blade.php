@php($currentKqlcntSynced = isset($syncedKqlcntNotifyNos[$kqlcnt['notify_no'] ?? '']))

<div class="fixed inset-0 flex items-center justify-center overflow-hidden bg-gray-950/70 p-3 sm:p-5"
     style="z-index: 9999; position: fixed; inset: 0;"
     wire:click.self="closeKqlcnt">
    <div class="w-full max-w-7xl overflow-hidden rounded-2xl bg-white shadow-2xl"
         style="display:flex; flex-direction:column; height:calc(100vh - 24px); max-height:calc(100vh - 24px); min-height:0;">
        <div class="flex items-start justify-between gap-4 border-b border-gray-100 bg-white px-5 py-4 sm:px-6" style="flex:0 0 auto;">
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

        <div class="px-5 py-5 sm:px-6" style="min-height:0; flex:1 1 auto; overflow-y:auto; overscroll-behavior:contain;">
            <div class="grid gap-4 md:grid-cols-4">
                <div class="rounded-xl border border-gray-200 p-4">
                    <div class="text-xs uppercase text-gray-500">Trạng thái</div>
                    <div class="mt-1 font-semibold text-gray-900">{{ ($kqlcnt['status'] ?? '') === 'PUB_KQLCNT' ? 'Đã công bố KQLCNT' : ($kqlcnt['status'] ?? '—') }}</div>
                </div>
                <div class="rounded-xl border border-gray-200 p-4 md:col-span-2">
                    <div class="text-xs uppercase text-gray-500">Chủ đầu tư</div>
                    <div class="mt-1 font-semibold text-gray-900">{{ $kqlcnt['investor_name'] ?? '—' }}</div>
                    @if (!empty($kqlcnt['investor_code']))<div class="mt-1 text-xs text-gray-500">{{ $kqlcnt['investor_code'] }}</div>@endif
                </div>
                <div class="rounded-xl border border-gray-200 p-4">
                    <div class="text-xs uppercase text-gray-500">Hợp đồng khớp nhà thầu</div>
                    <div class="mt-1 text-xl font-bold text-gray-900">{{ count($kqlcnt['contracts'] ?? []) }}</div>
                </div>
            </div>

            <div class="mt-4 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-800">
                <div class="flex flex-wrap items-center gap-2">
                    <span>Nhà thầu đang xem:</span>
                    <strong>{{ $contractorName }}</strong>
                    <span class="opacity-70">({{ $kqlcnt['contractor_code'] ?? $contractorCode }})</span>
                    @if ($currentKqlcntSynced)<span class="rounded-full bg-white px-2 py-1 text-xs font-semibold text-emerald-700">KQLCNT đã đồng bộ</span>@endif
                </div>
                <p class="mt-1 text-xs text-indigo-700">Danh mục Smart Pricing bên dưới sẽ chỉ giữ các thuốc có mã TBMT đúng gói đang xem và đơn vị trúng thầu khớp chính nhà thầu này.</p>
            </div>

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
                                    @if (!empty($winner['contractorAddress']))<div class="mt-2 text-sm text-gray-600">{{ $winner['contractorAddress'] }}</div>@endif
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
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">Nguồn hợp đồng KQLCNT chưa có bản ghi khớp mã nhà thầu {{ $contractorCode }}. Danh mục thuốc vẫn được đối chiếu độc lập bằng Smart Pricing.</div>
                    @endforelse
                </div>
            </section>

            @include('Muasamcong::livewire.partials.hsmt-catalogue')
        </div>

        <div class="flex items-center justify-between gap-3 border-t border-gray-100 bg-white px-5 py-3 sm:px-6" style="flex:0 0 auto; min-height:58px; position:relative; z-index:2;">
            <div class="text-xs text-gray-500">Dùng nút Đóng hoặc dấu × để thoát cửa sổ.</div>
            <div class="flex items-center gap-2">
                <button wire:click="syncKqlcnt" wire:loading.attr="disabled" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 sm:hidden">{{ $currentKqlcntSynced ? 'Đồng bộ lại' : 'Đồng bộ' }}</button>
                <button type="button" wire:click="closeKqlcnt" class="rounded-lg bg-gray-900 px-5 py-2 text-sm font-semibold text-white hover:bg-gray-800">Đóng</button>
            </div>
        </div>
    </div>
</div>