@extends('ClientPortal::layouts.application')

@section('title', 'Chi tiết thuốc')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Thông tin sản phẩm')
@section('app-dashboard-route', route('client.pharma.dashboard'))

@section('content')
<div class="mx-auto max-w-6xl space-y-5">
    <a href="{{ route('client.pharma.products') }}" class="inline-flex items-center gap-2 text-sm font-bold text-slate-600 hover:text-slate-950"><span aria-hidden="true">←</span> Danh mục thuốc</a>

    <section class="rounded-[2rem] bg-slate-950 px-5 py-6 text-white shadow-sm sm:px-7">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-300">Product Intelligence</p>
                <h1 class="mt-2 text-2xl font-black tracking-tight sm:text-3xl">{{ $product->brandName }}</h1>
                <p class="mt-2 text-sm leading-6 text-slate-300">{{ $product->activeIngredients ?: 'Chưa có hoạt chất' }}@if($product->strength) · {{ $product->strength }}@endif</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @if($awards->isNotEmpty())<span class="rounded-full bg-blue-500/20 px-3 py-1 text-xs font-bold text-blue-100 ring-1 ring-inset ring-blue-400/40">Trúng thầu</span>@endif
                    @if($profile)<span class="rounded-full bg-emerald-500/20 px-3 py-1 text-xs font-bold text-emerald-100 ring-1 ring-inset ring-emerald-400/40">HSSP</span>@endif
                    @if($supplierPricingVisible && $suppliers->contains(fn ($supplier) => array_key_exists('import_price', $supplier) && $supplier['import_price'] !== null))<span class="rounded-full bg-amber-400/20 px-3 py-1 text-xs font-bold text-amber-100 ring-1 ring-inset ring-amber-300/40">Giá NCC</span>@endif
                </div>
            </div>
            <div class="flex flex-wrap gap-2 text-xs font-bold">
                <span class="rounded-full bg-white/10 px-3 py-1.5">{{ $medicine['medicine_code'] ?: 'Chưa có mã thuốc' }}</span>
                <span class="rounded-full bg-white/10 px-3 py-1.5">{{ $product->registrationNumber ?: 'Chưa có GPLH' }}</span>
            </div>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="mb-5"><p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">01 · Sản phẩm</p><h2 class="mt-1 text-lg font-black text-slate-950">Thông tin cơ bản</h2></div>
        <dl class="grid gap-x-7 gap-y-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach([
                ['Hoạt chất', $product->activeIngredients],
                ['Hàm lượng', $product->strength],
                ['Dạng bào chế', $product->dosageForm],
                ['Đường dùng', $product->route],
                ['Đơn vị tính', $product->unit],
                ['Quy cách', $product->packaging],
                ['Nhà sản xuất', $product->manufacturer],
                ['Nước sản xuất', $medicine['manufacturing_country']],
                ['Công ty đăng ký', $medicine['registered_company']],
                ['Hạn dùng', $medicine['shelf_life']],
                ['Nhóm thông tư', $medicine['circular_group']],
                ['Nhóm điều trị', $medicine['therapeutic_group']],
            ] as [$label, $value])
                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ $label }}</dt><dd class="mt-1.5 text-sm font-semibold leading-6 text-slate-800">{{ $value ?: '—' }}</dd></div>
            @endforeach
        </dl>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="mb-5 flex items-start justify-between gap-3">
            <div><p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">02 · Hồ sơ</p><h2 class="mt-1 text-lg font-black text-slate-950">Hồ sơ sản phẩm</h2></div>
            @if($profile)<span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ $profile['status'] ?: 'Chưa xác định' }}</span>@endif
        </div>
        @if($profile)
            <dl class="grid gap-x-7 gap-y-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach([
                    ['Phiên bản', $profile['version']],
                    ['Nguồn', $profile['source']],
                    ['Hiệu lực từ', $profile['effective_from']?->format('d/m/Y')],
                    ['Hiệu lực đến', $profile['effective_to']?->format('d/m/Y')],
                    ['Xác minh lúc', $profile['verified_at']?->format('d/m/Y H:i')],
                    ['GPLH hiệu lực đến', $medicine['visa_validity_date']?->format('d/m/Y')],
                ] as [$label, $value])
                    <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ $label }}</dt><dd class="mt-1.5 text-sm font-semibold text-slate-800">{{ $value ?: '—' }}</dd></div>
                @endforeach
            </dl>
            @if($profile['link'])<a href="{{ $profile['link'] }}" target="_blank" rel="noopener noreferrer" class="mt-5 inline-flex rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700">Mở hồ sơ ↗</a>@endif
            @if($profile['notes'])<p class="mt-4 rounded-2xl bg-slate-50 p-4 text-sm leading-6 text-slate-600">{{ $profile['notes'] }}</p>@endif
        @else
            <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">Sản phẩm này chưa có hồ sơ hiện hành.</p>
        @endif
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="mb-5"><p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">03 · Thầu</p><h2 class="mt-1 text-lg font-black text-slate-950">Thông tin trúng thầu gần đây</h2></div>
        @forelse($awards as $award)
            <article class="grid gap-4 border-t border-slate-100 py-4 first:border-t-0 first:pt-0 sm:grid-cols-2 lg:grid-cols-4">
                <div><p class="text-xs font-bold text-slate-400">TBMT</p><p class="mt-1 text-sm font-bold text-slate-800">{{ $award['bidding_notice_code'] ?: '—' }}</p></div>
                <div><p class="text-xs font-bold text-slate-400">Chủ đầu tư</p><p class="mt-1 text-sm text-slate-700">{{ $award['investor_name'] ?: '—' }}</p></div>
                <div><p class="text-xs font-bold text-slate-400">Quyết định</p><p class="mt-1 text-sm text-slate-700">{{ $award['decision_number'] ?: '—' }}@if($award['decision_date']) · {{ $award['decision_date']->format('d/m/Y') }}@endif</p></div>
                <div><p class="text-xs font-bold text-slate-400">Đơn giá trúng thầu</p><p class="mt-1 text-sm font-black tabular-nums text-slate-950">{{ $award['unit_price'] !== null ? number_format((float) $award['unit_price'], 0, ',', '.') : '—' }}</p></div>
                <div class="sm:col-span-2"><p class="text-xs font-bold text-slate-400">Đơn vị trúng thầu</p><p class="mt-1 text-sm text-slate-700">{{ $award['winning_company_name'] ?: '—' }}</p></div>
                <div><p class="text-xs font-bold text-slate-400">Số lượng</p><p class="mt-1 text-sm text-slate-700">{{ $award['quantity'] !== null ? number_format((float) $award['quantity'], 0, ',', '.') : '—' }}</p></div>
                <div><p class="text-xs font-bold text-slate-400">Lô</p><p class="mt-1 text-sm text-slate-700">{{ $award['lot_no'] ?: '—' }}</p></div>
            </article>
        @empty
            <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">Chưa có thông tin trúng thầu được liên kết với sản phẩm này.</p>
        @endforelse
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">04 · Nhà cung cấp</p><h2 class="mt-1 text-lg font-black text-slate-950">Thông tin nhà cung cấp</h2></div>
            @if(!$supplierPricingVisible)<p class="text-xs font-semibold text-slate-400">Giá thương mại được bảo vệ theo quyền riêng.</p>@endif
        </div>
        @forelse($suppliers as $supplier)
            <article class="grid gap-4 border-t border-slate-100 py-4 first:border-t-0 first:pt-0 sm:grid-cols-2 {{ $supplierPricingVisible ? 'lg:grid-cols-5' : 'lg:grid-cols-3' }}">
                <div><p class="text-xs font-bold text-slate-400">Nhà cung cấp</p><p class="mt-1 text-sm font-bold text-slate-800">{{ $supplier['supplier_name'] ?: '—' }}</p></div>
                <div><p class="text-xs font-bold text-slate-400">Ngày làm việc</p><p class="mt-1 text-sm text-slate-700">{{ $supplier['working_date']?->format('d/m/Y') ?: '—' }}</p></div>
                <div><p class="text-xs font-bold text-slate-400">Hiệu lực</p><p class="mt-1 text-sm text-slate-700">{{ $supplier['start_date']?->format('d/m/Y') ?: '—' }} → {{ $supplier['end_date']?->format('d/m/Y') ?: '—' }}</p></div>
                @if($supplierPricingVisible)
                    <div><p class="text-xs font-bold text-slate-400">Giá thu NCC</p><p class="mt-1 text-sm font-black tabular-nums text-slate-950">{{ isset($supplier['import_price']) ? number_format((float) $supplier['import_price'], 0, ',', '.') : '—' }}</p></div>
                    <div><p class="text-xs font-bold text-slate-400">Giá xuất HĐ NCC</p><p class="mt-1 text-sm font-black tabular-nums text-slate-950">{{ isset($supplier['invoice_price']) ? number_format((float) $supplier['invoice_price'], 0, ',', '.') : '—' }}</p></div>
                @endif
            </article>
        @empty
            <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">Chưa có nhà cung cấp được liên kết với sản phẩm này.</p>
        @endforelse
    </section>
</div>
@endsection
