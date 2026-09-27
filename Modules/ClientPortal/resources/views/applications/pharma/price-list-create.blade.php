@extends('ClientPortal::layouts.application')

@section('title', 'Tạo bảng giá')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Lập bảng giá cho khách hàng')
@section('app-dashboard-route', route('client.pharma.dashboard'))

@section('content')
@php
    $editingPriceList = $editingPriceList ?? null;
    $isEditing = $editingPriceList !== null;
    $field = fn (string $name, $fallback = null) => old($name, $isEditing ? data_get($editingPriceList, $name, $fallback) : $fallback);
    $selectedExisting = $isEditing ? $editingPriceList->items->keyBy('medicine_variant_id') : collect();
    $canApprove = $canApprove ?? false;
    $isGlobalMode = !$isEditing && $canApprove && request('type') === 'global';
@endphp
<div class="mx-auto max-w-7xl space-y-5">
    <div class="flex items-center justify-between gap-3"><a href="{{ route('client.pharma.price-lists') }}" class="text-sm font-bold text-slate-600">← Bảng giá của tôi</a><span class="rounded-full bg-amber-100 px-3 py-1.5 text-xs font-bold text-amber-800">{{ $isGlobalMode ? 'Kích hoạt trực tiếp' : 'Lưu ở trạng thái Nháp' }}</span></div>
    <section class="rounded-[2rem] bg-slate-950 px-5 py-6 text-white shadow-sm sm:px-7"><p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-300">Create Price List</p><h1 class="mt-2 text-2xl font-black sm:text-3xl">{{ $isEditing ? 'Sửa bảng giá Nháp' : ($isGlobalMode ? 'Tạo bảng giá chung' : 'Tạo bảng giá cho khách hàng') }}</h1><p class="mt-2 text-sm text-slate-300">{{ $isGlobalMode ? 'Bảng giá chung được User phê duyệt tạo, gán User phụ trách và kích hoạt trực tiếp sau khi kiểm tra.' : 'Bảng giá khách hàng phải được khởi tạo từ bảng giá chung ACTIVE mà Admin đã cấp cho bạn.' }}</p></section>
    @if($errors->any())<div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>@endif

    @if(!$isEditing && $canApprove)<div class="flex gap-2 rounded-2xl border border-slate-200 bg-white p-2"><a href="{{ route('client.pharma.price-lists.create') }}" class="rounded-xl px-4 py-2 text-sm font-black {{ !$isGlobalMode ? 'bg-slate-950 text-white' : 'text-slate-600' }}">Bảng giá khách hàng</a><a href="{{ route('client.pharma.price-lists.create', ['type'=>'global']) }}" class="rounded-xl px-4 py-2 text-sm font-black {{ $isGlobalMode ? 'bg-slate-950 text-white' : 'text-slate-600' }}">Bảng giá chung</a></div>@endif

    <form id="price-list-editor" method="POST" action="{{ $isEditing ? route('client.pharma.price-lists.update', $editingPriceList->id) : ($isGlobalMode ? route('client.pharma.price-lists.global.store') : route('client.pharma.price-lists.store')) }}" class="space-y-5">@csrf @if($isEditing) @method('PUT') @endif
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-5"><p class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">01 · Khách hàng & mục đích</p><h2 class="mt-1 text-lg font-black text-slate-950">Thông tin bảng giá</h2></div>
            <div class="grid gap-4 lg:grid-cols-2">
                <label><span class="mb-1.5 block text-xs font-bold text-slate-500">Tên bảng giá *</span><input name="name" value="{{ $field('name') }}" required maxlength="255" class="h-12 w-full rounded-2xl border border-slate-300 px-4" placeholder="VD: Bảng giá BV An Bình Q4/2026"></label>
                @if($isGlobalMode)<div><span class="mb-1.5 block text-xs font-bold text-slate-500">User phụ trách *</span><select id="client-price-list-manager" name="manager_user_id" required class="h-12 w-full rounded-2xl border border-slate-300 px-4"><option value="">Chọn User phụ trách</option>@foreach($activeUsers as $assignedUser)<option value="{{ $assignedUser->id }}" @selected((string)old('manager_user_id') === (string)$assignedUser->id)>{{ $assignedUser->name }}{{ $assignedUser->email ? ' · '.$assignedUser->email : '' }}</option>@endforeach</select></div>@else<div><span class="mb-1.5 block text-xs font-bold text-slate-500">Khách hàng *</span><x-select-search id="client-price-list-customer" name="partner_id" placeholder="Tra cứu khách hàng..." :value="$field('partner_id')"><option value="">Chọn khách hàng</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" @selected((string)$field('partner_id') === (string)$customer->id)>{{ $customer->name }}{{ $customer->tax_code ? ' · MST '.$customer->tax_code : '' }}</option>@endforeach</x-select-search></div>@endif
                @if(!$isGlobalMode)<label><span class="mb-1.5 block text-xs font-bold text-slate-500">Mục đích *</span><select name="purpose_id" required class="h-12 w-full rounded-2xl border border-slate-300 px-4"><option value="">Chọn mục đích</option>@foreach($purposes as $purpose)<option value="{{ $purpose->id }}" @selected((string)$field('purpose_id') === (string)$purpose->id)>{{ $purpose->name }}</option>@endforeach</select></label>@endif
                <div class="grid grid-cols-2 gap-3"><label><span class="mb-1.5 block text-xs font-bold text-slate-500">Hiệu lực từ *</span><input type="date" name="effective_from" value="{{ $field('effective_from', now()->toDateString()) instanceof \Carbon\CarbonInterface ? $field('effective_from')->toDateString() : $field('effective_from', now()->toDateString()) }}" required class="h-12 w-full rounded-2xl border border-slate-300 px-3"></label><label><span class="mb-1.5 block text-xs font-bold text-slate-500">Đến *</span><input type="date" name="effective_to" value="{{ $field('effective_to', now()->addMonth()->toDateString()) instanceof \Carbon\CarbonInterface ? $field('effective_to')->toDateString() : $field('effective_to', now()->addMonth()->toDateString()) }}" required class="h-12 w-full rounded-2xl border border-slate-300 px-3"></label></div>
            </div>
            <label class="mt-4 block"><span class="mb-1.5 block text-xs font-bold text-slate-500">Ghi chú</span><textarea name="notes" rows="2" maxlength="1000" class="w-full rounded-2xl border border-slate-300 px-4 py-3">{{ $field('notes') }}</textarea></label>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div><p class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">02 · Bảng giá gốc</p><h2 class="mt-1 text-lg font-black text-slate-950">Chọn bảng giá để khởi tạo</h2><p class="mt-1 text-sm text-slate-500">Chỉ hiển thị bảng giá chung đang ACTIVE và nằm trong phạm vi Admin cấp cho User.</p></div>
            @if($sourcePriceLists->isEmpty())
                <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-800">Hiện chưa có bảng giá chung ACTIVE được cấp cho bạn. Vui lòng liên hệ người quản trị Pharma.</div>
            @else
                <div class="mt-4 grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto]">
                    <select id="source-price-list" name="source_price_list_id" required class="h-12 w-full rounded-2xl border border-slate-300 px-4"><option value="">Chọn bảng giá gốc</option>@foreach($sourcePriceLists as $source)<option value="{{ $source->id }}" @selected((string)old('source_price_list_id',$sourcePriceListId) === (string)$source->id)>{{ $source->code }} — {{ $source->name }} · {{ $source->items_count }} SP</option>@endforeach</select>
                    <button id="load-source-price-list" type="button" class="h-12 rounded-2xl bg-slate-950 px-5 text-sm font-black text-white">Khởi tạo từ bảng giá</button>
                </div>
            @endif
        </section>

        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-5"><p class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">03 · Sản phẩm & giá</p><div class="mt-1 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between"><div><h2 class="text-lg font-black text-slate-950">Chọn sản phẩm từ bảng giá gốc</h2><p class="mt-1 text-sm text-slate-500">Giá bán CT mặc định lấy từ bảng giá gốc. Bỏ checkbox nếu sản phẩm không áp dụng cho khách hàng này.</p></div>@if($sourcePriceListId)<label class="w-full lg:w-80"><span class="mb-1 block text-xs font-bold text-slate-500">Tìm sản phẩm</span><div class="relative"><input id="source-product-search" type="search" class="h-11 w-full rounded-2xl border border-slate-300 pl-4 pr-10 text-sm" placeholder="Tên thuốc, SKU, hoạt chất, SĐK..."><button id="clear-source-product-search" type="button" class="absolute right-2 top-1/2 hidden h-7 w-7 -translate-y-1/2 rounded-full text-lg font-bold text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Xóa tìm kiếm">×</button></div></label>@endif</div></div>
            @if(!$sourcePriceListId)
                <div class="p-8 text-center text-sm text-slate-500">Chọn bảng giá tại Bước 02 để tải sản phẩm.</div>
            @else
                <div class="max-h-[620px] overflow-auto"><table class="w-full min-w-[980px] text-left text-sm"><thead class="sticky top-0 bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500"><tr><th class="w-12 px-4 py-3"><input id="select-all-source-products" type="checkbox" class="h-5 w-5 rounded border-slate-300" title="Chọn/bỏ tất cả sản phẩm đang hiển thị"></th><th class="px-4 py-3">Thuốc</th><th class="px-4 py-3">Hoạt chất</th><th class="px-4 py-3 text-right">Giá kê khai</th><th class="w-48 px-4 py-3 text-right">Giá Bán (VAT) *</th></tr></thead><tbody class="divide-y divide-slate-100">
                @forelse($sourceProducts as $sourceItem) @php $product=$sourceItem->variant; $medicine=$product?->medicine; @endphp
                    <tr class="source-product-row" data-search="{{ mb_strtolower(($medicine?->name ?? '').' '.($product?->sku ?? '').' '.($medicine?->active_ingredients ?? '').' '.($medicine?->registration_number ?? '')) }}"><td class="px-4 py-3"><input type="checkbox" data-source-product-checkbox name="selected[{{ $product->id }}]" value="1" @checked(old('selected.'.$product->id, $isEditing ? $selectedExisting->has($product->id) : true)) class="h-5 w-5 rounded border-slate-300"></td><td class="px-4 py-3"><p class="font-black">{{ $medicine?->name }}</p><p class="mt-1 text-xs text-slate-400">{{ $product?->sku }} · {{ $medicine?->packaging_specification }}</p></td><td class="px-4 py-3 text-slate-600">{{ $medicine?->active_ingredients ?: '—' }}</td><td class="px-4 py-3 text-right font-bold tabular-nums">{{ $sourceItem->declared_price_snapshot !== null ? number_format((float)$sourceItem->declared_price_snapshot,0,',','.') : '—' }}</td><td class="px-4 py-3"><input type="text" inputmode="numeric" data-money-input data-raw-value="{{ old('company_price.'.$product->id, $isEditing && $selectedExisting->has($product->id) ? (int)$selectedExisting->get($product->id)->company_sale_price : ($sourceItem->company_sale_price !== null ? (int)$sourceItem->company_sale_price : '')) }}" name="company_price[{{ $product->id }}]" value="{{ ($v = old('company_price.'.$product->id, $isEditing && $selectedExisting->has($product->id) ? (int)$selectedExisting->get($product->id)->company_sale_price : ($sourceItem->company_sale_price !== null ? (int)$sourceItem->company_sale_price : ''))) !== '' ? number_format((float)str_replace('.', '', (string)$v),0,',','.') : '' }}" class="h-10 w-full rounded-xl border border-slate-300 px-3 text-right font-bold tabular-nums"></td></tr>
                @empty <tr><td colspan="5" class="p-8 text-center text-slate-500">Bảng giá gốc chưa có sản phẩm ACTIVE.</td></tr> @endforelse
                </tbody></table></div>
            @endif
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">04 · Kiểm tra & lưu</p>
            <div class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="text-lg font-black text-slate-950">{{ $isEditing ? 'Cập nhật bảng giá Nháp' : ($isGlobalMode ? 'Kiểm tra & kích hoạt bảng giá chung' : 'Lưu bảng giá Nháp') }}</h2><p class="mt-1 text-sm text-slate-500">{{ $isGlobalMode ? 'Bảng giá chung sẽ được kiểm tra, gán cho User phụ trách và ACTIVE ngay khi lưu.' : 'Sau khi lưu, bạn có thể kiểm tra lại chi tiết trước khi Gửi duyệt.' }}</p></div><div class="flex gap-2"><a href="{{ route('client.pharma.price-lists') }}" class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-bold text-slate-600">Hủy</a><button type="submit" @disabled(!$sourcePriceListId || $sourceProducts->isEmpty()) class="rounded-2xl bg-slate-950 px-6 py-3 text-sm font-black text-white disabled:opacity-40">{{ $isEditing ? 'Cập nhật bản Nháp' : ($isGlobalMode ? 'Kích hoạt bảng giá chung' : 'Lưu bảng giá Nháp') }}</button></div></div>
        </section>
    </form>
