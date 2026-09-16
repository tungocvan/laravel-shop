@php($bid = $bidIntelligence[$row->key] ?? ['has_award' => false])
@if($bid['has_award'] ?? false)
    @php($award = $bid['selected'] ?? null)
    <div class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50/60 p-3 text-xs">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <span class="inline-flex items-center gap-1.5 font-black text-emerald-800"><span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-600 text-[10px] text-white">✓</span> CÓ KẾT QUẢ TRÚNG THẦU</span>
            <span class="rounded-full bg-white px-2 py-1 font-bold text-slate-600">{{ $bid['count'] ?? 1 }} kết quả</span>
        </div>
        @if($award)
            <div class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 sm:grid-cols-3 xl:grid-cols-6">
                <div><div class="text-slate-500">Đơn giá</div><div class="font-black text-slate-900">{{ number_format((float) $award['price'], 0, ',', '.') }} ₫</div></div>
                <div><div class="text-slate-500">Số lượng</div><div class="font-bold text-slate-900">{{ $award['quantity'] !== null ? number_format((float) $award['quantity'], 0, ',', '.') : '—' }}</div></div>
                <div><div class="text-slate-500">Ngày</div><div class="font-bold text-slate-900">{{ $award['award_date'] ? \Illuminate\Support\Carbon::parse($award['award_date'])->format('d/m/Y') : '—' }}</div></div>
                <div><div class="text-slate-500">Quyết định</div><div class="font-bold text-slate-900">{{ $award['decision_number'] ?: '—' }}</div></div>
                <div><div class="text-slate-500">Nhà thầu</div><div class="font-bold text-slate-900">{{ $award['contractor'] ?: '—' }}</div></div>
                <div><div class="text-slate-500">Nguồn</div><div class="font-bold text-slate-900">{{ $award['source'] }}</div></div>
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
                <button type="button" wire:click="showBidHistory('{{ $row->key }}')" class="rounded-lg border border-emerald-300 bg-white px-3 py-2 font-bold text-emerald-800">Xem lịch sử / Chọn kết quả khác</button>
                <span class="self-center text-slate-500">Chỉ dùng làm tham chiếu, không tự thay đổi giá bán.</span>
            </div>
        @endif
    </div>
@else
    <div class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs">
        <div><div class="font-black text-amber-900">CHƯA CÓ KẾT QUẢ TRÚNG THẦU</div><div class="mt-0.5 text-amber-800">Chưa có award canonical cho SKU/quy cách này.</div></div>
        <a href="{{ route('admin.pharma.drug-bid-awards.create') }}" target="_blank" class="rounded-lg border border-amber-300 bg-white px-3 py-2 font-bold text-amber-900">＋ Cập nhật thủ công ↗</a>
    </div>
@endif
