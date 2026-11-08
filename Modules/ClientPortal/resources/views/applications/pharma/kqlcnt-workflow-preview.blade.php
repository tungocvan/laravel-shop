@extends('ClientPortal::layouts.application')
@section('title', $mode === 'allocation' ? 'Phân bổ số lượng KQLCNT' : 'Chính sách kinh doanh KQLCNT')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)
@section('content')
<div class="mx-auto w-full max-w-5xl space-y-4 px-1 pb-4 sm:px-3 lg:px-4">
    @include('ClientPortal::applications.pharma.partials.bid-award-workflow-hero', [
        'backUrl' => route('client.pharma.kqlcnt-awards.show', $item->id),
        'heading' => $mode === 'allocation' ? 'Phân bổ số lượng' : 'Thiết lập chính sách kinh doanh',
        'subtitle' => $item->contractor_name ?: 'Chưa cập nhật nhà thầu',
        'reference' => $item->notify_no,
        'eyebrow' => $mode === 'allocation' ? 'Allocation' : 'Commercial policy',
    ])

    <section class="rounded-2xl border border-amber-200 bg-amber-50 p-4 sm:p-5" role="status">
        <h2 class="text-sm font-bold text-amber-950">Chế độ kiểm tra — chưa cho phép lưu</h2>
        <p class="mt-2 text-sm text-amber-900">{{ $readiness['reason'] }}</p>
        <p class="mt-2 text-sm font-semibold text-amber-950">Nguyên nhân chặn nghiệp vụ: {{ $allocationBlockers['reason'] }}</p>
        <p class="mt-1 text-xs text-amber-900">Mã kiểm tra: {{ $allocationBlockers['status'] }} · Dòng Pharma cùng TBMT: {{ $allocationBlockers['legacy_count'] }}</p>
        <p class="mt-2 text-xs text-amber-900">Trạng thái liên kết: {{ $readiness['status'] }} · {{ $readiness['linked_count'] }}/{{ $readiness['canonical_count'] }} dòng. Chỉ kích hoạt nghiệp vụ ghi khi hồ sơ KQLCNT khớp chính xác phạm vi Bid Awards.</p>
    </section>

    @if($mode === 'allocation')
        <details class="rounded-[24px] border border-indigo-200 bg-white shadow-sm" open>
            <summary class="flex min-h-16 cursor-pointer list-none items-center justify-between gap-3 p-4 sm:p-5">
                <div>
                    <p class="text-[11px] font-black uppercase tracking-[.14em] text-indigo-600">Thiết lập chung</p>
                    <h2 class="mt-1 text-lg font-black">Phạm vi & hiệu lực phân bổ</h2>
                    <p class="mt-1 text-xs text-slate-500">Chưa thể lấy phạm vi bệnh viện từ Pharma khi chưa xác minh liên kết.</p>
                </div>
                <span class="rounded-full bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-800">Chưa liên kết</span>
            </summary>
            <div class="space-y-3 border-t border-indigo-100 p-4 sm:p-5">
                <div class="rounded-2xl border border-slate-200 p-4">
                    <p class="font-bold text-slate-900">① Phạm vi & hiệu lực</p>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <label class="text-sm text-slate-600">Tỉnh/Thành<input disabled value="Chưa xác định" class="mt-1 h-11 w-full rounded-xl border border-slate-200 bg-slate-100 px-3 text-slate-500" /></label>
                        <label class="text-sm text-slate-600">Ngày hiệu lực<input disabled value="Chưa xác định" class="mt-1 h-11 w-full rounded-xl border border-slate-200 bg-slate-100 px-3 text-slate-500" /></label>
                    </div>
                </div>
                <div class="rounded-2xl border border-slate-200 p-4">
                    <p class="font-bold text-slate-900">② Chọn cơ sở KCB</p>
                    <input disabled placeholder="Tìm bệnh viện..." class="mt-3 h-11 w-full rounded-xl border border-slate-200 bg-slate-100 px-3 text-sm" />
                    <p class="mt-2 text-xs text-slate-500">Danh sách bệnh viện chỉ được tải từ phạm vi Pharma đã xác minh.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 p-4">
                    <p class="font-bold text-slate-900">③ Kiểm tra & lưu</p>
                    <button type="button" disabled class="mt-3 min-h-12 w-full cursor-not-allowed rounded-2xl bg-slate-300 px-5 text-sm font-black text-slate-600">Lưu thiết lập phân bổ (chưa khả dụng)</button>
                </div>
            </div>
        </details>
    @endif

    <section class="rounded-[24px] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <p class="text-[11px] font-black uppercase tracking-[.14em] text-slate-400">{{ $mode === 'allocation' ? 'Tổng quan phân bổ' : 'Tổng quan chính sách kinh doanh' }}</p>
        <h2 class="mt-1 text-lg font-black">{{ $medicines->count() }} sản phẩm trúng thầu</h2>
        <p class="mt-1 text-xs text-slate-500">Sản phẩm thuộc hồ sơ canonical — dữ liệu phân bổ và chính sách chưa được xác minh.</p>
        <div class="mt-3 grid gap-3 md:grid-cols-2">
            @foreach($medicines as $medicine)
                <article class="rounded-2xl border border-amber-200 bg-amber-50/40 p-4">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <h3 class="font-black text-slate-950">{{ $medicine->medicine_name ?: 'Chưa có tên thuốc' }}</h3>
                        <span class="rounded-full bg-amber-100 px-2 py-1 text-[11px] font-bold text-amber-800">Chưa xác minh</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-600">{{ $medicine->active_ingredient ?: 'Chưa có hoạt chất' }}</p>
                    <div class="mt-3 grid grid-cols-3 gap-2 text-xs">
                        <div class="rounded-xl bg-white p-2"><span class="block text-slate-500">SL trúng thầu</span><b class="mt-1 block">{{ number_format((float) $medicine->quantity, 4, ',', '.') }}</b></div>
                        <div class="rounded-xl bg-white p-2"><span class="block text-slate-500">{{ $mode === 'allocation' ? 'Đã phân bổ' : 'CSKD' }}</span><b class="mt-1 block">—</b></div>
                        <div class="rounded-xl bg-white p-2"><span class="block text-slate-500">{{ $mode === 'allocation' ? 'Còn lại' : 'Trạng thái' }}</span><b class="mt-1 block">—</b></div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    @if($mode === 'allocation')
        <section class="rounded-[24px] border border-slate-200 bg-white shadow-sm">
            <div class="p-4 sm:p-5">
                <p class="text-[11px] font-black uppercase tracking-[.14em] text-slate-400">Bệnh viện nhận phân bổ</p>
                <h2 class="mt-1 text-lg font-black">Chưa có phạm vi bệnh viện được xác minh</h2>
                <input disabled placeholder="Tìm bệnh viện..." class="mt-3 h-11 w-full rounded-xl border border-slate-200 bg-slate-100 px-3 text-sm" />
                <button type="button" disabled class="mt-3 min-h-11 w-full cursor-not-allowed rounded-xl bg-slate-300 px-4 text-sm font-bold text-slate-600">Nhận phân bổ số lượng (chưa khả dụng)</button>
            </div>
        </section>
    @endif

    <details class="rounded-[24px] border border-slate-200 bg-white shadow-sm">
        <summary class="min-h-14 cursor-pointer p-4 font-bold text-slate-950 sm:p-5">Đối soát liên kết KQLCNT → Pharma</summary>
        <div class="border-t border-slate-100 p-4 sm:p-5">
            <p class="text-sm text-slate-600">Chỉ kiểm tra, không tự động liên kết hoặc tạo sản phẩm Pharma.</p>
            <dl class="mt-3 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                <div><dt class="text-slate-500">Đã liên kết</dt><dd class="font-bold">{{ $mappingDiagnostics['linked_count'] }}</dd></div>
                <div><dt class="text-slate-500">Thiếu liên kết</dt><dd class="font-bold">{{ $mappingDiagnostics['missing_count'] }}</dd></div>
                <div><dt class="text-slate-500">Xung đột</dt><dd class="font-bold">{{ $mappingDiagnostics['ambiguous_count'] }}</dd></div>
                <div><dt class="text-slate-500">Ứng viên cần xác minh</dt><dd class="font-bold">{{ $mappingDiagnostics['candidates_count'] }}</dd></div>
            </dl>
            <div class="mt-3 divide-y divide-slate-100">
                @foreach($mappingDiagnostics['lines'] as $line)
                    <div class="flex flex-wrap items-center justify-between gap-2 py-2 text-sm">
                        <span class="min-w-0 break-words text-slate-800">{{ $line['medicine_name'] ?: 'Sản phẩm chưa có tên' }} · #{{ $line['canonical_id'] }}</span>
                        <span class="font-semibold text-slate-700">{{ $line['status'] === 'linked' ? 'Đã liên kết' : ($line['status'] === 'conflict' ? 'Xung đột định danh' : 'Chưa liên kết') }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </details>
</div>
@endsection