</div>
<script>
window.addEventListener('load', () => {
    const form = document.getElementById('price-list-editor');
    const customer = document.getElementById('client-price-list-customer');
    if (customer && window.TomSelect && !customer.tomselect) {
        new TomSelect(customer, { plugins: ['dropdown_input'], placeholder: 'Tra cứu khách hàng...', create: false, allowEmptyOption: true, dropdownParent: 'body' });
    }

    const draftKey = 'client-pharma-price-list-form-state';
    const restore = sessionStorage.getItem(draftKey);
    if (restore && form) {
        try {
            const state = JSON.parse(restore);
            ['name','purpose_id','effective_from','effective_to','notes'].forEach(name => {
                const el = form.elements.namedItem(name);
                if (el && state[name] !== undefined) el.value = state[name];
            });
            if (state.partner_id) {
                const partner = form.elements.namedItem('partner_id');
                if (partner) {
                    partner.value = state.partner_id;
                    if (partner.tomselect) partner.tomselect.setValue(state.partner_id, true);
                }
            }
        } finally {
            sessionStorage.removeItem(draftKey);
        }
    }

    const source = document.getElementById('source-price-list');
    const load = document.getElementById('load-source-price-list');
    if (source && load) {
        load.addEventListener('click', () => {
            if (!source.value || !form) return;
            const partner = form.elements.namedItem('partner_id');
            sessionStorage.setItem(draftKey, JSON.stringify({
                name: form.elements.namedItem('name')?.value || '',
                partner_id: partner?.value || '',
                purpose_id: form.elements.namedItem('purpose_id')?.value || '',
                effective_from: form.elements.namedItem('effective_from')?.value || '',
                effective_to: form.elements.namedItem('effective_to')?.value || '',
                notes: form.elements.namedItem('notes')?.value || '',
            }));
            const url = new URL(window.location.href);
            url.searchParams.set('source_price_list_id', source.value);
            window.location.href = url.toString();
        });
    }

    const search = document.getElementById('source-product-search');
    const clear = document.getElementById('clear-source-product-search');
    const selectAll = document.getElementById('select-all-source-products');
    const rows = [...document.querySelectorAll('.source-product-row')];
    const visibleCheckboxes = () => rows.filter(row => !row.classList.contains('hidden')).map(row => row.querySelector('[data-source-product-checkbox]')).filter(Boolean);
    const syncSelectAll = () => {
        if (!selectAll) return;
        const boxes = visibleCheckboxes();
        const checked = boxes.filter(box => box.checked).length;
        selectAll.checked = boxes.length > 0 && checked === boxes.length;
        selectAll.indeterminate = checked > 0 && checked < boxes.length;
    };
    const filterRows = () => {
        const q = (search?.value || '').trim().toLowerCase();
        rows.forEach(row => row.classList.toggle('hidden', q !== '' && !row.dataset.search.includes(q)));
        if (clear) clear.classList.toggle('hidden', q === '');
        syncSelectAll();
    };
    search?.addEventListener('input', filterRows);
    clear?.addEventListener('click', () => { search.value = ''; filterRows(); search.focus(); });
    selectAll?.addEventListener('change', () => { visibleCheckboxes().forEach(box => box.checked = selectAll.checked); syncSelectAll(); });
    rows.forEach(row => row.querySelector('[data-source-product-checkbox]')?.addEventListener('change', syncSelectAll));
    syncSelectAll();

    const moneyInputs = [...document.querySelectorAll('[data-money-input]')];
    const digits = value => String(value || '').replace(/\D/g, '');
    const formatMoney = value => digits(value).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    moneyInputs.forEach(input => {
        input.value = formatMoney(input.value);
        input.addEventListener('input', () => {
            const caretAtEnd = input.selectionStart === input.value.length;
            input.value = formatMoney(input.value);
            if (caretAtEnd) input.setSelectionRange(input.value.length, input.value.length);
        });
    });
    form?.addEventListener('submit', () => moneyInputs.forEach(input => { input.value = digits(input.value); }));
});
</script>
@endsection
