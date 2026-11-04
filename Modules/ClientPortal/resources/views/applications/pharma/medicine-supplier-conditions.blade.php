@extends('ClientPortal::layouts.application')
@section('title', 'Điều kiện nhà cung cấp')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Điều kiện nhà cung cấp')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('content')
<div class="mx-auto max-w-5xl space-y-5">
    <a href="{{ route('client.pharma.products') }}" class="text-sm font-bold text-indigo-700">← Danh mục thuốc</a>
    <header class="rounded-2xl bg-slate-950 p-6 text-white">
        <p class="text-xs font-bold uppercase tracking-wider text-indigo-200">Pharma · Nhà cung cấp</p>
        <h1 class="mt-2 text-2xl font-black">Điều kiện nhà cung cấp</h1>
        <p class="mt-2 text-sm text-slate-200">{{ $medicine->name }} · {{ $medicine->medicine_code }}</p>
    </header>

    <dialog id="supplier-feedback-modal" aria-labelledby="supplier-feedback-title" class="w-[min(92vw,520px)] rounded-2xl border border-slate-200 p-0 shadow-2xl backdrop:bg-slate-950/50">
        <div class="p-6">
            <h2 id="supplier-feedback-title" class="text-lg font-extrabold text-slate-950">Thông báo</h2>
            <ul id="supplier-feedback-messages" class="mt-3 list-disc space-y-2 pl-5 text-sm text-slate-700"></ul>
            <div class="mt-6 flex justify-end"><button type="button" id="supplier-feedback-close" class="rounded-xl bg-indigo-700 px-5 py-2.5 text-sm font-bold text-white">Đã hiểu</button></div>
        </div>
    </dialog>
    @if($errors->any())
        <script type="application/json" id="supplier-server-errors">@json($errors->all(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>
    @endif
    @if(session('success'))
        <script type="application/json" id="supplier-server-success">@json(session('success'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>
    @endif
    @if($canManageConditions)
    <form id="supplier-conditions-form" action="{{ route('client.pharma.products.supplier-conditions.save', $medicine->id) }}" method="POST" enctype="multipart/form-data" novalidate class="space-y-4">
        @csrf
        @if($editing)<input type="hidden" name="tracking_id" value="{{ $editing->id }}">@endif
        <nav class="flex flex-wrap gap-2 rounded-2xl border border-slate-200 bg-white p-2 shadow-sm" aria-label="Các phần điều kiện thương mại">
            @foreach(['supplier'=>'1. Nhà cung cấp & hiệu lực','commercial'=>'2. Điều kiện thương mại','distribution'=>'3. Phạm vi phân phối','commitments'=>'4. Cam kết & hồ sơ'] as $tab=>$title)
                <button type="button" data-supplier-tab="{{ $tab }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}" class="rounded-xl px-4 py-2 text-sm font-bold {{ $loop->first ? 'bg-indigo-700 text-white' : 'text-slate-700' }}">{{ $title }}</button>
            @endforeach
        </nav>
        <section data-supplier-panel="supplier" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-extrabold">Nhà cung cấp & hiệu lực</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-2 block text-sm font-bold" for="supplier-partner-search">Nhà cung cấp <span class="text-red-600">*</span></label>
                    <x-pwa-select-search id="supplier-partner-search" name="partner_id" :selected="old('partner_id', $editing?->partner_id)" placeholder="Chọn nhà cung cấp từ Partner Master" search-placeholder="Tìm tên nhà cung cấp / MST...">
                        <button type="button" data-pwa-select-search-option data-value="" data-label="Chọn nhà cung cấp từ Partner Master" data-search="" class="block w-full rounded-xl px-3 py-3 text-left text-sm hover:bg-slate-100">Chọn nhà cung cấp từ Partner Master</button>
                        @foreach($suppliers as $supplier)
                            <button type="button" data-pwa-select-search-option data-value="{{ $supplier->id }}" data-label="{{ $supplier->name }} · MST {{ $supplier->tax_code }}" data-search="{{ mb_strtolower($supplier->name.' '.($supplier->tax_code ?? '')) }}" class="block w-full rounded-xl px-3 py-3 text-left text-sm hover:bg-slate-100">
                                <strong class="block font-bold text-slate-900">{{ $supplier->name }}</strong>
                                @if($supplier->tax_code)<small class="mt-0.5 block text-xs text-slate-500">MST {{ $supplier->tax_code }}</small>@endif
                            </button>
                        @endforeach
                    </x-pwa-select-search>
                    <p id="supplier-partner-error" role="alert" class="mt-2 hidden text-sm font-semibold text-red-700">Vui lòng chọn nhà cung cấp trước khi lưu.</p>
                </div>
                @foreach(['start_date'=>'Hiệu lực từ','end_date'=>'Hiệu lực đến','working_date'=>'Ngày ghi nhận'] as $key=>$title)
                    <label class="text-sm font-bold">{{ $title }} <input type="date" name="{{ $key }}" value="{{ old($key, $editing?->{$key}?->format('Y-m-d')) }}" class="mt-2 block min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
                @endforeach
                <label class="text-sm font-bold">Trạng thái
                    <select name="status" class="mt-2 block min-h-11 w-full rounded-xl border border-slate-300 px-3">
                        @foreach(['active'=>'Đang hiệu lực','pending'=>'Chờ xử lý','inactive'=>'Ngừng hiệu lực'] as $key=>$title)
                            <option value="{{ $key }}" @selected(old('status', $editing?->status ?? 'active') === $key)>{{ $title }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-sm font-bold sm:col-span-2">Người đại diện NCC <input name="supplier_representative" value="{{ old('supplier_representative', $editing?->supplier_representative) }}" class="mt-2 block min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
            </div>
        </section>
        <section data-supplier-panel="commercial" hidden class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-extrabold">Điều kiện thương mại</h2>
            <p class="mt-2 text-xs text-slate-500">Giá nhập từ nhà cung cấp; giá bán cho khách hàng được quản lý tại Bảng giá.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                @foreach(['import_price'=>'Giá nhập NCC *','selling_price'=>'Giá bán tham khảo','invoice_price'=>'Giá hóa đơn'] as $key=>$title)
                    <label class="text-sm font-bold">{{ $title }} (đ) <input type="number" min="0" step="0.01" name="{{ $key }}" value="{{ old($key, $editing?->{$key}) }}" class="mt-2 block min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
                @endforeach
            </div>
        </section>
        <section data-supplier-panel="distribution" hidden class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-extrabold">3. Phạm vi được phép bán</h2>
            <p class="mt-1 text-sm text-slate-500">Chọn một phạm vi áp dụng như Admin. Cơ sở khám chữa bệnh lấy từ danh mục chính thức của Pharma.</p>
            @php
                $scope = old('distribution_scope', $editing?->distribution_scope ?? 'all');
                $selectedRegions = old('distribution_regions', $editing?->distribution_regions ?? []);
                $selectedProvinces = old('distribution_provinces', $editing?->distribution_provinces ?? []);
                $selectedFacilities = old('facility_ids', $selectedFacilityIds);
            @endphp
            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                @foreach(['all'=>'Toàn bộ cơ sở','regions'=>'Theo vùng miền','facilities'=>'Chọn từng cơ sở'] as $value=>$label)
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 p-3 text-sm font-semibold">
                        <input type="radio" name="distribution_scope" value="{{ $value }}" @checked($scope === $value) class="text-indigo-600">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            <div data-distribution-mode="regions" class="mt-4 space-y-4" hidden>
                <h3 class="text-sm font-bold">Vùng miền</h3>
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($distributionRegions as $code=>$label)
                        <label class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                            <input type="checkbox" name="distribution_regions[]" value="{{ $code }}" @checked(in_array((string) $code, array_map('strval', $selectedRegions), true)) data-region-checkbox>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <h3 class="text-sm font-bold">Tỉnh/Thành thuộc vùng miền</h3>
                    <p class="mt-1 text-xs text-slate-500">Chỉ hiển thị các tỉnh/thành thuộc vùng đã chọn.</p>
                    <div class="mt-3 space-y-4">
                        @foreach($distributionProvincesByRegion as $regionCode=>$provinceOptions)
                            <div data-province-region="{{ $regionCode }}" hidden>
                                <h4 class="mb-2 text-xs font-bold uppercase text-slate-500">{{ $distributionRegions[$regionCode] ?? $regionCode }}</h4>
                                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                    @foreach($provinceOptions as $provinceCode=>$provinceName)
                                        <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                                            <input type="checkbox" name="distribution_provinces[]" value="{{ $provinceCode }}" @checked(in_array((string) $provinceCode, array_map('strval', $selectedProvinces), true))>
                                            {{ $provinceName }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div data-distribution-mode="facilities" class="mt-4" hidden>
                <h3 class="text-sm font-bold">Chọn cơ sở khám chữa bệnh</h3>
                <p class="mt-1 text-xs text-slate-500">Tìm theo tên, mã CSKCB hoặc tỉnh/thành. Các cơ sở đã chọn luôn được giữ trong danh sách.</p>
                <div class="mt-3 flex gap-2">
                    <input id="facility-search-input" type="search" value="{{ request('facility_search') }}" placeholder="Tìm cơ sở, mã CSKCB hoặc tỉnh/thành..." class="min-h-11 min-w-0 flex-1 rounded-xl border border-slate-300 px-3">
                    <button type="button" id="facility-search-button" class="rounded-xl border border-indigo-300 px-4 text-sm font-bold text-indigo-700">Tìm</button>
                </div>
                <div class="mt-3 max-h-72 overflow-y-auto divide-y divide-slate-100 rounded-xl border border-slate-200">
                    @forelse($facilities as $facility)
                        <label class="flex items-start gap-3 p-3 hover:bg-slate-50">
                            <input type="checkbox" name="facility_ids[]" value="{{ $facility->id }}" @checked(in_array((string) $facility->id, array_map('strval', $selectedFacilities), true)) class="mt-1">
                            <span><span class="block text-sm font-semibold">{{ $facility->facility_name }}</span><span class="text-xs text-slate-500">{{ $facility->external_id }} · {{ $facility->province_name ?: 'Chưa có tỉnh/thành' }}</span></span>
                        </label>
                    @empty
                        <p class="p-4 text-sm text-slate-500">Không tìm thấy cơ sở phù hợp.</p>
                    @endforelse
                </div>
            </div>
        </section>
        <section data-supplier-panel="commitments" hidden class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-extrabold">Cam kết & hồ sơ</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <label class="text-sm font-bold">Số lượng cam kết <input type="number" min="0" step="0.01" name="committed_quantity" value="{{ old('committed_quantity', $editing?->committed_quantity) }}" class="mt-2 block min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
                <label class="text-sm font-bold">Đơn vị <input name="unit" value="{{ old('unit', $editing?->unit) }}" class="mt-2 block min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
                <label class="text-sm font-bold">Tiền cọc (đ) <input type="number" min="0" step="0.01" name="deposit_amount" value="{{ old('deposit_amount', $editing?->deposit_amount) }}" class="mt-2 block min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
                <label class="text-sm font-bold">Hợp đồng (PDF, Word hoặc ảnh; tối đa 10MB) <input type="file" name="contract" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="mt-2 block w-full rounded-xl border border-slate-300 p-3"></label>
                @if($editing?->contract_file_path)<p class="text-xs text-slate-600 sm:col-span-2">Đã có file hợp đồng lưu trong hệ thống. Chọn file mới để thay thế đường dẫn liên kết.</p>@endif
                <label class="text-sm font-bold sm:col-span-2">Ghi chú <textarea name="note" rows="3" class="mt-2 block w-full rounded-xl border border-slate-300 p-3">{{ old('note', $editing?->note) }}</textarea></label>
            </div>
            <div class="mt-5 flex flex-wrap items-center gap-3">
                <button type="submit" class="min-h-11 rounded-xl bg-indigo-700 px-6 font-bold text-white">{{ $editing ? 'Lưu cập nhật điều kiện thương mại' : 'Tạo điều kiện thương mại' }}</button>
                @if($editing)<a href="{{ route('client.pharma.products.supplier-conditions', $medicine->id) }}" class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-bold">Tạo bản ghi mới</a>@endif
            </div>
        </section>
    </form>
    @push('application-scripts')
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const root = document.getElementById('supplier-conditions-form');
        if (!root) return;
        const tabs = Array.from(root.querySelectorAll('[data-supplier-tab]'));
        const panels = Array.from(root.querySelectorAll('[data-supplier-panel]'));
        const show = name => {
            tabs.forEach(button => {
                const active = button.dataset.supplierTab === name;
                button.setAttribute('aria-selected', String(active));
                button.classList.toggle('bg-indigo-700', active);
                button.classList.toggle('text-white', active);
                button.classList.toggle('text-slate-700', !active);
            });
            panels.forEach(panel => { panel.hidden = panel.dataset.supplierPanel !== name; });
        };
        tabs.forEach(button => button.addEventListener('click', () => show(button.dataset.supplierTab)));
        const modal = document.getElementById('supplier-feedback-modal');
        const modalTitle = document.getElementById('supplier-feedback-title');
        const modalMessages = document.getElementById('supplier-feedback-messages');
        const closeModal = document.getElementById('supplier-feedback-close');
        const feedback = (title, messages) => {
            modalTitle.textContent = title;
            modalMessages.replaceChildren();
            messages.forEach(message => {
                const item = document.createElement('li');
                item.textContent = message;
                modalMessages.appendChild(item);
            });
            if (typeof modal.showModal === 'function') modal.showModal();
            else window.alert(title + '\\n' + messages.join('\\n'));
        };
        closeModal?.addEventListener('click', () => modal.close());
        const serverErrors = document.getElementById('supplier-server-errors');
        const serverSuccess = document.getElementById('supplier-server-success');
        if (serverErrors) {
            const errors = JSON.parse(serverErrors.textContent);
            show(errors.some(message => message.includes('Giá nhập')) ? 'commercial' : 'supplier');
            feedback('Không thể lưu điều kiện thương mại', errors);
        } else if (serverSuccess) {
            feedback('Lưu thành công', [JSON.parse(serverSuccess.textContent)]);
        }
        root.addEventListener('submit', event => {
            const issues = [];
            const partner = root.querySelector('[name="partner_id"]');
            const price = root.querySelector('[name="import_price"]');
            let firstTab = null;
            if (!partner?.value) {
                issues.push('Tab 1 - Nhà cung cấp: Vui lòng chọn nhà cung cấp.');
                firstTab = 'supplier';
            }
            if (!price?.value.trim() || !Number.isFinite(Number(price.value)) || Number(price.value) < 0) {
                issues.push('Tab 2 - Điều kiện thương mại: Giá nhập NCC là bắt buộc và phải là số không âm.');
                firstTab ??= 'commercial';
            }
            const start = root.querySelector('[name="start_date"]')?.value;
            const end = root.querySelector('[name="end_date"]')?.value;
            if (start && end && end < start) {
                issues.push('Tab 1 - Hiệu lực: Ngày kết thúc phải bằng hoặc sau ngày bắt đầu.');
                firstTab ??= 'supplier';
            }
            if (issues.length) {
                event.preventDefault();
                show(firstTab);
                feedback('Vui lòng kiểm tra dữ liệu trước khi lưu', issues);
            }
        });
        const scopeInputs = Array.from(root.querySelectorAll('input[name="distribution_scope"]'));
        const refreshDistribution = () => {
            const scope = scopeInputs.find(input => input.checked)?.value ?? 'all';
            root.querySelectorAll('[data-distribution-mode]').forEach(panel => {
                panel.hidden = panel.dataset.distributionMode !== scope;
                panel.querySelectorAll('input').forEach(input => { input.disabled = panel.hidden; });
            });
            const selectedRegions = Array.from(root.querySelectorAll('[data-region-checkbox]:checked')).map(input => input.value);
            root.querySelectorAll('[data-province-region]').forEach(group => {
                group.hidden = !selectedRegions.includes(group.dataset.provinceRegion);
                group.querySelectorAll('input').forEach(input => { input.disabled = group.hidden || scope !== 'regions'; });
            });
        };
        scopeInputs.forEach(input => input.addEventListener('change', refreshDistribution));
        root.querySelectorAll('[data-region-checkbox]').forEach(input => input.addEventListener('change', refreshDistribution));
        refreshDistribution();
        const searchButton = document.getElementById('facility-search-button');
        searchButton?.addEventListener('click', () => {
            const url = new URL(window.location.href);
            url.searchParams.set('facility_search', document.getElementById('facility-search-input').value.trim());
            window.location.assign(url.toString());
        });
        show('supplier');
    });
    </script>
    @endpush
    @else
        <p class="rounded-xl bg-slate-100 p-4 text-sm text-slate-600">Bạn có quyền xem nhưng chưa có quyền thêm hoặc cập nhật điều kiện thương mại.</p>
    @endif

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 p-4"><h2 class="font-extrabold">Các điều kiện đã ghi nhận</h2><p class="mt-1 text-xs text-slate-500">Chỉ tài khoản có quyền xem giá nhà cung cấp mới truy cập được.</p></div>
        <div class="overflow-x-auto"><table class="w-full min-w-[850px] text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-3">Nhà cung cấp</th><th class="p-3">Phạm vi</th><th class="p-3 text-right">Giá nhập</th><th class="p-3 text-right">Giá bán</th><th class="p-3">Hiệu lực</th><th class="p-3">Trạng thái</th>@if($canManageConditions)<th class="p-3">Thao tác</th>@endif</tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($trackings as $tracking)
                    <tr>
                        <td class="p-3 font-bold">{{ $tracking->supplier_name ?: '—' }}</td>
                        <td class="p-3">{{ $tracking->distribution_scope ?: '—' }}</td>
                        <td class="p-3 text-right tabular-nums">{{ $tracking->import_price !== null ? number_format((float) $tracking->import_price, 0, ',', '.') : '—' }}</td>
                        <td class="p-3 text-right tabular-nums">{{ $tracking->selling_price !== null ? number_format((float) $tracking->selling_price, 0, ',', '.') : '—' }}</td>
                        <td class="p-3">{{ $tracking->start_date?->format('d/m/Y') ?? '—' }} – {{ $tracking->end_date?->format('d/m/Y') ?? '—' }}</td>
                        <td class="p-3">{{ $tracking->status ?: '—' }}</td>@if($canManageConditions)<td class="p-3"><a class="font-bold text-indigo-700" href="{{ route('client.pharma.products.supplier-conditions', ['medicine' => $medicine->id, 'tracking' => $tracking->id]) }}">Sửa</a></td>@endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $canManageConditions ? 7 : 6 }}" class="p-8 text-center text-slate-500">Chưa có điều kiện nhà cung cấp cho thuốc này.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        <div class="p-4">{{ $trackings->links() }}</div>
    </section>
</div>
@endsection
