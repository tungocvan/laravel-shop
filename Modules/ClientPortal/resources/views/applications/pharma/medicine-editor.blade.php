@extends('ClientPortal::layouts.application')
@section('title', $medicine ? 'Sửa thuốc' : 'Thêm thuốc')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Medicine Master')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('content')
<div class="mx-auto max-w-6xl pb-24">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <a href="{{ route('client.pharma.products') }}" class="text-sm font-semibold text-indigo-700 hover:underline">← Danh mục thuốc</a>
            <div class="mt-3 flex items-center gap-3"><h1 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ $medicine ? 'Chỉnh sửa thuốc' : 'Thêm thuốc mới' }}</h1><span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">Medicine Master</span></div>
            <p class="mt-2 text-sm text-slate-500">Thông tin chuẩn được lưu trực tiếp vào danh mục thuốc. Chọn từng mục để nhập liệu.</p>
        </div>
    </div>
    @if(session('success'))<div role="status" class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    @if($errors->any())<div role="alert" class="mb-4 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">Vui lòng kiểm tra dữ liệu: {{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ $medicine ? route('client.pharma.products.update', $medicine->id) : route('client.pharma.products.store') }}" x-ref="medicineForm">
        @csrf
        @if($medicine) @method('PUT') @endif
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-50/80 px-3 pt-3 sm:px-5">
                <nav class="flex gap-1 overflow-x-auto" role="tablist" aria-label="Nhóm thông tin thuốc" data-medicine-tabs>
                    <button type="button" id="medicine-tab-identity" role="tab" aria-controls="medicine-panel-identity" aria-selected="true" tabindex="0" data-tab="identity" class="flex min-h-12 shrink-0 items-center gap-2 rounded-t-xl border-b-2 px-4 py-3 text-sm font-semibold transition-colors border-indigo-600 bg-white text-indigo-700"><span class="text-xs opacity-60">01</span><span>Thông tin thuốc</span></button>
                    <button type="button" id="medicine-tab-registration" role="tab" aria-controls="medicine-panel-registration" aria-selected="false" tabindex="-1" data-tab="registration" class="flex min-h-12 shrink-0 items-center gap-2 rounded-t-xl border-b-2 px-4 py-3 text-sm font-semibold transition-colors border-transparent text-slate-500 hover:text-slate-800"><span class="text-xs opacity-60">02</span><span>Đăng ký & sản xuất</span></button>
                    <button type="button" id="medicine-tab-classification" role="tab" aria-controls="medicine-panel-classification" aria-selected="false" tabindex="-1" data-tab="classification" class="flex min-h-12 shrink-0 items-center gap-2 rounded-t-xl border-b-2 px-4 py-3 text-sm font-semibold transition-colors border-transparent text-slate-500 hover:text-slate-800"><span class="text-xs opacity-60">03</span><span>Phân loại & quản lý</span></button>
                    <button type="button" id="medicine-tab-pricing" role="tab" aria-controls="medicine-panel-pricing" aria-selected="false" tabindex="-1" data-tab="pricing" class="flex min-h-12 shrink-0 items-center gap-2 rounded-t-xl border-b-2 px-4 py-3 text-sm font-semibold transition-colors border-transparent text-slate-500 hover:text-slate-800"><span class="text-xs opacity-60">04</span><span>Giá & ghi chú</span></button>
                </nav>
            </div>
            <section id="medicine-panel-identity" role="tabpanel" aria-labelledby="medicine-tab-identity" class="space-y-6 p-5 sm:p-7">
                <div><h2 class="text-lg font-bold text-slate-900">Thông tin định danh</h2><p class="mt-1 text-sm text-slate-500">Tên thuốc, hoạt chất và đặc điểm sử dụng.</p></div>
                <div class="grid gap-5 sm:grid-cols-2"><label class="block space-y-1.5"><span class="text-sm font-semibold text-slate-700">Tên thuốc <span class="text-rose-600">*</span></span><input name="name" type="text" required  value="{{ old('name', $medicine?->name) }}" class="w-full min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-indigo-500 focus:ring-indigo-500">@error('name')<span class="block text-xs text-rose-700">{{ $message }}</span>@enderror</label>
