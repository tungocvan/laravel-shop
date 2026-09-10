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
        <button data-source-export-button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-60">Xuất Excel theo bộ lọc</button>
    </form>

    @include('Invoices::livewire.source-data-manager')

    <div data-source-export-modal class="fixed inset-0 z-[140] hidden items-center justify-center bg-slate-950/45 px-4 py-6 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="source-data-export-modal-title">
        <div class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/10">
            <div class="border-b border-emerald-100 bg-emerald-50 px-5 py-5 sm:px-6">
                <div class="flex items-start gap-4">
                    <div data-source-export-modal-icon class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xl font-bold text-emerald-700">✓</div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Xuất Excel</p>
                        <h2 id="source-data-export-modal-title" class="mt-1 text-lg font-bold text-slate-950">Đã xuất dữ liệu thành công</h2>
                        <p data-source-export-modal-message class="mt-1 text-sm leading-6 text-slate-600">File Excel đã được tạo và tải xuống.</p>
                    </div>
                </div>
            </div>
            <div class="px-5 py-4 sm:px-6">
                <p class="text-sm text-slate-600">Các checkbox hóa đơn đã xuất đã được bỏ chọn để tránh xuất lặp ngoài ý muốn.</p>
            </div>
            <div class="flex justify-end border-t border-slate-100 bg-slate-50 px-5 py-4 sm:px-6">
                <button data-source-export-modal-close type="button" class="inline-flex min-h-10 items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Đóng</button>
            </div>
        </div>
    </div>

    @once
        <script>
            (() => {
                const state = window.__invoiceSourceExportState ??= { selected: new Set() };

                const rowId = (row) => (row?.getAttribute('wire:key') || '').replace(/^source-data-(desktop|mobile)-row-/, '');
                const roots = () => document.querySelectorAll('[data-source-export-root]');

                const showModal = (root, message, error = false) => {
                    const modal = root.querySelector('[data-source-export-modal]');
                    if (!modal) return;
                    const title = modal.querySelector('#source-data-export-modal-title');
                    const body = modal.querySelector('[data-source-export-modal-message]');
                    const icon = modal.querySelector('[data-source-export-modal-icon]');
                    if (title) title.textContent = error ? 'Không thể xuất Excel' : 'Đã xuất dữ liệu thành công';
                    if (body) body.textContent = message;
                    if (icon) {
                        icon.textContent = error ? '!' : '✓';
                        icon.className = error
                            ? 'flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-rose-100 text-xl font-bold text-rose-700'
                            : 'flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xl font-bold text-emerald-700';
                    }
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                };

                const hideModal = (modal) => {
                    modal?.classList.add('hidden');
                    modal?.classList.remove('flex');
                };

                const syncSummary = (root) => {
                    const summary = root.querySelector('[data-source-export-summary]');
                    const button = root.querySelector('[data-source-export-button]');
                    const count = state.selected.size;
                    if (summary) summary.textContent = count > 0 ? `${count} hóa đơn đã chọn để xuất.` : 'Không chọn hóa đơn: xuất toàn bộ theo bộ lọc hiện tại.';
                    if (button && !button.disabled) button.textContent = count > 0 ? `Xuất ${count} hóa đơn đã chọn` : 'Xuất Excel theo bộ lọc';
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

                document.addEventListener('click', (event) => {
                    const close = event.target.closest?.('[data-source-export-modal-close]');
                    if (close) hideModal(close.closest('[data-source-export-modal]'));
                    if (event.target.matches?.('[data-source-export-modal]')) hideModal(event.target);
                });

                document.addEventListener('submit', async (event) => {
                    const form = event.target.closest?.('#source-data-export-form');
                    if (!form) return;
                    event.preventDefault();
                    if (form.dataset.exporting === '1') return;

                    const root = form.closest('[data-source-export-root]');
                    const button = root?.querySelector('[data-source-export-button]');
                    const selectedCount = state.selected.size;
                    form.dataset.exporting = '1';
                    if (button) {
                        button.disabled = true;
                        button.textContent = 'Đang tạo Excel...';
                    }

                    form.querySelectorAll('[data-export-source-id]').forEach((input) => input.remove());
                    state.selected.forEach((id) => {
                        const hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = 'source_ids[]';
                        hidden.value = id;
                        hidden.setAttribute('data-export-source-id', '1');
                        form.append(hidden);
                    });

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            body: new FormData(form),
                            credentials: 'same-origin',
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        });
                        if (!response.ok) throw new Error(`HTTP ${response.status}`);

                        const blob = await response.blob();
                        const disposition = response.headers.get('Content-Disposition') || '';
                        const utf8Name = disposition.match(/filename\*=UTF-8''([^;]+)/i)?.[1];
                        const quotedName = disposition.match(/filename="([^"]+)"/i)?.[1];
                        const filename = utf8Name ? decodeURIComponent(utf8Name) : (quotedName || 'hoa-don-chi-tiet.xlsx');
                        const url = URL.createObjectURL(blob);
                        const link = document.createElement('a');
                        link.href = url;
                        link.download = filename;
                        document.body.append(link);
                        link.click();
                        link.remove();
                        setTimeout(() => URL.revokeObjectURL(url), 1000);

                        state.selected.clear();
                        form.querySelectorAll('[data-export-source-id]').forEach((input) => input.remove());
                        if (root) syncCheckboxes(root);
                        showModal(
                            root,
                            selectedCount > 0
                                ? `Đã xuất ${selectedCount} hóa đơn đã chọn. Các checkbox vừa xuất đã được bỏ chọn.`
                                : 'Đã xuất toàn bộ hóa đơn theo bộ lọc hiện tại.',
                        );
                    } catch (error) {
                        showModal(root, 'Có lỗi khi tạo hoặc tải file Excel. Vui lòng thử lại.', true);
                    } finally {
                        form.dataset.exporting = '0';
                        if (button) button.disabled = false;
                        if (root) syncSummary(root);
                    }
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
