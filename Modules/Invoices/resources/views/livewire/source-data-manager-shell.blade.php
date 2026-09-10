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
        <span id="source-data-export-summary" class="text-xs text-slate-500">Không chọn hóa đơn: xuất toàn bộ theo bộ lọc hiện tại.</span>
        <button id="source-data-export-button" type="submit" class="inline-flex min-h-10 items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Xuất Excel theo bộ lọc</button>
    </form>

    @include('Invoices::livewire.source-data-manager')

    <script>
        (() => {
            const root = document.currentScript?.closest('.space-y-6');
            if (!root || root.dataset.exportSelectionInstalled === '1') return;
            root.dataset.exportSelectionInstalled = '1';

            const form = root.querySelector('#source-data-export-form');
            const summary = root.querySelector('#source-data-export-summary');
            const button = root.querySelector('#source-data-export-button');
            const selected = new Set();

            const rowId = (row) => {
                const key = row?.getAttribute('wire:key') || '';
                return key.replace(/^source-data-(desktop|mobile)-row-/, '');
            };

            const sync = () => {
                root.querySelectorAll('[data-source-export-checkbox]').forEach((input) => {
                    const row = input.closest('[wire\\:key]');
                    input.checked = selected.has(rowId(row));
                });

                const visibleIds = [...new Set([...root.querySelectorAll('[wire\\:key^="source-data-desktop-row-"]')].map(rowId).filter(Boolean))];
                root.querySelectorAll('[data-source-export-select-all]').forEach((input) => {
                    input.checked = visibleIds.length > 0 && visibleIds.every((id) => selected.has(id));
                    input.indeterminate = visibleIds.some((id) => selected.has(id)) && !input.checked;
                });

                if (selected.size > 0) {
                    summary.textContent = `${selected.size} hóa đơn đã chọn để xuất.`;
                    button.textContent = `Xuất ${selected.size} hóa đơn đã chọn`;
                } else {
                    summary.textContent = 'Không chọn hóa đơn: xuất toàn bộ theo bộ lọc hiện tại.';
                    button.textContent = 'Xuất Excel theo bộ lọc';
                }
            };

            const addRowCheckbox = (row, mobile = false) => {
                if (!row || row.querySelector('[data-source-export-checkbox]')) return;
                const target = mobile ? row.querySelector(':scope > div:first-child') : row.querySelector('td:first-child');
                if (!target) return;
                const label = document.createElement('label');
                label.className = mobile
                    ? 'mb-2 inline-flex min-h-8 items-center gap-2 text-xs font-semibold text-indigo-700'
                    : 'mb-2 inline-flex min-h-8 items-center gap-2 text-xs font-semibold text-indigo-700';
                const input = document.createElement('input');
                input.type = 'checkbox';
                input.setAttribute('data-source-export-checkbox', '1');
                input.className = 'rounded border-slate-300 text-indigo-600 focus:ring-indigo-500';
                input.addEventListener('change', () => {
                    const id = rowId(row);
                    if (input.checked) selected.add(id); else selected.delete(id);
                    sync();
                });
                const text = document.createElement('span');
                text.textContent = 'Chọn xuất Excel';
                label.append(input, text);
                target.prepend(label);
            };

            const install = () => {
                root.querySelectorAll('[wire\\:key^="source-data-desktop-row-"]').forEach((row) => addRowCheckbox(row, false));
                root.querySelectorAll('[wire\\:key^="source-data-mobile-row-"]').forEach((row) => addRowCheckbox(row, true));

                const firstHeader = root.querySelector('table thead th:first-child');
                if (firstHeader && !firstHeader.querySelector('[data-source-export-select-all]')) {
                    const label = document.createElement('label');
                    label.className = 'mt-1 flex items-center gap-2 text-[11px] font-semibold normal-case tracking-normal text-indigo-700';
                    const input = document.createElement('input');
                    input.type = 'checkbox';
                    input.setAttribute('data-source-export-select-all', '1');
                    input.className = 'rounded border-slate-300 text-indigo-600 focus:ring-indigo-500';
                    input.addEventListener('change', () => {
                        const visibleIds = [...new Set([...root.querySelectorAll('[wire\\:key^="source-data-desktop-row-"]')].map(rowId).filter(Boolean))];
                        visibleIds.forEach((id) => input.checked ? selected.add(id) : selected.delete(id));
                        sync();
                    });
                    const text = document.createElement('span');
                    text.textContent = 'Chọn tất cả trang';
                    label.append(input, text);
                    firstHeader.append(label);
                }
                sync();
            };

            form?.addEventListener('submit', () => {
                form.querySelectorAll('input[data-export-source-id]').forEach((input) => input.remove());
                selected.forEach((id) => {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'source_ids[]';
                    hidden.value = id;
                    hidden.setAttribute('data-export-source-id', '1');
                    form.append(hidden);
                });
            });

            install();
            new MutationObserver(install).observe(root, { childList: true, subtree: true });
        })();
    </script>
</div>