<label class="block space-y-1.5"><span class="text-sm font-semibold text-slate-700">Loại sản phẩm <span class="text-rose-600">*</span></span><select name="product_type" required class="w-full min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-indigo-500 focus:ring-indigo-500">@foreach(\Modules\Pharma\Models\Medicine::productTypeOptions() as $value => $optionLabel)<option value="{{ $value }}" @selected(old('product_type', $medicine?->product_type ?? \Modules\Pharma\Models\Medicine::PRODUCT_TYPE_MODERN) === $value)>{{ $optionLabel }}</option>@endforeach</select>@error('product_type')<span class="block text-xs text-rose-700">{{ $message }}</span>@enderror</label>
<label class="block space-y-1.5"><span class="text-sm font-semibold text-slate-700">Hoạt chất</span><input name="active_ingredients" type="text"   value="{{ old('active_ingredients', $medicine?->active_ingredients) }}" class="w-full min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-indigo-500 focus:ring-indigo-500">@error('active_ingredients')<span class="block text-xs text-rose-700">{{ $message }}</span>@enderror</label>
<label class="block space-y-1.5"><span class="text-sm font-semibold text-slate-700">Nồng độ / Hàm lượng</span><input name="concentration" type="text"   value="{{ old('concentration', $medicine?->concentration) }}" class="w-full min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-indigo-500 focus:ring-indigo-500">@error('concentration')<span class="block text-xs text-rose-700">{{ $message }}</span>@enderror</label>
<label class="block space-y-1.5"><span class="text-sm font-semibold text-slate-700">Dạng bào chế</span><input name="dosage_form" type="text"   value="{{ old('dosage_form', $medicine?->dosage_form) }}" class="w-full min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-indigo-500 focus:ring-indigo-500">@error('dosage_form')<span class="block text-xs text-rose-700">{{ $message }}</span>@enderror</label>
<label class="block space-y-1.5"><span class="text-sm font-semibold text-slate-700">Đường dùng</span><input name="route_of_administration" type="text"   value="{{ old('route_of_administration', $medicine?->route_of_administration) }}" class="w-full min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-indigo-500 focus:ring-indigo-500">@error('route_of_administration')<span class="block text-xs text-rose-700">{{ $message }}</span>@enderror</label>
<label class="block space-y-1.5"><span class="text-sm font-semibold text-slate-700">Đơn vị tính</span><input name="unit" type="text"   value="{{ old('unit', $medicine?->unit) }}" class="w-full min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-indigo-500 focus:ring-indigo-500">@error('unit')<span class="block text-xs text-rose-700">{{ $message }}</span>@enderror</label>
<label class="block space-y-1.5"><span class="text-sm font-semibold text-slate-700">Quy cách đóng gói</span><input name="packaging_specification" type="text"   value="{{ old('packaging_specification', $medicine?->packaging_specification) }}" class="w-full min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-indigo-500 focus:ring-indigo-500">@error('packaging_specification')<span class="block text-xs text-rose-700">{{ $message }}</span>@enderror</label>
<label class="block space-y-1.5"><span class="text-sm font-semibold text-slate-700">Hạn dùng</span><input name="shelf_life" type="text"   value="{{ old('shelf_life', $medicine?->shelf_life) }}" class="w-full min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-indigo-500 focus:ring-indigo-500">@error('shelf_life')<span class="block text-xs text-rose-700">{{ $message }}</span>@enderror</label></div>
            </section>
            <section id="medicine-panel-registration" role="tabpanel" aria-labelledby="medicine-tab-registration" hidden class="space-y-6 p-5 sm:p-7">
                <div><h2 class="text-lg font-bold text-slate-900">Đăng ký & sản xuất</h2><p class="mt-1 text-sm text-slate-500">Giấy phép lưu hành, doanh nghiệp và thời hạn hiệu lực.</p></div>
                <div class="grid gap-5 sm:grid-cols-2"><label class="block space-y-1.5"><span class="text-sm font-semibold text-slate-700">Giấy phép lưu hành</span><input name="registration_number" type="text"   value="{{ old('registration_number', $medicine?->registration_number) }}" class="w-full min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-indigo-500 focus:ring-indigo-500">@error('registration_number')<span class="block text-xs text-rose-700">{{ $message }}</span>@enderror</label>
