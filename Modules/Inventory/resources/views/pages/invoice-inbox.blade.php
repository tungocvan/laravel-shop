@extends('Admin::layouts.master')

@section('title', request()->filled('inbox') ? 'Xử lý nhập kho' : 'Hóa đơn chờ nhập kho')

@section('content')
<div class="space-y-6">
    @if(session('inventory_success'))
        <div role="status" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800">{{ session('inventory_success') }}</div>
    @endif

    @if(session('inventory_error'))
        <div role="alert" class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-800">{{ session('inventory_error') }}</div>
    @endif

    <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">HÓA ĐƠN MUA HÀNG → KHO</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">{{ request()->filled('inbox') ? 'Xử lý nhập kho' : 'Hóa đơn chờ nhập kho' }}</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                @if(request()->filled('inbox'))
                    Đối chiếu mặt hàng, bổ sung số lô/hạn sử dụng, chọn kho và kiểm tra phiếu trước khi xác nhận nhập kho.
                @else
                    Chọn năm, tháng để xem hóa đơn mua hàng. Hóa đơn đủ dữ liệu có thể bắt đầu nhập kho ngay; hóa đơn thiếu chi tiết được đánh dấu để đồng bộ dữ liệu trước.
                @endif
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if(request()->filled('inbox'))
                @if($selectedInboxHeader?->source_invoice_id && $selectedInboxHeader?->receipt?->status !== 'CONFIRMED' && (bool) auth('admin')->user()?->can('inventory.receipt.manage'))
                    <form method="POST" action="{{ route('admin.inventory.invoice-inbox.refresh-source', ['inboxId' => $selectedInboxHeader->id]) }}">
                        @csrf
                        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-emerald-300 bg-emerald-50 px-5 py-2.5 text-sm font-semibold text-emerald-800 hover:bg-emerald-100">↻ Cập nhật lại từ hóa đơn nguồn</button>
                    </form>
                @endif
                <a href="{{ route('admin.inventory.invoice-inbox') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-indigo-300 bg-white px-5 py-2.5 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">← Danh sách hóa đơn</a>
            @endif
            <a href="{{ route('admin.inventory.dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:border-indigo-300">Tổng quan kho</a>
        </div>
    </header>

    @if(request()->filled('inbox'))
        @if($selectedInboxHeader?->receipt?->status === 'DRAFT')
            <livewire:inventory.invoice-draft-editor :inbox-id="(int) request('inbox')" />
        @endif

        <template id="inventory-item-search-template">
            <div class="relative min-w-0 flex-1" data-inventory-item-search-shell>
                <x-search placeholder="Tìm SKU hoặc tên mặt hàng..." input-class="min-h-11" />
                <div data-inventory-item-search-results class="absolute inset-x-0 top-full z-[90] mt-2 hidden max-h-72 overflow-y-auto rounded-2xl border border-slate-200 bg-white p-1.5 shadow-2xl ring-1 ring-slate-950/5"></div>
            </div>
        </template>

        <livewire:inventory.invoice-inbox-workspace :selected-inbox-id="(int) request('inbox')" />
    @else
        <livewire:inventory.invoice-receiving-source-queue />
    @endif
</div>

@if(request()->filled('inbox'))
<script>
(() => {
    const endpoint = @json(route('admin.inventory.items.search'));
    const template = document.getElementById('inventory-item-search-template');

    const inventoryWorkspace = (element) => {
        const root = element.closest('[wire\\:id]');
        if (!root || !window.Livewire) return null;

        return window.Livewire.find(root.getAttribute('wire:id'));
    };

    const renderResults = (menu, items, select, input, lineId) => {
        menu.replaceChildren();

        if (!items.length) {
            menu.classList.add('hidden');
            return;
        }

        items.forEach((item) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'block min-h-11 w-full rounded-xl px-3 py-2.5 text-left transition hover:bg-slate-50 focus:bg-slate-50 focus:outline-none';

            const title = document.createElement('span');
            title.className = 'block truncate text-sm font-bold text-slate-900';
            title.textContent = item.sku;
            button.appendChild(title);

            const meta = document.createElement('span');
            meta.className = 'mt-0.5 block truncate text-xs font-medium text-slate-500';
            meta.textContent = `${item.display_name} · ${item.base_uom || '—'}`;
            button.appendChild(meta);

            button.addEventListener('pointerdown', (event) => event.preventDefault());
            button.addEventListener('click', async () => {
                const component = inventoryWorkspace(select);
                if (!component) return;

                input.value = `${item.sku} — ${item.display_name}`;
                menu.classList.add('hidden');
                await component.call('assignLine', Number(lineId), Number(item.id));
            });

            menu.appendChild(button);
        });

        menu.classList.remove('hidden');
    };

    const enhanceItemSelectors = () => {
        if (!template) return;

        document.querySelectorAll('select[wire\\:change^="assignLine("]').forEach((select) => {
            const action = select.getAttribute('wire:change') || '';
            const match = action.match(/assignLine\((\d+)/);
            if (!match) return;

            const lineId = match[1];
            const existing = select.parentElement?.querySelector(`[data-inventory-item-search-for="${lineId}"]`);
            if (existing) {
                select.classList.add('hidden');
                return;
            }

            const fragment = template.content.cloneNode(true);
            const shell = fragment.querySelector('[data-inventory-item-search-shell]');
            const input = shell?.querySelector('input[type="search"]');
            const menu = shell?.querySelector('[data-inventory-item-search-results]');
            if (!shell || !input || !menu || !select.parentElement) return;

            shell.dataset.inventoryItemSearchFor = lineId;
            select.parentElement.insertBefore(fragment, select);
            select.classList.add('hidden');

            let timer = null;
            let controller = null;

            const load = () => {
                window.clearTimeout(timer);
                timer = window.setTimeout(async () => {
                    controller?.abort();
                    controller = new AbortController();

                    try {
                        const url = new URL(endpoint, window.location.origin);
                        url.searchParams.set('q', input.value.trim());
                        const response = await fetch(url, {
                            headers: {'Accept': 'application/json'},
                            signal: controller.signal,
                        });
                        if (!response.ok) return;

                        const payload = await response.json();
                        renderResults(menu, Array.isArray(payload.items) ? payload.items : [], select, input, lineId);
                    } catch (error) {
                        if (error.name !== 'AbortError') console.error(error);
                    }
                }, 220);
            };

            input.addEventListener('focus', load);
            input.addEventListener('input', load);
            input.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') menu.classList.add('hidden');
            });
            input.addEventListener('blur', () => window.setTimeout(() => menu.classList.add('hidden'), 140));
        });
    };

    const bindDraftEditorTrigger = () => {
        document.querySelectorAll('summary').forEach((summary) => {
            if (!summary.textContent.includes('Chỉnh sửa phiếu nhập nháp trước khi xác nhận')) return;
            if (summary.dataset.draftModalBound === '1') return;

            summary.dataset.draftModalBound = '1';
            summary.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                window.Livewire?.dispatch('open-invoice-draft-editor');
            });
        });
    };

    const enhance = () => {
        enhanceItemSelectors();
        bindDraftEditorTrigger();
    };

    document.addEventListener('DOMContentLoaded', enhance);
    document.addEventListener('livewire:initialized', () => {
        enhance();
        window.Livewire?.hook('morph.updated', () => queueMicrotask(enhance));
    });
})();
</script>
@endif
@endsection
