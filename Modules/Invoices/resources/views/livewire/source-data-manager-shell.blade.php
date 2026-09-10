<div class="space-y-6" data-source-export-root>
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
        <span data-source-export-summary class="text-xs text-slate-500">Không chọn hóa đơn: xuất toàn bộ theo bộ lọc hiện tại.</span>
        <button data-source-export-button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Xuất Excel theo bộ lọc</button>
    </form>

    @include('Invoices::livewire.source-data-manager')

    @once
        <script>
            (() => {
                const state = window.__invoiceSourceExportState ??= { selected: new Set() };

                const rowId = (row) => (row?.getAttribute('wire:key') || '').replace(/^source-data-(desktop|mobile)-row-/, '');

                const roots = () => document.querySelectorAll('[data-source-export-root]');

                const syncSummary = (root) => {
                    const summary = root.querySelector('[data-source-export-summary]');
                    const button = root.querySelector('[data-source-export-button]');
                    const count = state.selected.size;
                    if (summary) summary.textContent = count > 0 ? `${count} hóa đơn đã chọn để xuất.` : 'Không chọn hóa đơn: xuất toàn bộ theo bộ lọc hiện tại.';
                    if (button) button.textContent = count > 0 ? `Xuất ${count} hóa đơn đã chọn` : 'Xuất Excel theo bộ lọc';
                };

                const syncCheckboxes = (root) => {
                    root.querySelectorAll('[data-source-export-checkbox]').forEach((input) => {
                        const row = input.closest('[wire\\:key]');
                        input.checked = state.selected.has(rowId(row));
                    });
                    const visible = [...root.querySelectorAll('[wire\\:key^="source-data-desktop-row-"]')].map(rowId).filter(Boolean);
                    root.querySelectorAll('[data-source-export-select-all]').forEach((input) => {
                        input.checked = visible.length > 0 && visible.every((id) => state.selected.has(id));
                        input.indeterminate = visible.some((id) => state.selected.has(id)) && !input.checked;
                    });
                    syncSummary(root);
                };

                const install = (root) => {
                    root.querySelectorAll('[wire\\:key^="source-data-desktop-row-"]').forEach((row) => {
                        const cell = row.querySelector('td:first-child');
                        if (!cell || cell.querySelector('[data-source-export-checkbox]')) return;
                        const label = document.createElement('label');
                        label.className = 'mb-2 inline-flex min-h-8 items-center gap-2 text-xs font-semibold text-indigo-700';
                        label.innerHTML = '<input type="checkbox" data-source-export-checkbox class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"><span>Chọn xuất Excel</span>';
                        cell.prepend(label);
                    });

                    root.querySelectorAll('[wire\\:key^="source-data-mobile-row-"]').forEach((row) => {
                        const cell = row.querySelector(':scope > div:first-child');
                        if (!cell || cell.querySelector('[data-source-export-checkbox]')) return;
                        const label = document.createElement('label');
                        label.className = 'mb-2 inline-flex min-h-8 items-center gap-2 text-xs font-semibold text-indigo-700';
                        label.innerHTML = '<input type="checkbox" data-source-export-checkbox class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"><span>Chọn xuất Excel</span>';
                        cell.prepend(label);
                    });

                    const firstHeader = root.querySelector('table thead th:first-child');
                    if (firstHeader && !firstHeader.querySelector('[data-source-export-select-all]')) {
                        const label = document.createElement('label');
                        label.className = 'mt-1 flex items-center gap-2 text-[11px] font-semibold normal-case tracking-normal text-indigo-700';
                        label.innerHTML = '<input type="checkbox" data-source-export-select-all class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"><span>Chọn tất cả trang</span>';
                        firstHeader.append(label);
                    }

                    syncCheckboxes(root);
                };

                const installAll = () => roots().forEach(install);

                document.addEventListener('change', (event) => {
                    const root = event.target.closest?.('[data-source-export-root]');
                    if (!root) return;
                    if (event.target.matches('[data-source-export-checkbox]')) {
                        const id = rowId(event.target.closest('[wire\\:key]'));
                        if (event.target.checked) state.selected.add(id); else state.selected.delete(id);
                        syncCheckboxes(root);
                    }
                    if (event.target.matches('[data-source-export-select-all]')) {
                        const visible = [...root.querySelectorAll('[wire\\:key^="source-data-desktop-row-"]')].map(rowId).filter(Boolean);
                        visible.forEach((id) => event.target.checked ? state.selected.add(id) : state.selected.delete(id));
                        syncCheckboxes(root);
                    }
                });

                document.addEventListener('submit', (event) => {
                    const form = event.target.closest?.('#source-data-export-form');
                    if (!form) return;
                    form.querySelectorAll('[data-export-source-id]').forEach((input) => input.remove());
                    state.selected.forEach((id) => {
                        const hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = 'source_ids[]';
                        hidden.value = id;
                        hidden.setAttribute('data-export-source-id', '1');
                        form.append(hidden);
                    });
                });

                installAll();
                document.addEventListener('livewire:navigated', installAll);
                document.addEventListener('livewire:init', () => {
                    if (window.Livewire?.hook) {
                        Livewire.hook('morph.updated', ({ el }) => {
                            const root = el?.closest?.('[data-source-export-root]') || el?.querySelector?.('[data-source-export-root]');
                            if (root) queueMicrotask(() => install(root));
                        });
                    }
                });
            })();
        </script>
    @endonce
</div>