<label class="block space-y-1.5"><span class="text-sm font-semibold text-slate-700">Cơ sở đăng ký</span><input name="registered_company" type="text"   value="{{ old('registered_company', $medicine?->registered_company) }}" class="w-full min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-indigo-500 focus:ring-indigo-500">@error('registered_company')<span class="block text-xs text-rose-700">{{ $message }}</span>@enderror</label>
<label class="block space-y-1.5"><span class="text-sm font-semibold text-slate-700">Cơ sở sản xuất</span><input name="manufacturing_company" type="text"   value="{{ old('manufacturing_company', $medicine?->manufacturing_company) }}" class="w-full min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-indigo-500 focus:ring-indigo-500">@error('manufacturing_company')<span class="block text-xs text-rose-700">{{ $message }}</span>@enderror</label>
<label class="block space-y-1.5"><span class="text-sm font-semibold text-slate-700">Nước sản xuất</span><input name="manufacturing_country" type="text"   value="{{ old('manufacturing_country', $medicine?->manufacturing_country) }}" class="w-full min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-indigo-500 focus:ring-indigo-500">@error('manufacturing_country')<span class="block text-xs text-rose-700">{{ $message }}</span>@enderror</label>
<div class="block space-y-1.5"><x-pwa-date label="Hiệu lực Visa" name="visa_validity_date" :value="old('visa_validity_date', $medicine?->visa_validity_date ? \Illuminate\Support\Carbon::parse($medicine->visa_validity_date)->format('Y-m-d') : '')" class="min-h-11" />@error('visa_validity_date')<span class="block text-xs text-rose-700">{{ $message }}</span>@enderror</div>
<div class="block space-y-1.5"><x-pwa-date label="Ngày GMP" name="gmp_certification_date" :value="old('gmp_certification_date', $medicine?->gmp_certification_date ? \Illuminate\Support\Carbon::parse($medicine->gmp_certification_date)->format('Y-m-d') : '')" class="min-h-11" />@error('gmp_certification_date')<span class="block text-xs text-rose-700">{{ $message }}</span>@enderror</div></div>
            </section>
            <section id="medicine-panel-classification" role="tabpanel" aria-labelledby="medicine-tab-classification" hidden class="space-y-6 p-5 sm:p-7">
                <div><h2 class="text-lg font-bold text-slate-900">Phân loại & quản lý</h2><p class="mt-1 text-sm text-slate-500">Thông tư, nhóm điều trị và danh mục chuyên môn.</p></div>
                <div class="grid gap-5 sm:grid-cols-2"><label class="block space-y-1.5"><span class="text-sm font-semibold text-slate-700">STT thông tư</span><input name="circular_order_number" type="text"   value="{{ old('circular_order_number', $medicine?->circular_order_number) }}" class="w-full min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-indigo-500 focus:ring-indigo-500">@error('circular_order_number')<span class="block text-xs text-rose-700">{{ $message }}</span>@enderror</label>
