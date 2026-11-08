@extends('ClientPortal::layouts.application')
@section('title', $mode === 'allocation' ? 'Phân bổ số lượng KQLCNT' : 'Chính sách kinh doanh KQLCNT')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)
@section('content')
<div class="min-w-0 space-y-4 pb-6">
    <header class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
        <a href="{{ route('client.pharma.kqlcnt-awards.show', $item->id) }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-indigo-700">← Chi tiết hồ sơ</a>
        <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $presentation['page_title'] }}</p>
        <h1 class="mt-1 text-xl font-extrabold text-slate-950 sm:text-2xl">{{ $mode === 'allocation' ? 'Phân bổ số lượng' : 'Thiết lập chính sách kinh doanh' }}</h1>
        <p class="mt-1 break-words text-sm text-slate-600">TBMT {{ $item->notify_no }} · {{ $item->contractor_name ?: 'Chưa cập nhật nhà thầu' }}</p>
    </header>
    <section class="rounded-2xl border border-amber-200 bg-amber-50 p-4 sm:p-5" role="status">
        <h2 class="text-sm font-bold text-amber-950">Chế độ kiểm tra — chưa cho phép lưu</h2>
        <p class="mt-2 text-sm text-amber-900">{{ $readiness['reason'] }}</p>
        <p class="mt-2 text-xs text-amber-900">Trạng thái liên kết: {{ $readiness['status'] }} · {{ $readiness['linked_count'] }}/{{ $readiness['canonical_count'] }} dòng. Phạm vi bệnh viện hiện còn dùng khóa TBMT chung; chức năng ghi sẽ chỉ được mở sau khi tách an toàn theo nhà thầu và kiểm tra quyền.</p>
    </section>
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="border-b border-slate-100 p-4 sm:p-5">
            <h2 class="text-base font-bold text-slate-950">Sản phẩm thuộc hồ sơ canonical</h2>
            <p class="mt-1 text-xs text-slate-600">Danh sách chỉ đọc từ KQLCNT, không lấy thêm sản phẩm của nhà thầu khác cùng TBMT.</p>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach($medicines as $medicine)
                <article class="flex flex-wrap items-center justify-between gap-3 p-4 sm:px-5">
                    <div class="min-w-0">
                        <p class="break-words text-sm font-bold text-slate-950">{{ $medicine->medicine_name ?: 'Chưa có tên thuốc' }}</p>
                        <p class="mt-1 text-xs text-slate-600">{{ $medicine->active_ingredient ?: 'Chưa có hoạt chất' }}</p>
                    </div>
                    <p class="text-sm font-semibold tabular-nums text-slate-800">SL trúng thầu: {{ number_format((float) $medicine->quantity, 4, ',', '.') }}</p>
                </article>
            @endforeach
        </div>
    </section>
</div>
@endsection
