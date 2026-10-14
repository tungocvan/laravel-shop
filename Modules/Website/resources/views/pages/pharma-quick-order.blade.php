@extends('Website::layouts.frontend')

@section('title', 'Đặt hàng nhanh Pharma')

@section('content')
<main class="min-h-screen bg-slate-50 px-4 py-6 pb-28 text-slate-900 sm:px-6" aria-labelledby="quick-order-title">
    <div class="mx-auto max-w-6xl">
        <nav class="mb-4 text-sm text-slate-500"><a href="{{ route('home') }}">Trang chủ</a> / Đặt hàng nhanh</nav>
        <h1 id="quick-order-title" class="text-2xl font-bold sm:text-3xl">Đặt hàng nhanh Pharma</h1>
        <p class="mt-2 text-sm text-slate-600">Chọn số lượng thuốc cần mua. Giá và điều kiện mua sẽ được xác nhận khi xét duyệt yêu cầu.</p>

        <div class="mt-6 grid gap-5 lg:grid-cols-[minmax(0,1fr)_260px]">
            <section class="min-w-0" aria-label="Danh sách thuốc">
                <label for="quick-order-search" class="sr-only">Tìm tên thuốc</label>
                <div class="flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 shadow-sm">
                    <svg class="h-5 w-5 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                    <input id="quick-order-search" type="search" maxlength="120" value="{{ $search }}" autocomplete="off" placeholder="Nhập tên thuốc cần tìm..." class="min-w-0 flex-1 border-0 bg-transparent py-2 text-sm outline-none">
                    <button id="quick-order-clear" type="button" class="rounded-lg px-2 py-2 text-sm text-slate-500" aria-label="Xóa tìm kiếm">Xóa</button>
                </div>
                <p id="quick-order-status" role="status" aria-live="polite" class="mt-2 min-h-5 text-xs text-slate-500"></p>
                <div id="quick-order-results" class="mt-2 divide-y divide-slate-200 overflow-hidden rounded-xl border border-slate-200 bg-white"></div>
                <div class="mt-4 text-center">
                    <button id="quick-order-more" type="button" class="hidden rounded-lg border border-emerald-600 bg-white px-5 py-2.5 text-sm font-semibold text-emerald-700 active:scale-[0.985]">Xem thêm</button>
                </div>
            </section>

            <aside class="hidden self-start rounded-xl border border-slate-200 bg-white p-4 shadow-sm lg:sticky lg:top-5 lg:block" aria-label="Giỏ hàng tạm">
                <h2 class="font-semibold">Giỏ hàng tạm</h2>
                <p class="mt-2 text-sm text-slate-600"><span data-quick-order-lines>0</span> sản phẩm · <span data-quick-order-quantity>0</span> đơn vị</p>
                <p class="mt-2 text-sm font-semibold text-emerald-700">Tạm tính: <span data-quick-order-total>0 đ</span></p>
                <p class="mt-1 text-xs text-amber-700" data-quick-order-unpriced hidden>Có sản phẩm chưa xác định đơn giá.</p>
                <p class="mt-3 text-xs text-slate-500">Chưa gửi yêu cầu. Đơn hàng cần được Admin xét duyệt trước khi xuất kho.</p>
                <button type="button" data-quick-order-continue disabled class="mt-4 w-full rounded-lg bg-emerald-700 px-4 py-3 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-500">Tiếp tục</button>
            </aside>
        </div>
        <section id="quick-order-buyer-panel" hidden class="mt-6 rounded-xl border border-slate-200 bg-white p-4 sm:p-6" aria-labelledby="quick-order-buyer-title">
            <h2 id="quick-order-buyer-title" class="text-lg font-semibold">Thông tin người mua và nhận hàng</h2>
            <p class="mt-1 text-sm text-slate-600">Đây là bản nháp để chuẩn bị yêu cầu. Chưa gửi đến Admin.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="quick-order-buyer-type" class="mb-1 block text-sm font-medium">Đối tượng mua</label>
                    <select id="quick-order-buyer-type" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                        <option value="company">Doanh nghiệp / cơ sở y tế / nhà thuốc</option>
                        <option value="individual">Cá nhân</option>
                    </select>
                </div>
                <div data-company-field>
                    <label for="quick-order-company" class="mb-1 block text-sm font-medium">Tên doanh nghiệp / cơ sở</label>
                    <input id="quick-order-company" maxlength="255" autocomplete="organization" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                </div>
                <div data-company-field>
                    <label for="quick-order-tax-code" class="mb-1 block text-sm font-medium">Mã số thuế</label>
                    <input id="quick-order-tax-code" maxlength="20" inputmode="numeric" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                </div>
                <div>
                    <label for="quick-order-recipient" class="mb-1 block text-sm font-medium">Người nhận hàng</label>
                    <input id="quick-order-recipient" maxlength="150" autocomplete="name" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                </div>
                <div>
                    <label for="quick-order-phone" class="mb-1 block text-sm font-medium">Số điện thoại</label>
                    <input id="quick-order-phone" maxlength="20" autocomplete="tel" inputmode="tel" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label for="quick-order-address" class="mb-1 block text-sm font-medium">Địa chỉ nhận hàng</label>
                    <textarea id="quick-order-address" rows="2" maxlength="500" autocomplete="street-address" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"></textarea>
                </div>
                <div class="sm:col-span-2">
                    <label for="quick-order-notes" class="mb-1 block text-sm font-medium">Ghi chú</label>
                    <textarea id="quick-order-notes" rows="2" maxlength="1000" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"></textarea>
                </div>
            </div>
            <p class="mt-4 text-xs text-amber-700">Thông tin được lưu tạm trong phiên đăng nhập trên trình duyệt này. Chưa xác minh tư cách mua thuốc; không phải đơn đặt hàng.</p>
            <button type="button" disabled class="mt-4 cursor-not-allowed rounded-lg bg-slate-200 px-5 py-3 text-sm font-semibold text-slate-500">Gửi yêu cầu (sắp triển khai)</button>
        </section>
    </div>