<label class="block space-y-1.5"><span class="text-sm font-semibold text-slate-700">Nhóm thuốc theo thông tư</span><input name="circular_group" type="text"   value="{{ old('circular_group', $medicine?->circular_group) }}" class="w-full min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-indigo-500 focus:ring-indigo-500">@error('circular_group')<span class="block text-xs text-rose-700">{{ $message }}</span>@enderror</label>
<label class="block space-y-1.5"><span class="text-sm font-semibold text-slate-700">Nhóm điều trị</span><input name="therapeutic_group" type="text"   value="{{ old('therapeutic_group', $medicine?->therapeutic_group) }}" class="w-full min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-indigo-500 focus:ring-indigo-500">@error('therapeutic_group')<span class="block text-xs text-rose-700">{{ $message }}</span>@enderror</label></div>
                <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2"><h3 class="font-bold text-slate-900">Danh mục thuốc</h3><span class="text-xs text-slate-500">Có thể chọn nhiều danh mục</span></div>
                    <div class="grid max-h-64 gap-2 overflow-y-auto rounded-xl border border-slate-200 bg-white p-3 sm:grid-cols-2">
                        @foreach($categories as $category)
                            <label class="flex min-h-10 items-center gap-3 rounded-lg px-2 text-sm text-slate-700 hover:bg-indigo-50"><input type="checkbox" name="category_ids[]" value="{{ $category->id }}" @checked(in_array((int) $category->id, array_map('intval', old('category_ids', $selectedCategories)))) class="h-4 w-4 rounded border-slate-300 text-indigo-600"><span>{{ $category->parent_id ? '↳ ' : '' }}{{ $category->name }}</span></label>
                        @endforeach
                    </div>
                    @error('category_ids')<p class="mt-2 text-xs text-rose-700">{{ $message }}</p>@enderror
                </div>
                <label class="flex min-h-12 items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 text-sm font-semibold text-slate-800"><input type="checkbox" name="is_special_control" value="1" @checked(old('is_special_control', $medicine?->is_special_control ?? false)) class="h-4 w-4 rounded">Thuốc kiểm soát đặc biệt</label>
            </section>
            <section id="medicine-panel-pricing" role="tabpanel" aria-labelledby="medicine-tab-pricing" hidden class="space-y-6 p-5 sm:p-7">
                <div><h2 class="text-lg font-bold text-slate-900">Giá & ghi chú</h2><p class="mt-1 text-sm text-slate-500">Thông tin giá kê khai và ghi chú nội bộ.</p></div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <label class="block space-y-1.5"><span class="text-sm font-semibold text-slate-700">Giá kê khai (VNĐ)</span>
                        <input name="declared_price" data-vnd-price="declared" type="text" inputmode="numeric" autocomplete="off" value="{{ old('declared_price', $medicine?->declared_price !== null ? number_format((float) $medicine->declared_price, 0, ',', '.') : '') }}" class="w-full min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('declared_price')<span class="block text-xs text-rose-700">{{ $message }}</span>@enderror
                    </label>
                    <label class="block space-y-1.5"><span class="text-sm font-semibold text-slate-700">Giá bán (VNĐ)</span>
                        <input name="selling_price" data-vnd-price="selling" type="text" inputmode="numeric" autocomplete="off" value="{{ old('selling_price', $medicine?->selling_price !== null ? number_format((float) $medicine->selling_price, 0, ',', '.') : ($medicine?->declared_price !== null ? number_format((float) $medicine->declared_price, 0, ',', '.') : '')) }}" class="w-full min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('selling_price')<span class="block text-xs text-rose-700">{{ $message }}</span>@enderror
                        <span class="block text-xs text-slate-500">Mặc định bằng giá kê khai; không được cao hơn giá kê khai.</span>
                    </label>
                </div>
                <label class="block space-y-2"><span class="text-sm font-semibold text-slate-700">Ghi chú Medicine Master</span><textarea name="notes" rows="6" class="w-full rounded-xl border border-slate-300 p-3 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Thông tin bổ sung về thuốc...">{{ old('notes', $medicine?->notes) }}</textarea></label>
            </section>
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-7">
                <span class="text-xs text-slate-500">Mục <span data-tab-counter>1</span>/4</span>
                <div class="flex gap-2">
                    <button type="button" data-tab-previous hidden class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold">Quay lại</button>
                    <button type="button" data-tab-next class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Tiếp theo →</button>
                </div>
            </div>
        </div>
        <div class="sticky bottom-0 z-20 mt-5 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white/95 px-4 py-3 shadow-lg backdrop-blur">
            <span class="hidden text-xs text-slate-500 sm:block">* Trường bắt buộc · Dữ liệu được kiểm tra khi lưu</span>
            <div class="ml-auto flex items-center gap-3">
                <a href="{{ route('client.pharma.products') }}" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">Hủy</a>
                <button type="submit" class="rounded-xl bg-indigo-700 px-6 py-3 text-sm font-bold text-white shadow-sm hover:bg-indigo-800">{{ $medicine ? 'Lưu thay đổi' : 'Thêm thuốc' }}</button>
            </div>
        </div>
    </form>
