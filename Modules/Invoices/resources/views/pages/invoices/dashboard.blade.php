@extends('Admin::layouts.master')

@section('title', 'Dashboard hóa đơn')

@section('content')
    @php
        $capabilities = $dashboard->capabilities;
        $invoiceMetrics = $dashboard->metrics['invoices'];
        $classificationMetrics = $dashboard->metrics['classification'] ?? ['available' => false, 'expense_breakdown' => []];
        $pdfMetrics = $dashboard->metrics['pdf'];
        $processing = $dashboard->processing;
        $formatDate = static fn (?string $value): string => $value
            ? \Illuminate\Support\Carbon::parse($value)->timezone(config('app.timezone'))->format('d/m/Y H:i')
            : 'Chưa có dữ liệu';
        $formatMoney = static fn (float|int $value): string => number_format((float) $value, 0, ',', '.').' đ';
        $invoiceTypeLabels = ['sold' => 'Hóa đơn bán ra', 'purchase' => 'Hóa đơn mua vào', 'unknown' => 'Hóa đơn chưa xác định chiều'];
        $backupStatusLabels = ['running' => 'Đang chạy', 'skipped' => 'Không có file mới', 'success' => 'Hoàn tất', 'failed' => 'Thất bại', 'unknown' => 'Không xác định'];
    @endphp

    <div class="space-y-7">
        <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Invoices operations center</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Dashboard hóa đơn</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Theo dõi vận hành, sức khỏe tích hợp, phân loại dữ liệu mua vào và mở đúng workspace xử lý khi cần.</p>
                <p class="mt-2 text-xs text-slate-500">Cập nhật lúc {{ $formatDate($dashboard->generatedAt) }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($capabilities['create'])<a href="{{ route('admin.invoices.source-data') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-amber-300 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-800 transition hover:bg-amber-100">Phân loại dữ liệu nguồn</a>@endif
                @if ($capabilities['configure'])<a href="{{ route('admin.invoices.backup-restore') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-indigo-300 bg-white px-4 py-2.5 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-50">Backup & Restore</a>@endif
                <a href="{{ route('admin.invoices.hoadon-list') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">Mở danh sách hóa đơn</a>
            </div>
        </header>

        @if (! $dashboard->availability['invoices'] || ($pdfMetrics['visible'] && ! $dashboard->availability['invoice_files']))<div role="alert" class="rounded-2xl border border-amber-300 bg-amber-50 px-5 py-4 text-sm text-amber-900">Một hoặc nhiều nhóm dữ liệu chưa sẵn sàng. Dashboard chỉ hiển thị các chỉ số có thể đọc an toàn.</div>@endif
        @foreach ($dashboard->warnings as $warning)<div role="alert" class="rounded-2xl border px-5 py-4 text-sm {{ $warning['level'] === 'danger' ? 'border-red-300 bg-red-50 text-red-900' : 'border-amber-300 bg-amber-50 text-amber-900' }}"><p class="font-semibold">Cần chú ý</p><p class="mt-1">{{ $warning['message'] }}</p></div>@endforeach

        <section aria-labelledby="invoice-kpi-heading">
            <div class="mb-3"><h2 id="invoice-kpi-heading" class="text-lg font-semibold text-slate-900">Tổng quan vận hành</h2><p class="mt-1 text-sm text-slate-500">Các KPI dẫn trực tiếp về danh sách để xử lý chi tiết.</p></div>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <a href="{{ route('admin.invoices.hoadon-list') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow-md"><p class="text-sm font-medium text-slate-600">Tổng hóa đơn</p><p class="mt-2 text-3xl font-bold text-indigo-700">{{ $invoiceMetrics['available'] ? number_format($invoiceMetrics['total']) : '—' }}</p><p class="mt-2 text-xs text-slate-500">Gần nhất: {{ $formatDate($invoiceMetrics['latest_at']) }}</p></a>
                <a href="{{ route('admin.invoices.hoadon-list', ['invoiceType' => 'sold']) }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-emerald-300 hover:shadow-md"><p class="text-sm font-medium text-slate-600">Hóa đơn bán ra</p><p class="mt-2 text-3xl font-bold text-emerald-700">{{ $invoiceMetrics['available'] ? number_format($invoiceMetrics['sold']) : '—' }}</p></a>
                <a href="{{ route('admin.invoices.hoadon-list', ['invoiceType' => 'purchase']) }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-sky-300 hover:shadow-md"><p class="text-sm font-medium text-slate-600">Hóa đơn mua vào</p><p class="mt-2 text-3xl font-bold text-sky-700">{{ $invoiceMetrics['available'] ? number_format($invoiceMetrics['purchase']) : '—' }}</p></a>
                @if ($pdfMetrics['visible'])
                    <a href="{{ route('admin.invoices.hoadon-list') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow-md"><p class="text-sm font-medium text-slate-600">PDF đã lưu</p><p class="mt-2 text-3xl font-bold text-violet-700">{{ $pdfMetrics['available'] ? number_format($pdfMetrics['stored']) : '—' }}</p><p class="mt-2 text-xs text-slate-500">{{ $pdfMetrics['available'] ? 'Có thể tải/xem ngay' : 'Chưa có dữ liệu trạng thái PDF' }}</p></a>
                    <a href="{{ route('admin.invoices.hoadon-list') }}#invoice-pdf-drive-sync" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-amber-300 hover:shadow-md"><p class="text-sm font-medium text-slate-600">PDF chưa hoàn tất</p><p class="mt-2 text-3xl font-bold {{ ($pdfMetrics['missing'] + $pdfMetrics['error']) > 0 ? 'text-amber-700' : 'text-emerald-700' }}">{{ $pdfMetrics['available'] ? number_format($pdfMetrics['missing'] + $pdfMetrics['error']) : '—' }}</p><p class="mt-2 text-xs text-slate-500">{{ $pdfMetrics['available'] ? number_format($pdfMetrics['missing']).' thiếu · '.number_format($pdfMetrics['error']).' lỗi' : 'Chưa có dữ liệu trạng thái PDF' }}</p></a>
                @endif
            </div>
        </section>

        <section aria-labelledby="classification-kpi-heading" class="rounded-2xl border border-amber-200 bg-amber-50/30 p-5 shadow-sm sm:p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div><p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Hóa đơn mua vào · sau phân loại</p><h2 id="classification-kpi-heading" class="mt-1 text-lg font-bold text-slate-950">Cơ cấu hàng hóa & chi phí</h2><p class="mt-1 text-sm text-slate-600">Giá trị sử dụng số tiền trước VAT. VAT hóa đơn không được tự động xem là chi phí.</p></div>
                @if ($capabilities['create'])<a href="{{ route('admin.invoices.source-data') }}" class="inline-flex min-h-10 shrink-0 items-center justify-center rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-700">Mở phân loại nguồn →</a>@endif
            </div>

            @if ($classificationMetrics['available'])
                <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                    <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs font-semibold uppercase text-slate-500">Tổng mua vào có nguồn</p><p class="mt-2 text-xl font-bold text-slate-950">{{ $formatMoney($classificationMetrics['total_value']) }}</p><p class="mt-1 text-xs text-slate-500">{{ number_format($classificationMetrics['total_count']) }} hóa đơn</p></div>
                    <a href="{{ route('admin.invoices.source-data', ['businessClassification' => 'GOODS']) }}" class="rounded-xl border border-emerald-200 bg-white p-4 transition hover:border-emerald-400"><p class="text-xs font-semibold uppercase text-emerald-700">Hàng hóa</p><p class="mt-2 text-xl font-bold text-emerald-800">{{ $formatMoney($classificationMetrics['goods_value']) }}</p><p class="mt-1 text-xs text-slate-500">{{ number_format($classificationMetrics['goods_count']) }} hóa đơn</p></a>
                    <a href="{{ route('admin.invoices.source-data', ['businessClassification' => 'SERVICE_EXPENSE']) }}" class="rounded-xl border border-amber-200 bg-white p-4 transition hover:border-amber-400"><p class="text-xs font-semibold uppercase text-amber-700">Dịch vụ / Chi phí</p><p class="mt-2 text-xl font-bold text-amber-800">{{ $formatMoney($classificationMetrics['expense_value']) }}</p><p class="mt-1 text-xs text-slate-500">{{ number_format($classificationMetrics['expense_count']) }} hóa đơn · {{ number_format($classificationMetrics['expense_unclassified_count']) }} chưa phân loại cấp 2</p></a>
                    <a href="{{ route('admin.invoices.source-data', ['businessClassification' => 'MIXED']) }}" class="rounded-xl border border-violet-200 bg-white p-4 transition hover:border-violet-400"><p class="text-xs font-semibold uppercase text-violet-700">Hỗn hợp</p><p class="mt-2 text-xl font-bold text-violet-800">{{ $formatMoney($classificationMetrics['mixed_value']) }}</p><p class="mt-1 text-xs text-slate-500">{{ number_format($classificationMetrics['mixed_count']) }} hóa đơn · chưa tách giá trị theo dòng</p></a>
                    <a href="{{ route('admin.invoices.source-data', ['businessClassification' => 'UNCLASSIFIED']) }}" class="rounded-xl border border-slate-300 bg-white p-4 transition hover:border-slate-400"><p class="text-xs font-semibold uppercase text-slate-600">Chưa phân loại</p><p class="mt-2 text-xl font-bold text-slate-800">{{ $formatMoney($classificationMetrics['unclassified_value']) }}</p><p class="mt-1 text-xs text-slate-500">{{ number_format($classificationMetrics['unclassified_count']) }} hóa đơn cần review</p></a>
                </div>

                @if (!empty($classificationMetrics['expense_breakdown']))
                    <div class="mt-5"><div class="mb-3 flex items-center justify-between gap-3"><div><h3 class="font-semibold text-slate-900">Phân loại chi phí cấp 2</h3><p class="mt-1 text-xs text-slate-500">Danh mục động; có thể bổ sung nhóm mới mà không thay schema hóa đơn nguồn.</p></div></div><div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">@foreach ($classificationMetrics['expense_breakdown'] as $expense)<div class="rounded-xl border border-amber-100 bg-white px-4 py-3"><p class="truncate text-xs font-semibold text-slate-600" title="{{ $expense['name'] }}">{{ $expense['name'] }}</p><p class="mt-1 font-bold text-slate-950">{{ $formatMoney($expense['total_value']) }}</p><p class="mt-1 text-[11px] text-slate-500">{{ number_format($expense['invoice_count']) }} hóa đơn</p></div>@endforeach</div></div>
                @endif
            @else
                <div class="mt-5 rounded-xl border border-amber-200 bg-white px-4 py-4 text-sm text-amber-900">Dữ liệu phân loại chưa sẵn sàng. Hãy chạy migration của Invoices rồi mở lại Dashboard.</div>
            @endif
        </section>

        <section aria-labelledby="quick-actions-heading">
            <div class="mb-3"><h2 id="quick-actions-heading" class="text-lg font-semibold text-slate-900">Thao tác nhanh</h2><p class="mt-1 text-sm text-slate-500">Dashboard không lặp lại nghiệp vụ; mỗi hành động mở đúng workspace chuyên trách.</p></div>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
                <a href="{{ route('admin.invoices.hoadon-list') }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-indigo-300"><p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Dữ liệu</p><p class="mt-1 font-bold text-slate-950">Danh sách hóa đơn</p></a>
                @if ($capabilities['create'])<a href="{{ route('admin.invoices.source-data') }}" class="rounded-2xl border border-amber-200 bg-amber-50/60 p-4 shadow-sm transition hover:border-amber-400"><p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Phân loại</p><p class="mt-1 font-bold text-slate-950">Dữ liệu nguồn & chi phí</p></a>@endif
                @if ($capabilities['create'])<a href="{{ route('admin.invoices.hoadon') }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-sky-300"><p class="text-xs font-semibold uppercase tracking-wide text-sky-600">GDT</p><p class="mt-1 font-bold text-slate-950">Đồng bộ hóa đơn</p></a>@endif
                <a href="{{ route('admin.invoices.reports.partners') }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-emerald-300"><p class="text-xs font-semibold uppercase tracking-wide text-emerald-600">Báo cáo</p><p class="mt-1 font-bold text-slate-950">Tổng hợp đối tác</p></a>
                <a href="{{ route('admin.invoices.hoadon-list') }}#invoice-pdf-drive-sync" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-violet-300"><p class="text-xs font-semibold uppercase tracking-wide text-violet-600">Google Drive</p><p class="mt-1 font-bold text-slate-950">PDF ↔ Drive</p></a>
                @if ($capabilities['configure'])<a href="{{ route('admin.invoices.backup-restore') }}" class="rounded-2xl border border-indigo-200 bg-indigo-50/60 p-4 shadow-sm transition hover:border-indigo-400"><p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Protection</p><p class="mt-1 font-bold text-slate-950">Backup / Restore</p></a>@endif
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-3">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-2" aria-labelledby="health-heading">
                <div class="mb-4"><h2 id="health-heading" class="text-lg font-semibold text-slate-900">Operational Health</h2><p class="mt-1 text-sm text-slate-500">Chỉ hiển thị trạng thái có thể hành động, không suy đoán trạng thái queue toàn cục.</p></div>
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @if ($processing['gdt']['visible'])
                        <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs font-medium text-slate-500">GDT</p>@if ($processing['gdt']['available'])<p class="mt-1 font-semibold {{ $processing['gdt']['configured'] ? 'text-emerald-700' : 'text-amber-700' }}">{{ $processing['gdt']['configured'] ? 'Đã cấu hình tài khoản' : 'Cấu hình chưa đầy đủ' }}</p><p class="mt-1 text-xs text-slate-500">Phiên server: {{ $processing['gdt']['session_available'] ? 'Đang khả dụng' : 'Chưa khả dụng' }}</p>@else<p class="mt-1 font-semibold text-amber-700">Không đọc được trạng thái</p>@endif @if ($capabilities['configure'] && (! $processing['gdt']['configured'] || ! $processing['gdt']['session_available']))<a href="{{ route('admin.invoices.create-token') }}" class="mt-3 inline-flex text-xs font-semibold text-indigo-700 hover:text-indigo-800">Kết nối GDT →</a>@endif</div>
                    @endif
                    <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs font-medium text-slate-500">Google Drive</p>@if (! $driveStatus['available'])<p class="mt-1 font-semibold text-amber-700">Không đọc được trạng thái</p>@elseif ($driveStatus['connected'])<p class="mt-1 font-semibold text-emerald-700">Đã kết nối</p>@if ($driveStatus['email'] !== '')<p class="mt-1 truncate text-xs text-slate-500">{{ $driveStatus['email'] }}</p>@endif<p class="mt-1 text-xs text-slate-500">Thư mục: {{ $driveStatus['folder_name'] ?: 'Laravel-Backup' }}</p><p class="mt-1 text-xs text-slate-500">Kiểm tra gần nhất: {{ $formatDate($driveStatus['last_checked_at'] ?: null) }}</p>@else<p class="mt-1 font-semibold text-red-700">Chưa kết nối</p>@if ($capabilities['configure'])<a href="{{ route('admin.system.settings.cloud.google.connect') }}" class="mt-3 inline-flex text-xs font-semibold text-indigo-700 hover:text-indigo-800">Kết nối Google Drive →</a>@endif @endif</div>
                    <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs font-medium text-slate-500">Queue</p><p class="mt-1 font-semibold text-slate-900">Theo dõi tại workspace</p><p class="mt-1 text-xs text-slate-500">Batch PDF/GDT dùng trạng thái theo từng lần chạy.</p></div>
                    @if ($pdfMetrics['visible'])<div class="rounded-xl bg-slate-50 p-4"><p class="text-xs font-medium text-slate-500">PDF</p>@if ($pdfMetrics['available'])<p class="mt-1 font-semibold {{ ($pdfMetrics['missing'] + $pdfMetrics['error']) > 0 ? 'text-amber-700' : 'text-emerald-700' }}">{{ number_format($pdfMetrics['stored']) }} đã lưu</p><p class="mt-1 text-xs text-slate-500">{{ number_format($pdfMetrics['missing']) }} thiếu · {{ number_format($pdfMetrics['error']) }} lỗi</p><a href="{{ route('admin.invoices.hoadon-list') }}#invoice-pdf-drive-sync" class="mt-3 inline-flex text-xs font-semibold text-indigo-700 hover:text-indigo-800">Đến PDF ↔ Drive →</a>@else<p class="mt-1 font-semibold text-amber-700">Chưa có dữ liệu trạng thái</p>@endif</div>@endif
                </div>
            </section>

            @if ($capabilities['configure'])
                <section class="rounded-2xl border border-indigo-200 bg-indigo-50/40 p-5 shadow-sm" aria-labelledby="protection-heading"><p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Module protection</p><h2 id="protection-heading" class="mt-1 text-lg font-bold text-slate-950">Backup & Recovery</h2><div class="mt-4 space-y-3 text-sm"><div class="flex items-center justify-between gap-3"><span class="text-slate-600">Backup module</span><span class="font-semibold text-slate-900">Snapshot + checksum</span></div><div class="flex items-center justify-between gap-3"><span class="text-slate-600">Restore</span><span class="font-semibold text-slate-900">Phải kiểm tra readiness</span></div><div class="flex items-center justify-between gap-3"><span class="text-slate-600">Safety Backup</span><span class="font-semibold text-emerald-700">Bắt buộc</span></div><div class="flex items-center justify-between gap-3"><span class="text-slate-600">Partner master</span><span class="font-semibold text-emerald-700">0 thay đổi</span></div></div><a href="{{ route('admin.invoices.backup-restore') }}" class="mt-5 inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">Kiểm tra Restore / Backup ngay</a></section>
            @endif
        </div>

        <div class="grid gap-6 {{ $processing['backup']['visible'] ? 'xl:grid-cols-2' : '' }}">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="recent-invoices-heading"><div class="flex items-start justify-between gap-4 border-b border-slate-200 p-5"><div><h2 id="recent-invoices-heading" class="font-bold text-slate-950">Hoạt động hóa đơn gần đây</h2><p class="mt-1 text-sm text-slate-500">Tối đa 5 bản ghi, không hiển thị định danh nhạy cảm hoặc số tiền.</p></div><a href="{{ route('admin.invoices.hoadon-list') }}" class="shrink-0 text-sm font-semibold text-indigo-700">Mở danh sách</a></div><div class="divide-y divide-slate-100">@forelse ($dashboard->recentInvoices as $invoice)<div class="p-5"><div class="flex flex-wrap items-center justify-between gap-3"><p class="font-semibold text-slate-900">{{ $invoiceTypeLabels[$invoice['type']] }}</p><time datetime="{{ $invoice['created_at'] }}" class="text-xs text-slate-500">{{ $formatDate($invoice['created_at']) }}</time></div><p class="mt-1 text-sm text-slate-500">Đã được ghi nhận trong kho dữ liệu cục bộ.</p></div>@empty<div class="p-8 text-center text-sm text-slate-500">Chưa có hoạt động hóa đơn để hiển thị.</div>@endforelse</div></section>

            @if ($processing['backup']['visible'])
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="recent-backups-heading"><div class="border-b border-slate-200 p-5"><h2 id="recent-backups-heading" class="font-bold text-slate-950">Backup gần đây</h2><p class="mt-1 text-sm text-slate-500">Lịch sử backup PDF/email hiện hữu; module snapshot mới được quản lý tại Backup & Restore.</p></div><div class="divide-y divide-slate-100">@forelse ($dashboard->recentBackupRuns as $run)<div class="flex items-center justify-between gap-4 p-5"><div><p class="font-semibold text-slate-900">{{ $backupStatusLabels[$run['status']] ?? 'Không xác định' }}</p><p class="mt-1 text-xs text-slate-500">{{ strtoupper($run['mode']) }} · {{ number_format($run['files_count']) }} file</p></div><time datetime="{{ $run['finished_at'] }}" class="text-xs text-slate-500">{{ $formatDate($run['finished_at']) }}</time></div>@empty<div class="p-8 text-center text-sm text-slate-500">Chưa có backup gần đây.</div>@endforelse</div></section>
            @endif
        </div>
    </div>
@endsection