@extends('ClientPortal::layouts.application')

@section('title', 'Tạo bảng giá')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Lập bảng giá cho khách hàng')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)

@section('content')
@php
    $editingPriceList = $editingPriceList ?? null;
    $isEditing = $editingPriceList !== null;
    $field = fn (string $name, $fallback = null) => old($name, $isEditing ? data_get($editingPriceList, $name, $fallback) : $fallback);
    $selectedExisting = $isEditing ? $editingPriceList->items->keyBy('medicine_variant_id') : collect();
    $canApprove = $canApprove ?? false;
    $isGlobalMode = !$isEditing && $canApprove && request('type') === 'global';
@endphp
<div class="mx-auto max-w-7xl space-y-4 pb-24 lg:pb-6">
    <header class="sticky top-0 z-40 -mx-4 border-b border-slate-200 bg-white/95 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 lg:static lg:mx-0 lg:rounded-3xl lg:border lg:px-5">
        <div class="flex min-h-11 items-center gap-3">
            <a href="{{ route('client.pharma.price-lists') }}" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-xl font-black text-slate-700 shadow-sm" aria-label="Quay lại Bảng giá của tôi">←</a>
            <div class="min-w-0 flex-1"><p class="truncate text-sm font-black text-slate-950">{{ $isEditing ? 'Sửa bảng giá' : 'Tạo bảng giá' }}</p><p class="truncate text-xs text-slate-500">{{ $isGlobalMode ? 'Bảng giá chung' : 'Bảng giá khách hàng' }}</p></div>
            <span class="shrink-0 rounded-full bg-amber-100 px-3 py-1.5 text-xs font-bold text-amber-800">{{ $isGlobalMode ? 'Kích hoạt trực tiếp' : 'Nháp' }}</span>
        </div>
    </header>
    @if($errors->any())<div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>@endif

    <nav class="rounded-3xl border border-slate-200 bg-white px-3 py-3 shadow-sm" aria-label="Các bước tạo bảng giá">
        <ol class="grid grid-cols-4 gap-1">
            @foreach([1 => ['Khởi tạo','Chọn loại bảng giá'], 2 => ['Khách hàng','Thông tin & mục đích'], 3 => ['Sản phẩm','Chọn sản phẩm và giá'], 4 => ['Xem lại','Kiểm tra và lưu']] as $step => [$label,$hint])
            <li><button type="button" data-step-jump="{{ $step }}" class="price-step flex w-full flex-col items-center gap-1 rounded-2xl px-1 py-2 text-center text-[10px] font-bold text-slate-400 lg:flex-row lg:justify-start lg:px-3 lg:text-left">
                <span class="step-dot inline-flex h-7 w-7 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-xs font-black">{{ $step }}</span>
                <span><strong class="block text-[11px] lg:text-sm">{{ $label }}</strong><small class="hidden font-medium lg:block">{{ $hint }}</small></span>
            </button></li>
            @endforeach
        </ol>
    </nav>

    <form id="price-list-editor" method="POST" action="{{ $isEditing ? route('client.pharma.price-lists.update', $editingPriceList->id) : ($isGlobalMode ? route('client.pharma.price-lists.global.store') : route('client.pharma.price-lists.store')) }}" class="space-y-5">@csrf @if($isEditing) @method('PUT') @endif
        <section data-wizard-panel="2" class="{{ $sourcePriceListId || $isGlobalMode ? '' : 'hidden' }} rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-5"><p class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">02 · Khách hàng & mục đích</p><h2 class="mt-1 text-lg font-black text-slate-950">Thông tin cơ bản</h2></div>
            <div class="grid gap-4 lg:grid-cols-2">
                <label><span class="mb-1.5 block text-xs font-bold text-slate-500">Tên bảng giá *</span><input name="name" value="{{ $field('name') }}" required maxlength="255" class="h-12 w-full rounded-2xl border border-slate-300 px-4" placeholder="VD: Bảng giá BV An Bình Q4/2026"></label>
                @if($isGlobalMode)<div><span class="mb-1.5 block text-xs font-bold text-slate-500">User phụ trách *</span><x-select-search id="client-price-list-manager" name="manager_user_id" placeholder="Tra cứu User phụ trách..." :value="old('manager_user_id')"><option value="">Chọn User phụ trách</option>@foreach($activeUsers as $assignedUser)<option value="{{ $assignedUser->id }}" @selected((string)old('manager_user_id') === (string)$assignedUser->id)>{{ $assignedUser->name }}{{ $assignedUser->email ? ' · '.$assignedUser->email : '' }}</option>@endforeach</x-select-search></div>@else<div><span class="mb-1.5 block text-xs font-bold text-slate-500">Khách hàng *</span><div class="rounded-2xl border border-slate-300 bg-white px-1 shadow-sm focus-within:border-slate-950"><x-select-search id="client-price-list-customer" name="partner_id" placeholder="Tra cứu khách hàng..." :value="$field('partner_id')"><option value="">Chọn khách hàng</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" @selected((string)$field('partner_id') === (string)$customer->id)>{{ $customer->name }}{{ $customer->tax_code ? ' · MST '.$customer->tax_code : '' }}</option>@endforeach</x-select-search></div></div>@endif
                @if(!$isGlobalMode)<label><span class="mb-1.5 block text-xs font-bold text-slate-500">Mục đích *</span><select name="purpose_id" required class="h-12 w-full rounded-2xl border border-slate-300 px-4"><option value="">Chọn mục đích</option>@foreach($purposes as $purpose)<option value="{{ $purpose->id }}" @selected((string)$field('purpose_id') === (string)$purpose->id)>{{ $purpose->name }}</option>@endforeach</select></label>@endif
                <div class="grid grid-cols-2 gap-3"><label><span class="mb-1.5 block text-xs font-bold text-slate-500">Hiệu lực từ *</span><input type="date" name="effective_from" value="{{ $field('effective_from', now()->toDateString()) instanceof \Carbon\CarbonInterface ? $field('effective_from')->toDateString() : $field('effective_from', now()->toDateString()) }}" required class="h-12 w-full rounded-2xl border border-slate-300 px-3"></label><label><span class="mb-1.5 block text-xs font-bold text-slate-500">Đến *</span><input type="date" name="effective_to" value="{{ $field('effective_to', now()->addMonth()->toDateString()) instanceof \Carbon\CarbonInterface ? $field('effective_to')->toDateString() : $field('effective_to', now()->addMonth()->toDateString()) }}" required class="h-12 w-full rounded-2xl border border-slate-300 px-3"></label></div>
            </div>
            <label class="mt-4 block"><span class="mb-1.5 block text-xs font-bold text-slate-500">Ghi chú</span><textarea name="notes" rows="2" maxlength="1000" class="w-full rounded-2xl border border-slate-300 px-4 py-3">{{ $field('notes') }}</textarea></label>
        </section>

        <section data-wizard-panel="1" class="{{ $sourcePriceListId || $isGlobalMode ? 'hidden' : '' }} rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div><p class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">01 · Khởi tạo bảng giá</p><h2 class="mt-1 text-lg font-black text-slate-950">{{ $isEditing ? 'Sửa bảng giá' : ($isGlobalMode ? 'Tạo bảng giá chung' : 'Tạo bảng giá cho khách hàng') }}</h2><p class="mt-1 text-sm text-slate-500">Chọn loại bảng giá và nguồn khởi tạo phù hợp trước khi nhập thông tin.</p>
            @if(!$isEditing && $canApprove)<div class="mt-4 grid gap-3 sm:grid-cols-2"><a href="{{ route('client.pharma.price-lists.create') }}" class="rounded-2xl border-2 p-4 {{ !$isGlobalMode ? 'border-indigo-500 bg-indigo-50' : 'border-slate-200' }}"><span class="block font-black text-slate-950">Bảng giá khách hàng</span><span class="mt-1 block text-xs text-slate-500">Bảng giá riêng theo khách hàng, phục vụ chào giá và bán hàng.</span></a><a href="{{ route('client.pharma.price-lists.create', ['type'=>'global']) }}" class="rounded-2xl border-2 p-4 {{ $isGlobalMode ? 'border-indigo-500 bg-indigo-50' : 'border-slate-200' }}"><span class="block font-black text-slate-950">Bảng giá chung</span><span class="mt-1 block text-xs text-slate-500">Bảng giá áp dụng chung, dùng làm bảng giá gốc hoặc tham chiếu.</span></a></div>@endif
            </div>
            @if(!$isGlobalMode)<div class="mt-5"><p class="text-xs font-black uppercase tracking-[0.12em] text-slate-500">Khởi tạo từ bảng giá</p></div>@endif
            <div class="{{ $isGlobalMode ? 'hidden' : '' }}"><p class="mt-1 text-sm text-slate-500">Chỉ hiển thị bảng giá chung đang ACTIVE và nằm trong phạm vi Admin cấp cho User.</p></div>
            @if($sourcePriceLists->isEmpty())
                <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-800">Hiện chưa có bảng giá chung ACTIVE được cấp cho bạn. Vui lòng liên hệ người quản trị Pharma.</div>
            @else
                <div class="mt-4 grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto]">
                    <div class="rounded-2xl border-2 border-slate-200 bg-white p-1 shadow-sm focus-within:border-slate-950"><x-select-search id="source-price-list" name="source_price_list_id" placeholder="Tra cứu bảng giá gốc..." :value="old('source_price_list_id',$sourcePriceListId)"><option value="">Chọn bảng giá gốc</option>@foreach($sourcePriceLists as $source)<option value="{{ $source->id }}" @selected((string)old('source_price_list_id',$sourcePriceListId) === (string)$source->id)>{{ $source->code }} — {{ $source->name }} · {{ $source->items_count }} SP</option>@endforeach</x-select-search></div>
                    <button id="load-source-price-list" type="button" class="h-12 rounded-2xl bg-slate-950 px-5 text-sm font-black text-white disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400" disabled>Khởi tạo từ bảng giá</button>
                </div>
            @endif
        </section>

        <section data-wizard-panel="3" class="hidden overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-5"><p class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">03 · Sản phẩm & giá</p><div class="mt-1 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between"><div><h2 class="text-lg font-black text-slate-950">Chọn sản phẩm từ bảng giá gốc</h2><p class="mt-1 text-sm text-slate-500">Giá bán CT mặc định lấy từ bảng giá gốc. Bỏ checkbox nếu sản phẩm không áp dụng cho khách hàng này.</p></div>@if($sourcePriceListId)<div class="w-full lg:w-96"><span class="mb-1 block text-xs font-bold text-slate-500">Tìm / chọn sản phẩm</span><div class="rounded-2xl border border-slate-300 bg-white px-1 shadow-sm focus-within:border-slate-950"><x-select-search id="source-product-search" name="product_search" placeholder="Tên thuốc, SKU, hoạt chất, SĐK..."><option value="">Tất cả sản phẩm</option>@foreach($sourceProducts as $searchItem) @php $searchProduct=$searchItem->variant; $searchMedicine=$searchProduct?->medicine; @endphp @if($searchProduct)<option value="{{ $searchProduct->id }}">{{ $searchMedicine?->name }} · {{ $searchProduct->sku }} · {{ $searchMedicine?->active_ingredients }}</option>@endif @endforeach</x-select-search></div></div>@endif</div></div>
            @if(!$sourcePriceListId)
                <div class="p-8 text-center text-sm text-slate-500">Chọn bảng giá tại Bước 01 để tải sản phẩm.</div>
            @else
                <div class="max-h-[620px] overflow-auto"><table class="w-full min-w-[980px] text-left text-sm"><thead class="sticky top-0 bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500"><tr><th class="w-12 px-4 py-3"><input id="select-all-source-products" type="checkbox" class="h-5 w-5 rounded border-slate-300" title="Chọn/bỏ tất cả sản phẩm đang hiển thị"></th><th class="px-4 py-3">Thuốc</th><th class="px-4 py-3">Hoạt chất</th><th class="px-4 py-3 text-right">Giá kê khai</th><th class="w-48 px-4 py-3 text-right">Giá Bán (VAT) *</th></tr></thead><tbody class="divide-y divide-slate-100">
                @forelse($sourceProducts as $sourceItem) @php $product=$sourceItem->variant; $medicine=$product?->medicine; @endphp
                    <tr class="source-product-row" data-search="{{ mb_strtolower(($medicine?->name ?? '').' '.($product?->sku ?? '').' '.($medicine?->active_ingredients ?? '').' '.($medicine?->registration_number ?? '')) }}"><td class="px-4 py-3"><input type="checkbox" data-source-product-checkbox name="selected[{{ $product->id }}]" value="1" @checked(old('selected.'.$product->id, $isEditing ? $selectedExisting->has($product->id) : true)) class="h-5 w-5 rounded border-slate-300"></td><td class="px-4 py-3"><p class="font-black">{{ $medicine?->name }}</p><p class="mt-1 text-xs text-slate-400">{{ $product?->sku }} · {{ $medicine?->packaging_specification }}</p></td><td class="px-4 py-3 text-slate-600">{{ $medicine?->active_ingredients ?: '—' }}</td><td class="px-4 py-3 text-right font-bold tabular-nums">{{ $sourceItem->declared_price_snapshot !== null ? number_format((float)$sourceItem->declared_price_snapshot,0,',','.') : '—' }}</td><td class="px-4 py-3"><input type="text" inputmode="numeric" data-money-input data-raw-value="{{ old('company_price.'.$product->id, $isEditing && $selectedExisting->has($product->id) ? (int)$selectedExisting->get($product->id)->company_sale_price : ($sourceItem->company_sale_price !== null ? (int)$sourceItem->company_sale_price : '')) }}" name="company_price[{{ $product->id }}]" value="{{ ($v = old('company_price.'.$product->id, $isEditing && $selectedExisting->has($product->id) ? (int)$selectedExisting->get($product->id)->company_sale_price : ($sourceItem->company_sale_price !== null ? (int)$sourceItem->company_sale_price : ''))) !== '' ? number_format((float)str_replace('.', '', (string)$v),0,',','.') : '' }}" class="h-12 w-full min-w-28 rounded-2xl border-2 border-slate-200 bg-white px-3 text-right text-base font-black tabular-nums shadow-sm outline-none focus:border-slate-950 focus:ring-2 focus:ring-slate-100"></td></tr>
                @empty <tr><td colspan="5" class="p-8 text-center text-slate-500">Bảng giá gốc chưa có sản phẩm ACTIVE.</td></tr> @endforelse
                </tbody></table></div>
            @endif
        </section>

        <section data-wizard-panel="4" class="hidden rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">04 · Xem lại & lưu</p>
            <div id="price-list-review" class="mt-4 grid gap-3 rounded-2xl bg-slate-50 p-4 text-sm sm:grid-cols-2">
                <p><span class="block text-xs font-bold text-slate-400">Loại bảng giá</span><strong>{{ $isGlobalMode ? 'Bảng giá chung' : 'Bảng giá khách hàng' }}</strong></p>
                <p><span class="block text-xs font-bold text-slate-400">Hiệu lực</span><strong data-review-dates>—</strong></p>
                <p><span class="block text-xs font-bold text-slate-400">Tên bảng giá</span><strong data-review-name>—</strong></p>
                <p><span class="block text-xs font-bold text-slate-400">Sản phẩm đã chọn</span><strong data-review-products>0 sản phẩm</strong></p>
            </div>
            <div class="mt-4"><h2 class="text-lg font-black text-slate-950">{{ $isEditing ? 'Cập nhật bảng giá Nháp' : ($isGlobalMode ? 'Kiểm tra & kích hoạt bảng giá chung' : 'Lưu bảng giá Nháp') }}</h2><p class="mt-1 text-sm text-slate-500">{{ $isGlobalMode ? 'Bảng giá chung sẽ được kiểm tra, gán cho User phụ trách và ACTIVE ngay khi lưu.' : 'Sau khi lưu, bạn có thể kiểm tra lại chi tiết trước khi Gửi duyệt.' }}</p></div>
        </section>
    </form>
    <div class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 px-4 py-3 backdrop-blur lg:sticky lg:bottom-4 lg:ml-auto lg:w-fit lg:rounded-2xl lg:border lg:shadow-lg">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-3">
            <button id="wizard-back" type="button" class="h-11 rounded-2xl border border-slate-200 px-5 text-sm font-bold text-slate-600">← Quay lại</button>
            <div class="ml-auto flex gap-2"><button id="wizard-next" type="button" class="h-11 rounded-2xl bg-slate-950 px-6 text-sm font-black text-white disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400">Tiếp tục →</button><button id="wizard-submit" type="submit" form="price-list-editor" class="hidden h-11 rounded-2xl bg-slate-950 px-6 text-sm font-black text-white disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400">{{ $isEditing ? 'Cập nhật bảng giá' : ($isGlobalMode ? 'Tạo bảng giá' : 'Lưu bảng giá') }}</button></div>
        </div>
    </div>
