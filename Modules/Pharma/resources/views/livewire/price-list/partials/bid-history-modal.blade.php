@if($bidHistoryKey && isset($bidIntelligence[$bidHistoryKey]))
    @php($history = $bidIntelligence[$bidHistoryKey]['history'] ?? [])
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm" wire:click.self="closeBidHistory">
        <div class="w-full max-w-4xl overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b px-6 py-5">
                <div><p class="text-xs font-black uppercase tracking-[.18em] text-emerald-700">Bid Intelligence</p><h3 class="mt-1 text-xl font-bold text-slate-900">Lịch sử kết quả trúng thầu</h3><p class="mt-1 text-sm text-slate-500">Chọn kết quả dùng làm evidence cho bảng giá. Thao tác này không thay đổi các mức giá thương mại.</p></div>
                <button type="button" wire:click="closeBidHistory" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-bold">Đóng</button>
            </div>
            <div class="max-h-[65vh] overflow-auto p-5">
                <div class="space-y-3">
                    @forelse($history as $award)
                        @php($selected = (int)($selectedBidAwardIds[$bidHistoryKey] ?? 0) === (int)$award['id'])
                        <button type="button" wire:click="selectBidAward('{{ $bidHistoryKey }}', {{ $award['id'] }})" class="block w-full rounded-xl border p-4 text-left transition {{ $selected ? 'border-emerald-400 bg-emerald-50 ring-1 ring-emerald-300' : 'border-slate-200 hover:bg-slate-50' }}">
                            <div class="flex flex-wrap items-start justify-between gap-3"><div><div class="text-lg font-black text-slate-950">{{ number_format((float)$award['price'],0,',','.') }} ₫</div><div class="mt-1 text-sm font-semibold text-slate-700">{{ $award['contractor'] ?: 'Chưa có nhà thầu' }}</div></div><div class="flex items-center gap-2"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">{{ $award['source'] }}</span>@if($selected)<span class="rounded-full bg-emerald-600 px-2.5 py-1 text-xs font-bold text-white">Đang chọn</span>@endif</div></div>
                            <div class="mt-3 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4"><div><span class="text-slate-500">Số lượng</span><div class="font-bold">{{ $award['quantity'] !== null ? number_format((float)$award['quantity'],0,',','.') : '—' }}</div></div><div><span class="text-slate-500">Ngày</span><div class="font-bold">{{ $award['award_date'] ? \Illuminate\Support\Carbon::parse($award['award_date'])->format('d/m/Y') : '—' }}</div></div><div class="sm:col-span-2"><span class="text-slate-500">Quyết định</span><div class="font-bold">{{ $award['decision_number'] ?: '—' }}</div></div></div>
                        </button>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">Không có lịch sử kết quả trúng thầu.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endif
