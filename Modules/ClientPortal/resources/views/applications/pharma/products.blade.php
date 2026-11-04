@extends('ClientPortal::layouts.application')

@section('title', $featurePresentation['page_title'] ?? 'Danh mục thuốc')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Danh mục thuốc')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)

@section('content')
<div id="pharma-medicine-catalog" class="min-w-0 space-y-4 overflow-x-hidden">
    <section class="rounded-[2rem] bg-slate-950 px-5 py-6 text-white shadow-sm sm:px-7">
        <a href="{{ route('client.pharma.dashboard') }}" aria-label="Quay lại Không gian làm việc Pharma" class="inline-flex min-h-10 items-center rounded-xl px-1 text-sm font-bold text-slate-300 transition hover:text-white active:scale-[0.985] motion-reduce:transform-none">← Quay về dashboard</a>
        <p class="mt-2 text-xs font-bold uppercase tracking-[0.16em] text-slate-300">{{ $featurePresentation['eyebrow'] }}</p>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-black tracking-tight sm:text-3xl">{{ $featurePresentation['page_title'] }}</h1>
            <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-bold text-slate-200">{{ number_format($products->total(), 0, ',', '.') }} SKU</span>
        </div>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-300">{{ $featurePresentation['page_description'] }}</p>
    </section>

    @if(session('success'))<div role="status" class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    @if(session('error'))<div role="alert" class="rounded-xl bg-red-50 p-4 text-sm text-red-800">{{ session('error') }}</div>@endif
    <nav aria-label="Công cụ danh mục thuốc" class="flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
        @can('client.pharma.products.create')
            <a href="{{ route('client.pharma.products.create') }}" class="inline-flex min-h-10 items-center rounded-xl bg-indigo-700 px-4 text-sm font-bold text-white hover:bg-indigo-800">+ Thêm thuốc</a>
        @endcan
        @if($canImportMedicines)
            <a href="{{ route('client.pharma.products.import.page') }}" class="inline-flex min-h-10 items-center rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Nhập Excel</a>
            <a href="{{ route('client.pharma.products.import.template') }}" class="inline-flex min-h-10 items-center rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">↓ Tải mẫu nhập</a>
        @endif
        @if($canExportMedicines)
            <a href="{{ route('client.pharma.products.export.roundtrip') }}" class="inline-flex min-h-10 items-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 text-sm font-bold text-emerald-800 hover:bg-emerald-100">↓ Xuất Excel để nhập lại</a>

        @endif
        @if($canConfigureMedicineExcel)<a href="{{ route('client.pharma.products.excel-config') }}" class="inline-flex min-h-10 items-center rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">⚙ Cấu hình Excel</a>@endif
    </nav>
    @if($canExportMedicines)
    <details class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <summary class="flex min-h-12 cursor-pointer items-center justify-between px-4 text-sm font-bold text-slate-800">Mẫu xuất Excel <span class="text-xs text-indigo-700">Ẩn / hiện ▾</span></summary>
        <div class="border-t border-slate-100 p-3">
        <form id="medicine-export-form" method="POST" action="{{ route('client.pharma.products.export') }}" class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">
            @csrf
            @foreach(request()->only(['q', 'group', 'product_type', 'special_control', 'hssp', 'supplier_price', 'bid_award', 'filter']) as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <label class="min-w-44 text-xs font-bold text-slate-600">Mẫu xuất Excel
                <select name="profile_id" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 text-sm">
                    <option value="">Mẫu mặc định</option>
                    @foreach($medicineExcelProfiles as $template)
                        <option value="{{ $template['id'] }}">{{ $template['name'] }}{{ $template['is_default'] ? ' ★' : '' }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="inline-flex min-h-11 items-center justify-center self-end rounded-xl bg-slate-900 px-5 py-2 font-bold text-white">Xuất Excel</button>
            <span class="text-xs text-slate-500">Có chọn thuốc: xuất thuốc đã chọn. Không chọn: xuất toàn bộ kết quả lọc.</span>
            <button type="button" data-medicine-share hidden class="min-h-11 rounded-xl border border-slate-300 px-4 py-2 font-bold">Mở / Chia sẻ tệp</button>
            <span data-medicine-export-status role="status" class="text-xs text-slate-600"></span>
        </form>
        </div>
    </details>
    @endif

    <details class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm" @if($search !== '' || $circularGroup !== '' || $filter !== null || $selectedProductType !== '' || $selectedSpecialControl !== '' || $selectedHssp !== '' || $selectedSupplierPrice !== '' || $selectedBidAward !== '') open @endif>
        <summary class="mb-3 flex cursor-pointer items-center justify-between gap-3"><span><strong class="block text-sm text-slate-900">Tìm kiếm & bộ lọc</strong><span class="mt-1 block text-xs text-slate-500">Tìm theo tên, mã thuốc, SKU hoặc giấy phép lưu hành</span></span><span class="text-xs font-bold text-indigo-700">Ẩn / hiện ▾</span></summary>
        <form id="product-search-form" method="GET" action="{{ route('client.pharma.products') }}" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4 xl:items-start">
            <label class="min-w-0 flex-1 sm:col-span-2 xl:col-span-4">
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Tìm thuốc</span>
                <div class="relative"><input id="product-search-input" type="search" name="q" data-pwa-debounced-search="350" data-pwa-search-region="#product-results-region" data-pwa-search-clear="#product-search-clear" value="{{ $search }}" autocomplete="off" placeholder="Tên thuốc, mã thuốc, SKU, hoạt chất, GPLH..." class="w-full rounded-2xl border border-slate-300 px-4 py-3 pr-12 text-sm text-slate-950 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"><x-native-touch id="product-search-clear" type="button" data-pwa-search-clear-button="#product-search-input" class="{{ $search === '' ? 'hidden ' : '' }}absolute right-2 top-2 inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-500" aria-label="Xóa tìm kiếm">×</x-native-touch></div>
                <span class="mt-1.5 block text-xs text-slate-400">Kết quả tự cập nhật khi bạn nhập.</span>
            </label>
            <label class="w-full">
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Nhóm</span>
                <select name="group" class="h-[46px] w-full rounded-2xl border border-slate-300 px-3 text-sm text-slate-950" onchange="this.form.submit()">
                    <option value="">Tất cả nhóm</option>
                    @foreach($circularGroups as $group)
                        <option value="{{ $group }}" @selected($circularGroup === $group)>Nhóm {{ $group }}</option>
                    @endforeach
                </select>
            </label>
            <label class="w-full"><span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Loại sản phẩm</span><select name="product_type" class="h-[46px] w-full rounded-2xl border border-slate-300 px-3 text-sm" onchange="this.form.submit()"><option value="">Tất cả loại</option>@foreach($productTypeOptions as $value => $label)<option value="{{ $value }}" @selected($selectedProductType === (string) $value)>{{ $label }}</option>@endforeach</select></label>
            <label class="w-full"><span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Kiểm soát đặc biệt</span><select name="special_control" class="h-[46px] w-full rounded-2xl border border-slate-300 px-3 text-sm" onchange="this.form.submit()"><option value="">Tất cả</option><option value="yes" @selected($selectedSpecialControl === 'yes')>Có</option><option value="no" @selected($selectedSpecialControl === 'no')>Không</option></select></label>
            <label class="w-full"><span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Hồ sơ sản phẩm (HSSP)</span><select name="hssp" class="h-[46px] w-full rounded-2xl border border-slate-300 bg-white px-3 text-sm text-slate-950" onchange="this.form.submit()"><option value="" @selected($selectedHssp === '')>Tất cả</option><option value="with" @selected($selectedHssp === 'with')>Có HSSP</option><option value="without" @selected($selectedHssp === 'without')>Chưa có HSSP</option></select></label>
            @if($canViewSupplierPricing)
            <label class="w-full"><span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Giá nhà cung cấp</span><select name="supplier_price" class="h-[46px] w-full rounded-2xl border border-slate-300 bg-white px-3 text-sm text-slate-950" onchange="this.form.submit()"><option value="" @selected($selectedSupplierPrice === '')>Tất cả</option><option value="with" @selected($selectedSupplierPrice === 'with')>Có giá NCC</option><option value="without" @selected($selectedSupplierPrice === 'without')>Chưa có giá NCC</option></select></label>
            @endif
            <label class="w-full"><span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Trúng thầu</span><select name="bid_award" class="h-[46px] w-full rounded-2xl border border-slate-300 bg-white px-3 text-sm text-slate-950" onchange="this.form.submit()"><option value="" @selected($selectedBidAward === '')>Tất cả</option><option value="with" @selected($selectedBidAward === 'with')>Đã trúng thầu</option><option value="without" @selected($selectedBidAward === 'without')>Chưa trúng thầu</option></select></label>
            @if($search !== '' || $circularGroup !== '' || $filter !== null || $selectedProductType !== '' || $selectedSpecialControl !== '' || $selectedHssp !== '' || $selectedSupplierPrice !== '' || $selectedBidAward !== '')
                <div class="flex flex-col">
                    <span class="mb-1.5 block select-none text-xs font-bold uppercase tracking-wide text-transparent" aria-hidden="true">Thao tác</span>
                    <a href="{{ route('client.pharma.products') }}" class="inline-flex h-[46px] w-full items-center justify-center whitespace-nowrap rounded-2xl border border-slate-300 px-4 text-sm font-bold text-slate-700 transition hover:border-slate-400 hover:text-slate-950 lg:w-auto">Xóa bộ lọc</a>
                </div>
            @endif
        </form>
    </details>

    @if(session('medicine_import_staged'))
        <div role="status" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            <strong>Excel đã được tiếp nhận vào staging #{{ session('medicine_import_staged.batch_id') }} (chưa ghi vào danh mục).</strong>
            {{ session('medicine_import_staged.total') }} dòng; mới: {{ session('medicine_import_staged.new') }}, cập nhật: {{ session('medicine_import_staged.update') }}.
            Chưa ghi bất kỳ thay đổi nào vào Medicine Master. Cần rà soát và phê duyệt trước khi đồng bộ.
            <a href="{{ route('client.pharma.products.import.batch', session('medicine_import_staged.batch_id')) }}" class="ml-2 font-bold underline">Xem chi tiết staging</a>
        </div>
    @endif
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm">
        <label class="inline-flex min-h-10 cursor-pointer items-center gap-2 font-semibold text-slate-700">
            <input type="checkbox" data-medicine-select-all class="h-4 w-4 rounded border-slate-300 text-blue-600">
            Chọn tất cả thuốc đang hiển thị
        </label>
        <div class="flex items-center gap-3">
            <span class="text-slate-600"><strong data-medicine-selected-count>0</strong> thuốc đã chọn</span>
            <button type="button" data-medicine-clear-selection hidden class="rounded-xl border border-slate-300 px-3 py-2 font-semibold text-slate-700">Bỏ chọn</button>
        </div>
    </div>

    <div id="product-results-region" class="space-y-4">
    @if($masterOnlyMedicines->total() > 0)
    <section class="overflow-hidden rounded-2xl border border-amber-200 bg-white shadow-sm" aria-label="Thuốc chưa có biến thể">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-amber-100 bg-amber-50 px-4 py-3">
            <div><h2 class="text-sm font-extrabold text-amber-950">Thuốc mới · Chưa có SKU / biến thể <span class="ml-1 rounded-full bg-amber-100 px-2 py-1 text-xs">{{ $masterOnlyMedicines->total() }}</span></h2>
            <p class="mt-1 text-xs text-amber-800">Đã lưu vào Medicine Master. Chưa có biến thể nên chưa nằm trong danh sách SKU phía dưới.</p></div>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach($masterOnlyMedicines as $master)
                <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                    <div class="min-w-0">
                        <div class="font-bold text-slate-900">{{ $master->name }}</div>
                        <div class="mt-1 text-xs text-slate-500">{{ $master->medicine_code }} @if($master->registration_number) · GPLH: {{ $master->registration_number }} @endif</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-lg bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-800">Chưa có SKU</span>
                        @can('client.pharma.products.create-variant')
                            <a href="{{ route('client.pharma.products.variants.create', $master->id) }}" class="inline-flex min-h-10 items-center rounded-xl bg-indigo-700 px-3 text-xs font-bold text-white">Tạo SKU</a>
                        @endcan
                        @can('client.pharma.products.edit')
                            <a href="{{ route('client.pharma.products.edit', $master->id) }}" class="inline-flex min-h-10 items-center rounded-xl border border-indigo-200 px-3 text-xs font-bold text-indigo-800">Sửa thuốc</a>
                        @endcan
                        @can('client.pharma.products.delete')
                            <form method="POST" action="{{ route('client.pharma.products.destroy', $master->id) }}" onsubmit="return confirm('Xóa thuốc {{ addslashes($master->medicine_code) }}? Chỉ xóa được khi không có bất kỳ dữ liệu liên kết nào. Thao tác không thể hoàn tác.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex min-h-10 items-center rounded-xl border border-red-200 px-3 text-xs font-bold text-red-700 hover:bg-red-50">Xóa</button>
                            </form>
                        @endcan
                    </div>
                </div>
            @endforeach
        </div>
        @if($masterOnlyMedicines->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $masterOnlyMedicines->links() }}</div>
        @endif
    </section>
    @endif
    <section id="product-mobile-results" class="space-y-3 xl:hidden">
        @forelse($products as $product)
            <article data-product-card class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <label class="mb-3 inline-flex min-h-10 cursor-pointer items-center gap-2 text-xs font-bold text-slate-600">
                    <input type="checkbox" data-medicine-select value="{{ $product->medicineId }}" class="h-4 w-4 rounded border-slate-300 text-blue-600">
                    Chọn thuốc
                </label>
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="mt-1 font-black text-slate-950">{{ $product->brandName }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ $product->activeIngredients ?: '—' }}@if($product->strength) · {{ $product->strength }}@endif</p>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @if($product->hasBidAward)<span class="rounded-full bg-blue-50 px-2 py-1 text-[11px] font-bold text-blue-700 ring-1 ring-inset ring-blue-200">Trúng thầu</span>@endif
                            @if($product->hasProfile)<span class="rounded-full bg-emerald-50 px-2 py-1 text-[11px] font-bold text-emerald-700 ring-1 ring-inset ring-emerald-200">HSSP</span>@endif
                            @if($canViewSupplierPricing && $product->hasSupplierPricing)<span class="rounded-full bg-amber-50 px-2 py-1 text-[11px] font-bold text-amber-800 ring-1 ring-inset ring-amber-200">Giá NCC</span>@endif
                        </div>
                    </div>
                    <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">{{ $product->unit ?: '—' }}</span>
                </div>
                <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                    <div><dt class="text-xs font-bold text-slate-400">GPLH</dt><dd class="mt-1 text-slate-700">{{ $product->registrationNumber ?: '—' }}</dd></div>
                    <div><dt class="text-xs font-bold text-slate-400">Nhóm</dt><dd class="mt-1 text-slate-700">{{ $product->circularGroup ?: '—' }}</dd></div>
                    <div><dt class="text-xs font-bold text-slate-400">Giá kê khai</dt><dd class="mt-1 font-bold tabular-nums text-slate-800">{{ $product->declaredPrice !== null ? number_format($product->declaredPrice, 0, ',', '.') : '—' }}</dd></div>
                    <div><dt class="text-xs font-bold text-slate-400">Dạng bào chế</dt><dd class="mt-1 text-slate-700">{{ $product->dosageForm ?: '—' }}</dd></div>
                    <div class="col-span-2"><dt class="text-xs font-bold text-slate-400">Quy cách</dt><dd class="mt-1 text-slate-700">{{ $product->packaging ?: '—' }}</dd></div>
                    </dl>
                <div class="mt-4 flex justify-end gap-2">
                    @can('client.pharma.products.edit')
                        <a href="{{ route('client.pharma.products.edit', $product->medicineId) }}" class="inline-flex min-h-10 items-center rounded-xl border border-indigo-200 px-3 text-xs font-bold text-indigo-800">Sửa</a>
                    @endcan
                    <a href="{{ route('client.pharma.products.show', ['variant' => $product->variantId]) }}" aria-label="Xem chi tiết {{ $product->brandName }}" title="Xem chi tiết" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 text-slate-600 transition hover:bg-slate-50 hover:text-slate-950">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-5 w-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 12s3.5-6 9.75-6 9.75 6 9.75 6-3.5 6-9.75 6S2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="2.75" stroke-width="1.8"/></svg>
                    </a>
                </div>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">Không tìm thấy thuốc phù hợp.</div>
        @endforelse
    </section>

    <section class="hidden overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm xl:block" aria-label="Bảng danh mục thuốc">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-3">
            <div>
                <h2 class="text-sm font-extrabold text-slate-900">Danh sách thuốc có SKU</h2>
                <p class="mt-0.5 text-xs text-slate-500">Thông tin thuốc, giấy phép lưu hành và giá kê khai</p>
            </div>
            <span class="text-xs font-semibold text-slate-500">Cuộn ngang khi màn hình hẹp để xem đầy đủ cột</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1180px] table-fixed border-collapse text-left text-[13px]">
                <caption class="sr-only">Danh sách thuốc theo tên, nhóm, hoạt chất, giấy phép, đơn vị tính, quy cách và giá</caption>
                <colgroup>
                    <col class="w-[3.5%]">
                    <col class="w-[20%]">
                    <col class="w-[6%]">
                    <col class="w-[19%]">
                    <col class="w-[13%]">
                    <col class="w-[7%]">
                    <col class="w-[17%]">
                    <col class="w-[9.5%]">
                    <col class="w-[5%]">
                </colgroup>
                <thead class="border-b border-slate-200 bg-slate-50/90 text-[11px] font-extrabold uppercase tracking-[0.035em] text-slate-600">
                    <tr>
                        <th scope="col" class="px-3 py-3.5"><span class="sr-only">Chọn thuốc</span></th>
                        <th scope="col" class="px-3 py-3.5">Tên thuốc</th>
                        <th scope="col" class="px-2 py-3.5 text-center">Nhóm</th>
                        <th scope="col" class="px-3 py-3.5">Hoạt chất <span class="block font-medium normal-case tracking-normal text-slate-400">Hàm lượng</span></th>
                        <th scope="col" class="px-3 py-3.5">GPLH</th>
                        <th scope="col" class="px-2 py-3.5">ĐVT</th>
                        <th scope="col" class="px-3 py-3.5">Quy cách đóng gói</th>
                        <th scope="col" class="px-3 py-3.5 text-right">Giá kê khai</th>
                        <th scope="col" class="px-2 py-3.5 text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="product-desktop-results" class="divide-y divide-slate-100">
                    @forelse($products as $product)
                        <tr data-product-row class="group align-top transition-colors hover:bg-indigo-50/40">
                            <td class="px-3 py-4"><input type="checkbox" data-medicine-select value="{{ $product->medicineId }}" aria-label="Chọn thuốc {{ $product->brandName }}" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-blue-600"></td>
                            <td class="px-3 py-4">
                                <a href="{{ route('client.pharma.products.show', ['variant' => $product->variantId]) }}" class="block break-words font-extrabold leading-5 text-slate-950 hover:text-indigo-700 hover:underline">{{ $product->brandName }}</a>
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @if($product->hasBidAward)<span class="rounded-md bg-blue-50 px-1.5 py-0.5 text-[10px] font-bold text-blue-700 ring-1 ring-inset ring-blue-200">Trúng thầu</span>@endif
                                    @if($product->hasProfile)<span class="rounded-md bg-emerald-50 px-1.5 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-inset ring-emerald-200">HSSP</span>@endif
                                    @if($canViewSupplierPricing && $product->hasSupplierPricing)<span class="rounded-md bg-amber-50 px-1.5 py-0.5 text-[10px] font-bold text-amber-800 ring-1 ring-inset ring-amber-200">Giá NCC</span>@endif
                                </div>
                            </td>
                            <td class="px-2 py-4 text-center"><span class="inline-flex min-w-7 justify-center rounded-lg bg-slate-100 px-2 py-1 font-semibold text-slate-700">{{ $product->circularGroup ?: '—' }}</span></td>
                            <td class="px-3 py-4 leading-5 text-slate-700">
                                <div class="break-words">{{ $product->activeIngredients ?: '—' }}</div>
                                @if($product->strength)<div class="mt-1 text-xs font-semibold text-slate-500">{{ $product->strength }}</div>@endif
                            </td>
                            <td class="break-all px-3 py-4 font-medium leading-5 text-slate-600">{{ $product->registrationNumber ?: '—' }}</td>
                            <td class="break-words px-2 py-4 text-slate-600">{{ $product->unit ?: '—' }}</td>
                            <td class="break-words px-3 py-4 leading-5 text-slate-600">{{ $product->packaging ?: '—' }}</td>
                            <td class="px-3 py-4 text-right font-extrabold tabular-nums text-slate-900">{{ $product->declaredPrice !== null ? number_format($product->declaredPrice, 0, ',', '.') : '—' }}</td>
                            <td class="px-2 py-3.5">
                                <details data-medicine-actions class="relative">
                                    <summary aria-label="Thao tác cho {{ $product->brandName }}" class="flex min-h-9 cursor-pointer list-none items-center justify-center rounded-lg border border-slate-200 bg-white px-2 font-extrabold text-slate-700 hover:border-indigo-300 hover:text-indigo-700">⋯</summary>
                                    <div data-medicine-actions-menu class="absolute right-0 z-20 mt-1 w-44 rounded-xl border border-slate-200 bg-white p-1 shadow-xl">
                                        <a href="{{ route('client.pharma.products.show', ['variant' => $product->variantId]) }}" class="block rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Xem chi tiết</a>
                                        @can('client.pharma.products.edit')
                                            <a href="{{ route('client.pharma.products.edit', $product->medicineId) }}" class="block rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Sửa thuốc</a>
                                        @endcan
                                                                                @can('client.pharma.products.hssp.view')
                                            <a href="{{ route('client.pharma.products.hssp', $product->medicineId) }}" class="block rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Tạo / Xem HSSP</a>
                                        @endcan
                                        @can('client.pharma.products.supplier-pricing')
                                            <a href="{{ route('client.pharma.products.supplier-conditions', $product->medicineId) }}" class="block rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Điều kiện NCC</a>
                                        @endcan
                                        <div class="my-1 border-t border-slate-100"></div>
                                        <span title="Thuốc đã có SKU nên không thể xóa. Chỉ thuốc chưa có bất kỳ liên kết nào mới được xóa." aria-disabled="true" class="block cursor-not-allowed rounded-lg px-3 py-2 text-xs font-semibold text-slate-400">Xóa thuốc · Đã có SKU</span>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-5 py-10 text-center text-slate-500">Không tìm thấy thuốc phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if($products->hasMorePages())
        <div id="product-load-more-wrap" class="pt-1 text-center">
            <a data-pwa-load-more data-pwa-load-more-targets="#product-mobile-results::[data-product-card]|#product-desktop-results::[data-product-row]" data-pwa-load-more-wrap="#product-load-more-wrap" href="{{ $products->nextPageUrl() }}" class="inline-flex min-h-11 items-center justify-center rounded-2xl border border-slate-300 bg-white px-6 text-sm font-black text-slate-700">Xem thêm</a>
        </div>
    @endif
    </div>
