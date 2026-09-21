@php
    $currentPage = $products->currentPage();
    $lastPage = $products->lastPage();
    $fmtQty = fn ($value) => rtrim(rtrim(number_format((float) $value, 4, ',', '.'), '0'), ',');
@endphp

<div class="space-y-6">
    <header class="flex flex-col gap-3 border-b border-slate-200 pb-5 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Pharma · Kết quả trúng thầu</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-950 sm:text-3xl">Sản phẩm trúng thầu</h1>
            <p class="mt-2 text-sm text-slate-600">Chọn từng sản phẩm để phân bổ số lượng cho bệnh viện/đơn vị nhận. Chủ đầu tư TBMT chỉ là dữ liệu procurement.</p>
        </div>
        <div class="rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-3">
            <p class="text-xs font-semibold uppercase text-indigo-600">Mã TBMT</p>
            <p class="mt-1 font-mono text-base font-bold text-indigo-950">{{ $result->bidding_notice_code ?: '—' }}</p>
        </div>
    </header>

    <section class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-2 xl:grid-cols-4">
        <div><p class="text-xs font-semibold uppercase text-slate-500">Chủ đầu tư</p><p class="mt-1 font-semibold text-slate-950">{{ $result->investor_name ?: '—' }}</p><p class="mt-1 text-xs text-slate-500">{{ $result->investor_code ?: '—' }}</p></div>
        <div><p class="text-xs font-semibold uppercase text-slate-500">Quyết định</p><p class="mt-1 font-semibold text-slate-950">{{ $result->decision_number ?: '—' }}</p><p class="mt-1 text-xs text-slate-500">{{ $result->decision_date?->format('d/m/Y') ?: '—' }}</p></div>
        <div><p class="text-xs font-semibold uppercase text-slate-500">Nhà thầu</p><p class="mt-1 font-semibold text-slate-950">{{ $result->winning_company_name ?: '—' }}</p><p class="mt-1 text-xs text-slate-500">Mỗi sản phẩm vẫn giữ nhà thầu gốc nếu TBMT có nhiều nhà thầu.</p></div>
        <div><p class="text-xs font-semibold uppercase text-slate-500">Số sản phẩm</p><p class="mt-1 text-2xl font-bold text-slate-950">{{ number_format($products->total()) }}</p><p class="mt-1 text-xs text-slate-500">Theo mã TBMT hiện tại</p></div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_180px]">
            <label class="block text-sm font-medium text-slate-700">Tìm sản phẩm<input type="search" wire:model.live.debounce.300ms="search" placeholder="Tên thuốc, hoạt chất, lô, số đăng ký..." class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"></label>
            <label class="block text-sm font-medium text-slate-700">Hiển thị<select wire:model.live="perPage" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm">@foreach ($perPageOptions as $option)<option value="{{ $option }}">{{ $option }} / trang</option>@endforeach</select></label>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-[1180px] w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-600"><tr><th class="px-4 py-3">Sản phẩm</th><th class="px-4 py-3">Lô</th><th class="px-4 py-3">Nhà thầu</th><th class="px-4 py-3">Giá trúng</th><th class="px-4 py-3">SL trúng</th><th class="px-4 py-3">Đã phân bổ</th><th class="px-4 py-3">Còn lại</th><th class="px-4 py-3">HSSP</th><th class="px-4 py-3 text-right">Thao tác</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($products as $product)
                    @php
                        $name = $product->effectiveMedicineAttribute('medicine_name');
                        $ingredient = $product->effectiveMedicineAttribute('active_ingredient');
                        $allocated = (float) $product->allocations->sum('allocated_quantity');
                        $winning = (float) ($product->quantity ?? 0);
                        $remaining = $winning - $allocated;
                        $price = $product->winning_price ?? $product->unit_price;
                    @endphp
                    <tr class="align-top hover:bg-slate-50">
                        <td class="min-w-64 px-4 py-4"><div class="font-semibold text-slate-950">{{ $name['value'] ?: '—' }}</div><div class="mt-1 text-xs text-slate-500">{{ $ingredient['value'] ?: '—' }} · {{ $product->concentration ?: '—' }}</div></td>
                        <td class="min-w-40 px-4 py-4"><div class="font-mono text-xs font-semibold">{{ $product->lot_no ?: '—' }}</div><div class="mt-1 text-xs text-slate-500">{{ $product->lot_name ?: '—' }}</div></td>
                        <td class="min-w-52 px-4 py-4"><div class="font-medium text-slate-900">{{ $product->winning_company_name ?: '—' }}</div><div class="mt-1 text-xs text-slate-500">{{ $product->contractor_code ?: '—' }}</div></td>
                        <td class="px-4 py-4 font-semibold text-indigo-700">{{ $price !== null ? number_format((float) $price, 0, ',', '.') . ' VNĐ' : '—' }}</td>
                        <td class="px-4 py-4 font-semibold">{{ $fmtQty($winning) }}</td>
                        <td class="px-4 py-4 font-semibold text-indigo-700">{{ $fmtQty($allocated) }}</td>
                        <td class="px-4 py-4 font-semibold {{ $remaining < 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ $fmtQty($remaining) }}</td>
                        <td class="px-4 py-4"><span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $product->medicine_match_status === 'verified' ? 'Đã đối soát' : 'Cần rà soát' }}</span></td>
                        <td class="px-4 py-4 text-right"><a href="{{ route('admin.pharma.drug-bid-awards.allocation-detail', $product->id) }}" class="inline-flex min-h-10 items-center rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700">Phân bổ</a></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-6 py-12 text-center text-slate-500">Không có sản phẩm phù hợp.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($lastPage > 1)<nav class="flex items-center justify-between border-t border-slate-200 px-4 py-4"><p class="text-sm text-slate-500">Trang {{ $currentPage }}/{{ $lastPage }}</p><div class="flex gap-2"><button type="button" wire:click="gotoPage({{ max(1, $currentPage - 1) }})" @disabled($products->onFirstPage()) class="min-h-10 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold disabled:opacity-40">Trước</button><button type="button" wire:click="gotoPage({{ min($lastPage, $currentPage + 1) }})" @disabled(!$products->hasMorePages()) class="min-h-10 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold disabled:opacity-40">Sau</button></div></nav>@endif
    </section>
</div>
