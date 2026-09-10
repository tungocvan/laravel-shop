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
                <button type="button" wire:click="applySoldMonthAsGoods" wire:confirm="Áp dụng phân loại Hàng hóa cho các hóa đơn bán ra đang Chưa phân loại của tháng đang chọn? Các hóa đơn đã có phân loại sẽ được giữ nguyên." wire:loading.attr="disabled" wire:target="applySoldMonthAsGoods" @disabled($year === 'all' || $month === 'all') class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-40">
                    <span wire:loading.remove wire:target="applySoldMonthAsGoods">Áp dụng tháng này là Hàng hóa</span>
                    <span wire:loading wire:target="applySoldMonthAsGoods">Đang áp dụng...</span>
                </button>
            </div>
        </section>
    @endif

    <form id="source-data-export-form" method="POST" action="{{ route('admin.invoices.source-data.export-detail') }}" class="flex flex-wrap items-center justify-end gap-3">
        @csrf
        <input type="hidden" name="year" value="{{ $year }}">
        <input type="hidden" name="month" value="{{ $month }}">
        <input type="hidden" name="invoice_type" value="{{ $invoiceType }}">
        <input type="hidden" name="partner" value="{{ $partner }}">
        <input type="hidden" name="search" value="{{ $search }}">
        <input type="hidden" name="detail_status" value="{{ $detailStatus }}">
        <input type="hidden" name="business_classification" value="{{ $businessClassification }}">
        <span class="text-xs text-slate-500">Không chọn hóa đơn: xuất toàn bộ theo bộ lọc hiện tại.</span>
        <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Xuất Excel theo bộ lọc</button>
    </form>

    @include('Invoices::livewire.source-data-manager')

    @once
        <script>
            document.addEventListener('click', function (event) {
                if (!event.target.matches('[data-source-export-select-all]')) return;
                document.querySelectorAll('[data-source-export-checkbox]').forEach(function (checkbox) {
                    checkbox.checked = event.target.checked;
                });
            });
        </script>
    @endonce
</div>
