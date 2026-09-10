<div class="space-y-6">
    @if ($invoiceType === 'sold')
        <section class="rounded-2xl border border-emerald-200 bg-emerald-50/70 px-4 py-4 shadow-sm sm:px-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-bold uppercase tracking-wide text-emerald-700">Hóa đơn bán ra</p>
                    <h2 class="mt-1 text-base font-bold text-emerald-950">Phân loại nhanh toàn bộ hóa đơn bán ra là Hàng hóa</h2>
                    @if ($year !== 'all' && $month !== 'all')
                        <p class="mt-1 text-sm leading-6 text-emerald-800">Phạm vi: tháng {{ str_pad((string) $month, 2, '0', STR_PAD_LEFT) }}/{{ $year }}. Thao tác này áp dụng cho mọi hóa đơn bán ra trong tháng; sau đó bạn vẫn có thể đổi lại từng hóa đơn khi cần.</p>
                    @else
                        <p class="mt-1 text-sm leading-6 text-amber-800">Hãy chọn một năm và một tháng cụ thể để bật thao tác phân loại hàng loạt.</p>
                    @endif
                </div>
                <button
                    type="button"
                    wire:click="applySoldMonthAsGoods"
                    wire:confirm="Áp dụng phân loại Hàng hóa cho toàn bộ hóa đơn bán ra của tháng đang chọn? Các phân loại hiện có trong tháng này cũng sẽ được chuyển thành Hàng hóa; bạn có thể đổi lại từng hóa đơn sau đó."
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

    @include('Invoices::livewire.source-data-manager')
</div>
