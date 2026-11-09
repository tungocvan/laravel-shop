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
    @if(session('success'))<div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>@endif
    @if($errors->any())<div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>@endif
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
    @if($medicines->isNotEmpty() && $medicines->every(fn ($line) => $line->source === 'excel') && ($canResetExcel || $canDeleteExcel))
    <section class="rounded-2xl border border-amber-200 bg-amber-50 p-4 sm:p-5" aria-label="Quản lý hồ sơ Excel">
        <h2 class="text-base font-bold text-amber-950">Quản lý hồ sơ import Excel</h2>
        <p class="mt-2 text-sm text-amber-900">Chỉ thực hiện khi TBMT chưa có phiếu xuất kho, hợp đồng hoặc chứng từ liên quan. Máy chủ sẽ kiểm tra lại trước khi thay đổi dữ liệu.</p>
        <div class="mt-4 grid gap-4 lg:grid-cols-2">
            @if($canResetExcel)
            <form method="POST" action="{{ route('client.pharma.kqlcnt-awards.reset-excel-setup', $item->id) }}" class="space-y-3 rounded-xl border border-amber-200 bg-white p-4">
                @csrf
                <p class="text-sm font-semibold text-slate-900">Reset phân bổ và chính sách kinh doanh</p>
                <label class="flex items-start gap-2 text-sm text-slate-700"><input type="checkbox" name="acknowledge" value="1" required class="mt-1"> Tôi xác nhận reset toàn bộ thiết lập của TBMT này.</label>
                <button type="submit" class="min-h-11 rounded-xl bg-amber-700 px-4 py-2 text-sm font-bold text-white">Reset về chưa thiết lập</button>
            </form>
            @endif
            @if($canDeleteExcel)
            <form method="POST" action="{{ route('client.pharma.kqlcnt-awards.delete-excel', $item->id) }}" class="space-y-3 rounded-xl border border-red-200 bg-white p-4">
                @csrf
                @method('DELETE')
                <p class="text-sm font-semibold text-red-900">Xóa vĩnh viễn hồ sơ Excel</p>
                <p class="text-xs text-red-700">Chỉ xóa được sau khi reset hoàn tất. Không thể khôi phục bằng giao diện.</p>
                <label class="block text-sm text-slate-700">Nhập chính xác mã TBMT để xác nhận
                    <input type="text" name="confirm_notice" required autocomplete="off" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 px-3" placeholder="{{ $item->notify_no }}">
                </label>
                <label class="flex items-start gap-2 text-sm text-slate-700"><input type="checkbox" name="acknowledge" value="1" required class="mt-1"> Tôi hiểu thao tác này xóa vĩnh viễn dữ liệu Excel.</label>
                <button type="submit" class="min-h-11 rounded-xl bg-red-700 px-4 py-2 text-sm font-bold text-white">Xóa vĩnh viễn hồ sơ</button>
            </form>
            @endif
        </div>
    </section>
    @endif
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