</div>
<script>
(function () {
    function initializeMedicineTabs() {
        const root = document.querySelector('[data-medicine-tabs]');
        if (!root || root.dataset.initialized === 'true') return;
        root.dataset.initialized = 'true';
        const ids = ['identity', 'registration', 'classification', 'pricing'];
        const buttons = ids.map(id => document.getElementById('medicine-tab-' + id));
        const panels = ids.map(id => document.getElementById('medicine-panel-' + id));
        const counter = document.querySelector('[data-tab-counter]');
        const previous = document.querySelector('[data-tab-previous]');
        const next = document.querySelector('[data-tab-next]');
        let current = 0;
        function activate(index, focus) {
            current = Math.max(0, Math.min(ids.length - 1, index));
            ids.forEach((id, i) => {
                const active = i === current;
                panels[i].hidden = !active;
                buttons[i].setAttribute('aria-selected', String(active));
                buttons[i].tabIndex = active ? 0 : -1;
                buttons[i].classList.toggle('border-indigo-600', active);
                buttons[i].classList.toggle('bg-white', active);
                buttons[i].classList.toggle('text-indigo-700', active);
                buttons[i].classList.toggle('border-transparent', !active);
                buttons[i].classList.toggle('text-slate-500', !active);
            });
            counter.textContent = String(current + 1);
            previous.hidden = current === 0;
            next.hidden = current === ids.length - 1;
            if (focus) buttons[current].focus();
        }
        buttons.forEach((button, index) => {
            button.addEventListener('click', () => activate(index, false));
            button.addEventListener('keydown', event => {
                if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') {
                    event.preventDefault();
                    activate((index + (event.key === 'ArrowRight' ? 1 : ids.length - 1)) % ids.length, true);
                }
            });
        });
        previous.addEventListener('click', () => activate(current - 1, true));
        next.addEventListener('click', () => activate(current + 1, true));
        const form = root.closest('form');
        const declaredPrice = form.querySelector('[data-vnd-price="declared"]');
        const sellingPrice = form.querySelector('[data-vnd-price="selling"]');
        const digits = value => value.replace(/[^0-9]/g, '');
        const formatVnd = value => digits(value).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        let sellingTouched = Boolean(sellingPrice.value && digits(sellingPrice.value) !== digits(declaredPrice.value));
        [declaredPrice, sellingPrice].forEach(input => {
            input.addEventListener('input', () => {
                input.value = formatVnd(input.value);
                if (input === sellingPrice) sellingTouched = true;
                if (input === declaredPrice && !sellingTouched) sellingPrice.value = input.value;
                const declared = digits(declaredPrice.value);
                const selling = digits(sellingPrice.value);
                sellingPrice.setCustomValidity(selling && (!declared || Number(selling) > Number(declared))
                    ? 'Giá bán không được lớn hơn giá kê khai.' : '');
            });
            input.value = formatVnd(input.value);
        });
        form.addEventListener('invalid', event => {
            const panel = event.target.closest('[role="tabpanel"]');
            if (!panel) return;
            const index = ids.indexOf(panel.id.replace('medicine-panel-', ''));
            if (index >= 0) activate(index, false);
        }, true);
        form.addEventListener('submit', event => {
            const invalid = Array.from(form.querySelectorAll('input,select,textarea')).find(field => !field.checkValidity());
            if (invalid) {
                const panel = invalid.closest('[role="tabpanel"]');
                if (panel) activate(ids.indexOf(panel.id.replace('medicine-panel-', '')), false);
                event.preventDefault();
                invalid.focus();
                invalid.reportValidity();
            }
        });
        activate(0, false);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeMedicineTabs);
    } else {
        initializeMedicineTabs();
    }
})();
</script>
@endsection