</main>
<div class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white px-4 py-3 shadow-lg lg:hidden" style="padding-bottom:calc(12px + env(safe-area-inset-bottom, 0px))">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-3">
        <div><p class="text-sm font-semibold">Giỏ hàng tạm</p><p class="text-xs text-slate-500"><span data-quick-order-lines>0</span> sản phẩm · <span data-quick-order-quantity>0</span> đơn vị</p><p class="text-xs font-semibold text-emerald-700">Tạm tính: <span data-quick-order-total>0 đ</span></p><p class="text-xs text-amber-700" data-quick-order-unpriced hidden>Có sản phẩm chưa xác định đơn giá.</p></div>
        <button type="button" data-quick-order-continue disabled class="rounded-lg bg-emerald-700 px-4 py-3 text-xs font-semibold text-white disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-500">Tiếp tục</button>
    </div>
</div>
@endsection

@push('scripts')
@php
    $quickOrderInitialProducts = $quickOrderProducts->values()->all();
    $quickOrderInitialNextPage = $items->hasMorePages() ? $items->currentPage() + 1 : null;
@endphp
<script>
(() => {
    const endpoint = @json(route('website.pharma.quick-order'));
    const initial = @json($quickOrderInitialProducts);
    const initialNext = @json($quickOrderInitialNextPage);
    const search = document.getElementById('quick-order-search');
    const clear = document.getElementById('quick-order-clear');
    const results = document.getElementById('quick-order-results');
    const status = document.getElementById('quick-order-status');
    const more = document.getElementById('quick-order-more');
    if (!search || !results) return;
    const draftStorageKey = 'website.pharma.quick_order.draft.v1.' + @json((string) auth()->id());
    const quantities = new Map();
    const buyerFields = ['buyer-type', 'company', 'tax-code', 'recipient', 'phone', 'address', 'notes'];
    const buyerPanel = document.getElementById('quick-order-buyer-panel');
    const buyerType = document.getElementById('quick-order-buyer-type');
    const saveDraft = () => {
        try {
            const buyer = {};
            buyerFields.forEach(field => { buyer[field] = document.getElementById('quick-order-' + field)?.value || ''; });
            window.sessionStorage.setItem(draftStorageKey, JSON.stringify({
                quantities: [...quantities].map(([id, qty]) => [id, qty]),
                buyer
            }));
        } catch (_) { /* Browser storage may be disabled. */ }
    };
    try {
        const draft = JSON.parse(window.sessionStorage.getItem(draftStorageKey) || '{}');
        if (Array.isArray(draft.quantities)) {
            draft.quantities.slice(0, 200).forEach(entry => {
                if (!Array.isArray(entry) || entry.length !== 2) return;
                const id = Number(entry[0]), qty = Number(entry[1]);
                if (Number.isSafeInteger(id) && id > 0 && Number.isSafeInteger(qty) && qty > 0 && qty <= 9999) quantities.set(id, qty);
            });
        }
        if (draft.buyer && typeof draft.buyer === 'object') {
            buyerFields.forEach(field => {
                const input = document.getElementById('quick-order-' + field);
                if (input && typeof draft.buyer[field] === 'string') input.value = draft.buyer[field].slice(0, input.maxLength > 0 ? input.maxLength : 1000);
            });
        }
    } catch (_) { /* Invalid stored drafts are ignored. */ }
    const toggleCompanyFields = () => {
        document.querySelectorAll('[data-company-field]').forEach(el => el.hidden = buyerType.value !== 'company');
    };
    buyerType.addEventListener('change', toggleCompanyFields);
    buyerFields.forEach(field => document.getElementById('quick-order-' + field)?.addEventListener('input', saveDraft));
    toggleCompanyFields();
    document.querySelectorAll('[data-quick-order-continue]').forEach(button => button.addEventListener('click', () => {
        if (!quantities.size) return;
        buyerPanel.hidden = false;
        buyerPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }));
    const knownProducts = new Map(initial.map(product => [product.id, product]));
    let products = initial;
    let nextPage = initialNext;
    let controller = null;
    let timer = null;
    let serial = 0;
    let loading = false;

    const updateSummary = () => {
        const entries = [...quantities.values()].filter(n => n > 0);
        document.querySelectorAll('[data-quick-order-lines]').forEach(el => el.textContent = String(entries.length));
        document.querySelectorAll('[data-quick-order-quantity]').forEach(el => el.textContent = String(entries.reduce((a,b) => a+b, 0)));
        let total = 0;
        let unknown = false;
        quantities.forEach((quantity, id) => {
            const price = knownProducts.get(id)?.company_sale_price;
            if (price === null || price === undefined || !Number.isFinite(Number(price))) unknown = true;
            else total += quantity * Number(price);
        });
        document.querySelectorAll('[data-quick-order-total]').forEach(el => el.textContent = total.toLocaleString('vi-VN') + ' đ' + (unknown ? ' +' : ''));
        document.querySelectorAll('[data-quick-order-unpriced]').forEach(el => el.hidden = !unknown);
        document.querySelectorAll('[data-quick-order-continue]').forEach(el => el.disabled = entries.length === 0);
    };
    const setQuantity = (id, value) => {
        const parsed = Number(value);
        const amount = Number.isFinite(parsed) ? Math.min(9999, Math.max(0, Math.trunc(parsed))) : 0;
        if (amount) quantities.set(id, amount);
        else quantities.delete(id);
        updateSummary();
        saveDraft();
        render();
    };
    const render = () => {
        results.replaceChildren();
        for (const product of products) {
            const row = document.createElement('article');
            row.className = 'flex min-w-0 flex-wrap items-center gap-3 p-3 sm:flex-nowrap sm:p-4';
            const picture = document.createElement('div');
            picture.className = 'flex h-16 w-16 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xs text-slate-400';
            picture.textContent = 'Thuốc';
            const details = document.createElement('div');
            details.className = 'min-w-0 flex-1';
            const name = document.createElement('a');
            name.className = 'line-clamp-2 text-sm font-semibold text-slate-900 hover:text-emerald-700';
            name.textContent = product.title;
            name.href = @json(route('product.list')) .replace(/\/$/, '') + '/' + encodeURIComponent(product.slug);
            const note = document.createElement('p');
            note.className = 'mt-1 text-xs text-slate-500';
            note.textContent = product.company_sale_price !== null
                ? 'Đơn giá (Có VAT): ' + Number(product.company_sale_price).toLocaleString('vi-VN') + ' đ'
                : 'Đơn giá (Có VAT): Liên hệ xác nhận';
            details.append(name, note);
            const controls = document.createElement('div');
            controls.className = 'ml-auto flex shrink-0 items-center gap-1';
            const minus = document.createElement('button');
            minus.type = 'button'; minus.textContent = '−'; minus.setAttribute('aria-label', 'Giảm số lượng ' + product.title);
            minus.className = 'h-10 w-10 rounded-lg border border-slate-300 text-lg disabled:opacity-40';
            minus.disabled = !(quantities.get(product.id) > 0);
            minus.addEventListener('click', () => setQuantity(product.id, (quantities.get(product.id) || 0) - 1));
            const input = document.createElement('input');
            input.type = 'number'; input.min = '0'; input.max = '9999'; input.step = '1'; input.inputMode = 'numeric';
            input.className = 'h-10 w-16 rounded-lg border border-slate-300 text-center text-sm';
            input.value = quantities.get(product.id) || 0;
            input.setAttribute('aria-label', 'Số lượng ' + product.title);
            input.addEventListener('change', () => setQuantity(product.id, input.value));
            const plus = document.createElement('button');
            plus.type = 'button'; plus.textContent = '+'; plus.setAttribute('aria-label', 'Tăng số lượng ' + product.title);
            plus.className = 'h-10 w-10 rounded-lg bg-emerald-700 text-lg font-bold text-white active:scale-[0.985]';
            plus.addEventListener('click', () => setQuantity(product.id, (quantities.get(product.id) || 0) + 1));
            controls.append(minus, input, plus);
            row.append(picture, details, controls);
            results.append(row);
        }
        if (!products.length) {
            const empty = document.createElement('p');
            empty.className = 'p-6 text-sm text-slate-500';
            empty.textContent = 'Không tìm thấy thuốc phù hợp.';
            results.append(empty);
        }
        more.classList.toggle('hidden', !nextPage);
        more.disabled = loading;
    };
    const fetchPage = async (page, append = false) => {
        controller?.abort();
        controller = new AbortController();
        const current = ++serial;
        loading = true;
        more.disabled = true;
        status.textContent = 'Đang tìm kiếm...';
        const url = new URL(endpoint, window.location.origin);
        url.searchParams.set('search', search.value.trim());
        url.searchParams.set('page', String(page));
        try {
            const response = await fetch(url, { signal: controller.signal, credentials: 'same-origin', headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Không tải được danh sách thuốc.');
            const payload = await response.json();
            if (current !== serial) return;
            products = append ? products.concat(payload.products) : payload.products;
            payload.products.forEach(product => knownProducts.set(product.id, product));
            updateSummary();
            nextPage = payload.next_page;
            status.textContent = products.length + ' sản phẩm đã hiển thị';
        } catch (error) {
            if (error.name !== 'AbortError' && current === serial) status.textContent = 'Không thể tải kết quả. Vui lòng thử lại.';
        } finally {
            if (current === serial) { loading = false; render(); }
        }
    };
    search.addEventListener('input', () => {
        clearTimeout(timer);
        controller?.abort();
        serial++;
        timer = setTimeout(() => fetchPage(1), 300);
    });
    clear.addEventListener('click', () => {
        search.value = '';
        clearTimeout(timer);
        fetchPage(1);
        search.focus();
    });
    more.addEventListener('click', () => { if (nextPage && !loading) fetchPage(nextPage, true); });
    render();
    updateSummary();
})();
</script>
@endpush