</div>
@endsection

@push('application-scripts')
<script>
(() => {
    const root = document.getElementById('pharma-medicine-catalog');
    if (!root) return;

    const selected = new Set();
    const selectAll = root.querySelector('[data-medicine-select-all]');
    const count = root.querySelector('[data-medicine-selected-count]');
    const clear = root.querySelector('[data-medicine-clear-selection]');
    const visibleIds = () => new Set(Array.from(root.querySelectorAll('[data-medicine-select]'), input => input.value));

    const sync = () => {
        root.querySelectorAll('[data-medicine-select]').forEach(input => {
            input.checked = selected.has(input.value);
        });
        const visible = visibleIds();
        const matched = [...visible].filter(id => selected.has(id)).length;
        selectAll.checked = visible.size > 0 && matched === visible.size;
        selectAll.indeterminate = matched > 0 && matched < visible.size;
        selectAll.disabled = visible.size === 0;
        count.textContent = String(selected.size);
        clear.hidden = selected.size === 0;
    };

    root.addEventListener('change', event => {
        const input = event.target;
        if (!(input instanceof HTMLInputElement)) return;
        if (input.matches('[data-medicine-select]')) {
            if (input.checked) selected.add(input.value);
            else selected.delete(input.value);
        } else if (input.matches('[data-medicine-select-all]')) {
            for (const id of visibleIds()) {
                if (input.checked) selected.add(id);
                else selected.delete(id);
            }
        } else return;
        sync();
    });

    clear.addEventListener('click', () => {
        selected.clear();
        sync();
    });

    const exportForm = root.querySelector('#medicine-export-form');
    if (exportForm) {
        const status = exportForm.querySelector('[data-medicine-export-status]');
        const shareButton = exportForm.querySelector('[data-medicine-share]');
        let preparedFile = null;
        const standalone = () => window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

        exportForm.addEventListener('submit', async event => {
            // Normal browser keeps native file download; installed PWA retains its workspace.
            if (!standalone()) {
                for (const old of exportForm.querySelectorAll('[data-selected-export-id]')) old.remove();
                for (const id of selected) {
                    const field = document.createElement('input');
                    field.type = 'hidden';
                    field.name = 'ids[]';
                    field.value = id;
                    field.dataset.selectedExportId = '1';
                    exportForm.appendChild(field);
                }
                return;
            }
            event.preventDefault();
            preparedFile = null;
            shareButton.hidden = true;
            status.textContent = 'Đang chuẩn bị Excel...';
            const data = new FormData(exportForm);
            for (const id of selected) data.append('ids[]', id);
            try {
                const response = await fetch(exportForm.action, {
                    method: 'POST',
                    credentials: 'same-origin',
                    cache: 'no-store',
                    body: data,
                    headers: { 'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' },
                });
                if (!response.ok || !String(response.headers.get('content-type') || '').includes('spreadsheetml')) throw new Error('Không thể xuất Excel (' + response.status + ').');
                const blob = await response.blob();
                preparedFile = new File([blob], 'danh-muc-thuoc.xlsx', {
                    type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                });
                const shareData = { files: [preparedFile] };
                if (!navigator.canShare || !navigator.canShare(shareData)) {
                    status.textContent = 'Thiết bị không hỗ trợ chia sẻ tệp trực tiếp. Vui lòng xuất trên trình duyệt thường.';
                    return;
                }
                shareButton.hidden = false;
                status.textContent = 'Excel đã sẵn sàng.';
            } catch (error) {
                status.textContent = error.message;
            }
        });

        shareButton.addEventListener('click', async () => {
            if (!preparedFile) return;
            try { await navigator.share({ files: [preparedFile] }); }
            catch (error) { if (error.name !== 'AbortError') status.textContent = 'Không thể mở tệp trên thiết bị này.'; }
        });
    }

    // Only one row menu may be open. Port the menu to <body> to avoid clipping
    // inside the horizontally scrollable table and overlapping other rows.
    let activeMenu = null;
    const closeActions = () => {
        if (!activeMenu) return;
        const { details, menu } = activeMenu;
        menu.removeAttribute('style');
        details.appendChild(menu);
        details.open = false;
        activeMenu = null;
    };
    root.addEventListener('click', event => {
        const summary = event.target.closest('[data-medicine-actions] > summary');
        if (!summary) return;
        event.preventDefault();
        const details = summary.parentElement;
        if (activeMenu && activeMenu.details === details) { closeActions(); return; }
        closeActions();
        const menu = details.querySelector('[data-medicine-actions-menu]');
        if (!menu) return;
        details.open = true;
        document.body.appendChild(menu);
        const rect = summary.getBoundingClientRect();
        const width = 190;
        const left = Math.max(8, Math.min(rect.right - width, window.innerWidth - width - 8));
        const below = window.innerHeight - rect.bottom >= 180;
        menu.style.cssText = 'position:fixed;z-index:9999;width:' + width + 'px;left:' + left + 'px;' +
            (below ? 'top:' + Math.min(rect.bottom + 4, window.innerHeight - 8) + 'px;' : 'bottom:' + (window.innerHeight - rect.top + 4) + 'px;') +
            'max-height:calc(100vh - 16px);overflow-y:auto;';
        activeMenu = { details, menu };
    });
    document.addEventListener('pointerdown', event => {
        if (activeMenu && !activeMenu.menu.contains(event.target) && !activeMenu.details.contains(event.target)) closeActions();
    });
    document.addEventListener('keydown', event => { if (event.key === 'Escape') closeActions(); });
    window.addEventListener('scroll', closeActions, true);
    window.addEventListener('resize', closeActions);
    root.addEventListener('click', event => {
        if (activeMenu && event.target.closest('[data-medicine-actions-menu] a')) closeActions();
    });

    const results = root.querySelector('#product-results-region');
    if (results) new MutationObserver(sync).observe(results, { childList: true, subtree: true });
    sync();
})();
</script>
@endpush