</div>
<script>
window.addEventListener('load', () => {
    const form = document.getElementById('price-list-editor');
    let currentStep = 1;
    const panels = [...document.querySelectorAll('[data-wizard-panel]')];
    const stepButtons = [...document.querySelectorAll('[data-step-jump]')];
    const backButton = document.getElementById('wizard-back');
    const nextButton = document.getElementById('wizard-next');
    const submitButton = document.getElementById('wizard-submit');
    const formatDate = value => {
        if (!value) return '—';
        const [y,m,d] = value.split('-');
        return [d,m,y].filter(Boolean).join('/');
    };
    const selectedProductCount = () => document.querySelectorAll('[data-source-product-checkbox]:checked').length;
    const syncReview = () => {
        const name = form?.elements.namedItem('name')?.value || '—';
        const from = form?.elements.namedItem('effective_from')?.value || '';
        const to = form?.elements.namedItem('effective_to')?.value || '';
        document.querySelector('[data-review-name]')?.replaceChildren(document.createTextNode(name));
        document.querySelector('[data-review-dates]')?.replaceChildren(document.createTextNode(formatDate(from) + ' → ' + formatDate(to)));
        document.querySelector('[data-review-products]')?.replaceChildren(document.createTextNode(selectedProductCount() + ' sản phẩm'));
    };
    const showStep = step => {
        currentStep = Math.min(4, Math.max(1, Number(step) || 1));
        panels.forEach(panel => panel.classList.toggle('hidden', Number(panel.dataset.wizardPanel) !== currentStep));
        stepButtons.forEach(button => {
            const active = Number(button.dataset.stepJump) === currentStep;
            const completed = Number(button.dataset.stepJump) < currentStep;
            button.classList.toggle('text-slate-950', active || completed);
            button.classList.toggle('text-slate-400', !active && !completed);
            const dot = button.querySelector('.step-dot');
            dot?.classList.toggle('bg-slate-950', active);
            dot?.classList.toggle('text-white', active);
        });
        backButton?.classList.toggle('invisible', currentStep === 1);
        nextButton?.classList.toggle('hidden', currentStep === 4);
        submitButton?.classList.toggle('hidden', currentStep !== 4);
        if (currentStep === 4) syncReview();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };
    const sourceReady = () => {{ $isGlobalMode ? 'true' : "(document.getElementById('source-price-list')?.value || '') !== ''" }};
    const stepTwoReady = () => {
        const requiredNames = [{{ $isGlobalMode ? "'name','manager_user_id','effective_from','effective_to'" : "'name','partner_id','purpose_id','effective_from','effective_to'" }}];
        return requiredNames.every(name => String(form?.elements.namedItem(name)?.value || '').trim() !== '');
    };
    const syncWizardActions = () => {
        if (nextButton) nextButton.disabled = currentStep === 1 ? !sourceReady() : (currentStep === 2 ? !stepTwoReady() : false);
        const loadSource = document.getElementById('load-source-price-list');
        if (loadSource) loadSource.disabled = !sourceReady();
        if (submitButton) submitButton.disabled = selectedProductCount() === 0;
    };
    const persistAndLoadSource = () => {
        const source = document.getElementById('source-price-list');
        if (!sourceReady() || !form) return false;
        @if(!$isGlobalMode)
        const partner = form.elements.namedItem('partner_id');
        sessionStorage.setItem('client-pharma-price-list-form-state', JSON.stringify({
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
        return true;
        @else
        showStep(2);
        return true;
        @endif
    };
    backButton?.addEventListener('click', () => showStep(currentStep - 1));
    nextButton?.addEventListener('click', () => {
        if (currentStep === 1) {
            @if(!$isGlobalMode)
            if (!{{ $sourcePriceListId ? 'true' : 'false' }}) { persistAndLoadSource(); return; }
            @endif
        }
        if (currentStep === 2 && !stepTwoReady()) return;
        showStep(currentStep + 1);
        syncWizardActions();
    });
    stepButtons.forEach(button => button.addEventListener('click', () => {
        const target = Number(button.dataset.stepJump);
        if (target > currentStep) return;
        showStep(target);
        syncWizardActions();
    }));
    form?.addEventListener('input', () => { if (currentStep === 4) syncReview(); syncWizardActions(); });
    form?.addEventListener('change', syncWizardActions);
    showStep({{ $sourcePriceListId || $isGlobalMode ? '2' : '1' }});
    syncWizardActions();
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
        source.addEventListener('change', syncWizardActions);
        source.tomselect?.on('change', syncWizardActions);
        load.addEventListener('click', () => persistAndLoadSource());
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
