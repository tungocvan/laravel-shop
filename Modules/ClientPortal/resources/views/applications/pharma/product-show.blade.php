@extends('ClientPortal::layouts.application')

@section('title', 'Chi tiết thuốc')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Chi tiết sản phẩm · chỉ đọc')
@section('app-dashboard-route', route('client.pharma.dashboard'))

@section('content')
<div class="mx-auto max-w-5xl space-y-4">
    <div>
        <a href="{{ route('client.pharma.products') }}" class="inline-flex items-center gap-2 text-sm font-bold text-slate-600 hover:text-slate-950">
            <span aria-hidden="true">←</span> Danh mục thuốc
        </a>
    </div>

    <section class="rounded-[2rem] bg-slate-950 px-5 py-6 text-white shadow-sm sm:px-7">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-300">Medicine Detail</p>
        <h1 class="mt-2 text-2xl font-black tracking-tight sm:text-3xl">{{ $product->brandName }}</h1>
        <p class="mt-2 text-sm text-slate-300">{{ $product->activeIngredients ?: 'Chưa có hoạt chất' }}@if($product->strength) · {{ $product->strength }}@endif</p>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @php
                $fields = [
                    ['Mã SKU', $product->sku],
                    ['Giấy phép lưu hành', $product->registrationNumber],
                    ['Dạng bào chế', $product->dosageForm],
                    ['Đường dùng', $product->route],
                    ['Đơn vị tính', $product->unit],
                    ['Quy cách', $product->packaging],
                    ['Nhà sản xuất', $product->manufacturer],
                ];
            @endphp
            @foreach($fields as [$label, $value])
                <div class="{{ $label === 'Nhà sản xuất' ? 'sm:col-span-2 lg:col-span-3' : '' }}">
                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ $label }}</dt>
                    <dd class="mt-1.5 text-sm font-semibold leading-6 text-slate-800">{{ $value ?: '—' }}</dd>
                </div>
            @endforeach
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-slate-50 p-5 text-sm leading-6 text-slate-600">
        Đây là màn hình tra cứu chỉ đọc. Các thao tác sửa Medicine Master, xác minh, import/export và quản trị dữ liệu vẫn thuộc khu vực Admin Pharma.
    </section>
</div>
@endsection
