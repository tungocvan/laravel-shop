<div class="space-y-6">
    @if ($invoiceType === 'sold' && $stats['unclassified'] > 0)
        <section class="rounded-2xl border border-emerald-200 bg-emerald-50/70 px-4 py-4 shadow-sm sm:px-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-bold uppercase tracking-wide text-emerald-700">Hóa đơn bán ra · còn {{ number_format($stats['unclassified']) }} chưa phân loại</p>
                    <h2 class="mt-1 text-base font-bold text-emerald-950">Phân loại nhanh hóa đơn bán ra chưa phân loại là Hàng hóa</h2>
                    @if ($year !== 'all' && $month !== 'all')
                        <p class="mt-1 text-sm leading-6 text-emerald-800">Phạm vi: tháng {{ str_pad((string) $month, 2, '0', STR_PAD_LEFT) }}/{{ $year }}. Chỉ các hóa đơn đang Chưa phân loại được chuyển thành Hàng hóa; phân loại đã lưu trước đó được giữ nguyên.</p>
                    @else
                        <p class="mt-1 text-sm leading-6 text-amber-800">Hãy chọn một năm và một tháng cụ thể để bật thao tác phân loại hàng loạt.</p>
                    @endif
                </div>
                <button
                    type="button"
                    wire:click="applySoldMonthAsGoods"
                    wire:confirm="Áp dụng phân loại Hàng hóa cho các hóa đơn bán ra đang Chưa phân loại của tháng đang chọn? Các hóa đơn đã có phân loại sẽ được giữ nguyên."
                    wire:loading.attr="disabled"
                    wire:target="applySoldMonthAsGoods"
                    @disabled($year === 'all' || $month === 'all')
                    class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-40"
                >
                    <span wire:loading.remove wire:target="applySoldMonthAsGoods">Áp dụng tháng này là Hàng hóa</span>
                    <span wire:loading wire:target="applySoldMonthAsGoods">Đang áp dụng...</span>
                </button>
            </div>
        </section>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm sm:px-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-wide text-indigo-600">Xuất Excel dữ liệu nguồn</p>
                <h2 class="mt-1 text-base font-bold text-slate-950">Thông tin hóa đơn + toàn bộ dòng detail</h2>
                <p class="mt-1 text-sm leading-6 text-slate-600">Excel dùng kỳ dữ liệu và các điều kiện lọc hiện tại. Mỗi dòng detail là một dòng Excel; hóa đơn có 3 dòng hàng hóa/dịch vụ sẽ xuất 3 dòng và lặp lại thông tin chung của hóa đơn.</p>
                <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-sm font-semibold text-slate-700">
                    <label class="inline-flex min-h-9 items-center gap-2"><input type="checkbox" wire:model.live="exportAll" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"><span>Tất cả</span></label>
                    <label class="inline-flex min-h-9 items-center gap-2"><input type="checkbox" wire:model.live="exportPurchase" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"><span>Mua vào</span></label>
                    <label class="inline-flex min-h-9 items-center gap-2"><input type="checkbox" wire:model.live="exportSold" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"><span>Bán ra</span></label>
                </div>
            </div>
            <button
                type="button"
                wire:click="exportSourceDetail"
                wire:loading.attr="disabled"
                wire:target="exportSourceDetail"
                class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-wait disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="exportSourceDetail">Xuất Excel đầy đủ</span>
                <span wire:loading wire:target="exportSourceDetail">Đang tạo Excel...</span>
            </button>
        </div>
    </section>

    @include('Invoices::livewire.source-data-manager')
</div>
