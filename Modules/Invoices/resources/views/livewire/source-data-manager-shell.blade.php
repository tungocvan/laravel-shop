<div
    class="space-y-6"
    x-data="{
        selected: [],
        visibleIds: @js($records->pluck('id')->map(fn ($id) => (string) $id)->values()->all()),
        installExportCheckboxes() {
            const install = (selector, sourceId, mobile = false) => {
                const row = this.$root.querySelector(selector);
                if (!row || row.querySelector('[data-export-source-checkbox]')) return;
                const target = mobile ? row : row.querySelector('td');
                if (!target) return;
                const label = document.createElement('label');
                label.setAttribute('data-export-source-checkbox', '1');
                label.className = mobile
                    ? 'mb-3 inline-flex min-h-9 items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700'
                    : 'mb-2 inline-flex min-h-8 items-center gap-2 text-xs font-semibold text-indigo-700';
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.className = 'rounded border-slate-300 text-indigo-600 focus:ring-indigo-500';
                checkbox.checked = this.selected.includes(sourceId);
                checkbox.addEventListener('change', () => {
                    this.selected = checkbox.checked
                        ? [...new Set([...this.selected, sourceId])]
                        : this.selected.filter(id => id !== sourceId);
                    this.syncExportCheckboxes();
                });
                const text = document.createElement('span');
                text.textContent = 'Chọn xuất Excel';
                label.appendChild(checkbox);
                label.appendChild(text);
                target.prepend(label);
            };
            this.visibleIds.forEach(id => {
                install(`[wire\\:key=\"source-data-desktop-row-${id}\"]`, id, false);
                install(`[wire\\:key=\"source-data-mobile-row-${id}\"]`, id, true);
            });
            this.syncExportCheckboxes();
        },
        syncExportCheckboxes() {
            this.$root.querySelectorAll('[data-export-source-checkbox] input').forEach(input => {
                const row = input.closest('[wire\\:key]');
                const key = row?.getAttribute('wire:key') || '';
                const id = key.replace(/^source-data-(desktop|mobile)-row-/, '');
                input.checked = this.selected.includes(id);
            });
        },
        toggleVisible(checked) {
            this.selected = checked
                ? [...new Set([...this.selected, ...this.visibleIds])]
                : this.selected.filter(id => !this.visibleIds.includes(id));
            this.syncExportCheckboxes();
        }
    }"
    x-init="$nextTick(() => { installExportCheckboxes(); new MutationObserver(() => installExportCheckboxes()).observe($root, { childList: true, subtree: true }); })"
>
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

    <section class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm sm:px-5">
        <form id="source-data-export-form" method="POST" action="{{ route('admin.invoices.source-data.export-detail') }}">
            @csrf
            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="invoice_type" value="{{ $invoiceType }}">
            <input type="hidden" name="partner" value="{{ $partner }}">
            <input type="hidden" name="search" value="{{ $search }}">
            <input type="hidden" name="detail_status" value="{{ $detailStatus }}">
            <input type="hidden" name="business_classification" value="{{ $businessClassification }}">
            <template x-for="sourceId in selected" :key="sourceId"><input type="hidden" name="source_ids[]" :value="sourceId"></template>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap items-center gap-3">
                    <label class="inline-flex min-h-10 items-center gap-2 text-sm font-semibold text-slate-800">
                        <input type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" :checked="visibleIds.length > 0 && visibleIds.every(id => selected.includes(id))" @change="toggleVisible($event.target.checked)">
                        <span>Chọn tất cả {{ number_format($records->count()) }} hóa đơn trên trang</span>
                    </label>
                    <span class="text-xs font-semibold text-indigo-700" x-show="selected.length > 0" x-text="selected.length + ' hóa đơn đã chọn để xuất'"></span>
                    <span class="text-xs text-slate-500" x-show="selected.length === 0">Không chọn: xuất toàn bộ theo bộ lọc hiện tại.</span>
                </div>
                <button type="submit" class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <span x-show="selected.length === 0">Xuất Excel theo bộ lọc</span>
                    <span x-show="selected.length > 0" x-text="'Xuất ' + selected.length + ' hóa đơn đã chọn'"></span>
                </button>
            </div>
        </form>
    </section>

    @include('Invoices::livewire.source-data-manager')
</div>
