@extends('ClientPortal::layouts.application')
@section('title', 'Chi tiết TBMT '.$item->notify_no)
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)
@section('content')
@php
    $totalAmount = $medicines->sum(fn ($medicine) => $medicine->amount !== null ? (float) $medicine->amount : ((float) $medicine->quantity * (float) $medicine->winning_price));
    $contractPeriod = $item->contract_period !== null ? $item->contract_period.' '.($item->contract_period_unit ?: 'tháng') : ($item->contract_period_text ?: 'Chưa cập nhật');
@endphp
<div class="min-w-0 space-y-4 pb-6">
    <header class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
        <a href="{{ route('client.pharma.kqlcnt-awards.index', request()->only(['search', 'medicine', 'active', 'page'])) }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-indigo-700">← Theo dõi thầu</a>
        <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Hồ sơ kết quả trúng thầu</p>
        <h1 class="mt-1 break-all text-xl font-extrabold text-slate-950 sm:text-2xl">TBMT {{ $item->notify_no }}</h1>
        <p class="mt-1 break-words text-sm text-slate-600">{{ $item->contractor_name ?: 'Chưa cập nhật nhà thầu' }}</p>
    </header>
    <section class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5" aria-label="Thông tin hồ sơ">
        <h2 class="mb-4 text-base font-bold text-slate-950">Thông tin hồ sơ</h2>
        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach([
                'Nhà thầu' => $item->contractor_name,
                'Chủ đầu tư' => $item->investor_name,
                'Số quyết định' => $item->decision_no,
                'Số hợp đồng' => $item->contract_no,
                'Thời gian thực hiện hợp đồng' => $contractPeriod,
                'Khung thời gian hiệu lực' => $item->effect_frame_period,
            ] as $label => $value)
                <div class="min-w-0">
                    <dt class="text-xs text-slate-500">{{ $label }}</dt>
                    <dd class="mt-1 break-words text-sm font-semibold text-slate-900">{{ $value ?: 'Chưa cập nhật' }}</dd>
                </div>
            @endforeach
            <div><dt class="text-xs text-slate-500">Số tiền trúng thầu</dt><dd class="mt-1 text-lg font-extrabold text-slate-950">{{ number_format($totalAmount, 0, ',', '.') }} đ</dd></div>
            <div><dt class="text-xs text-slate-500">Số lượng sản phẩm</dt><dd class="mt-1 text-lg font-extrabold text-slate-950">{{ $medicines->count() }} sản phẩm</dd></div>
        </dl>
    </section>
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-4 py-4 sm:px-5">
            <h2 class="text-base font-bold text-slate-950">Danh mục thuốc trúng thầu</h2>
            <p class="mt-1 text-xs text-slate-500">Toàn bộ thuốc thuộc TBMT và nhà thầu này.</p>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach($medicines as $medicine)
                <article class="p-4 sm:p-5">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <h3 class="min-w-0 break-words text-sm font-bold text-slate-950">{{ $medicine->medicine_name ?: 'Chưa có tên thuốc' }}</h3>
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $medicine->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $medicine->is_active ? 'Hoạt động' : 'Không hoạt động' }}</span>
                    </div>
                    <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                        @foreach([
                            'Hoạt chất' => $medicine->active_ingredient,
                            'Hàm lượng' => $medicine->concentration,
                            'Quy cách' => $medicine->packaging_spec,
                            'Số lượng' => $medicine->quantity,
                            'Đơn giá trúng thầu' => $medicine->winning_price !== null ? number_format((float) $medicine->winning_price, 0, ',', '.').' đ' : null,
                            'Thành tiền' => $medicine->amount !== null ? number_format((float) $medicine->amount, 0, ',', '.').' đ' : null,
                            'Nhà sản xuất' => $medicine->manufacturer,
                            'Lô thầu' => $medicine->lot_name ?: $medicine->lot_no,
                        ] as $label => $value)
                            <div class="min-w-0"><dt class="text-xs text-slate-500">{{ $label }}</dt><dd class="mt-1 break-words font-medium text-slate-900">{{ $value !== null && $value !== '' ? $value : '—' }}</dd></div>
                        @endforeach
                    </dl>
                </article>
            @endforeach
        </div>
    </section>
</div>
@endsection
