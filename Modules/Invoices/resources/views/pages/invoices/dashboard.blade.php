@extends('Admin::layouts.master')

@section('title', 'Dashboard hóa đơn')

@section('content')
    @php
        $capabilities = $dashboard->capabilities;
        $invoiceMetrics = $dashboard->metrics['invoices'];
        $pdfMetrics = $dashboard->metrics['pdf'];
        $processing = $dashboard->processing;
        $formatDate = static fn (?string $value): string => $value
            ? \Illuminate\Support\Carbon::parse($value)->timezone(config('app.timezone'))->format('d/m/Y H:i')
            : 'Chưa có dữ liệu';

        $metricCards = [
            [
                'label' => 'Tổng hóa đơn',
                'value' => $invoiceMetrics['available'] ? number_format($invoiceMetrics['total']) : '—',
                'meta' => 'Gần nhất: '.$formatDate($invoiceMetrics['latest_at']),
                'accent' => 'text-indigo-700',
            ],
            [
                'label' => 'Hóa đơn bán ra',
                'value' => $invoiceMetrics['available'] ? number_format($invoiceMetrics['sold']) : '—',
                'meta' => 'Chỉ hiển thị số lượng bản ghi',
                'accent' => 'text-emerald-700',
            ],
            [
                'label' => 'Hóa đơn mua vào',
                'value' => $invoiceMetrics['available'] ? number_format($invoiceMetrics['purchase']) : '—',
                'meta' => 'Không hiển thị giá trị tài chính',
                'accent' => 'text-sky-700',
            ],
        ];

        if ($pdfMetrics['visible']) {
            $metricCards[] = [
                'label' => 'PDF khả dụng',
                'value' => $pdfMetrics['available'] ? number_format($pdfMetrics['stored']) : '—',
                'meta' => $pdfMetrics['available']
                    ? number_format($pdfMetrics['missing']).' thiếu · '.number_format($pdfMetrics['error']).' lỗi'
                    : 'Dữ liệu PDF chưa sẵn sàng',
                'accent' => $pdfMetrics['error'] > 0 ? 'text-red-700' : 'text-amber-700',
            ];
        }

        $invoiceTypeLabels = [
            'sold' => 'Hóa đơn bán ra',
            'purchase' => 'Hóa đơn mua vào',
            'unknown' => 'Hóa đơn chưa xác định chiều',
        ];
        $backupStatusLabels = [
            'running' => 'Đang chạy',
            'skipped' => 'Không có file mới',
            'success' => 'Hoàn tất',
            'failed' => 'Thất bại',
            'unknown' => 'Không xác định',
        ];
        $backupStatusClasses = [
            'running' => 'bg-sky-100 text-sky-700',
            'skipped' => 'bg-slate-100 text-slate-700',
            'success' => 'bg-emerald-100 text-emerald-700',
            'failed' => 'bg-red-100 text-red-700',
            'unknown' => 'bg-amber-100 text-amber-700',
        ];
        $warningClasses = [
            'warning' => 'border-amber-300 bg-amber-50 text-amber-900',
            'danger' => 'border-red-300 bg-red-50 text-red-900',
        ];
    @endphp

    <div class="space-y-8">
        <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Invoices</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Dashboard hóa đơn</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                    Theo dõi số lượng, trạng thái PDF, backup và mở các workspace quản trị theo quyền được cấp.
                </p>
                <p class="mt-2 text-xs text-slate-500">
                    Cập nhật lúc <time datetime="{{ $dashboard->generatedAt }}">{{ $formatDate($dashboard->generatedAt) }}</time>
                </p>
            </div>
            <a href="{{ route('admin.invoices.hoadon-list') }}"
               class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Mở danh sách hóa đơn
            </a>
        </header>

        @foreach ($dashboard->warnings as $warning)
            <div role="alert" class="rounded-2xl border px-5 py-4 text-sm {{ $warningClasses[$warning['level']] ?? $warningClasses['warning'] }}">
                <p class="font-semibold">Cần chú ý</p>
                <p class="mt-1">{{ $warning['message'] }}</p>
            </div>
        @endforeach

        <section aria-labelledby="invoice-summary-heading">
            <h2 id="invoice-summary-heading" class="sr-only">Tóm tắt dữ liệu hóa đơn</h2>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($metricCards as $card)
                    <a href="{{ route('admin.invoices.hoadon-list') }}"
                       class="group min-w-0 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        <p class="text-sm font-medium text-slate-600">{{ $card['label'] }}</p>
                        <p class="mt-2 text-3xl font-bold {{ $card['accent'] }}">{{ $card['value'] }}</p>
                        <p class="mt-2 text-xs leading-5 text-slate-500">{{ $card['meta'] }}</p>
                    </a>
                @endforeach
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-3">
            <section class="min-w-0 xl:col-span-2" aria-labelledby="invoice-workspaces-heading">
                <div class="mb-4">
                    <h2 id="invoice-workspaces-heading" class="text-lg font-semibold text-slate-900">Không gian quản lý</h2>
                    <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">
                        Dashboard chỉ điều hướng. Đồng bộ, export, download và các thao tác thay đổi dữ liệu vẫn nằm trong workspace chuyên trách.
                    </p>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <a href="{{ route('admin.invoices.hoadon-list') }}"
                       class="rounded-2xl border border-indigo-200 bg-indigo-50/60 p-5 transition hover:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Dữ liệu nội bộ</p>
                        <h3 class="mt-2 text-lg font-bold text-slate-950">Danh sách hóa đơn</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Mở bộ lọc, thống kê chi tiết và các chức năng được cấp quyền.</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @if ($capabilities['export'])
                                <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">Có quyền export</span>
                            @endif
                            @if ($capabilities['download'])
                                <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">Có quyền tải PDF</span>
                            @endif
                        </div>
                    </a>

                    <a href="{{ route('admin.invoices.reports.partners') }}"
                       class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-5 transition hover:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Báo cáo</p>
                        <h3 class="mt-2 text-lg font-bold text-slate-950">Tổng hợp đối tác</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Xem báo cáo nghiệp vụ trong workspace có kiểm soát; Dashboard không mang số liệu tài chính ra ngoài.</p>
                    </a>

                    @if ($capabilities['create'])
                        <a href="{{ route('admin.invoices.hoadon') }}"
                           class="rounded-2xl border border-sky-200 bg-sky-50/60 p-5 transition hover:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-500">
                            <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">GDT</p>
                            <h3 class="mt-2 text-lg font-bold text-slate-950">Đồng bộ hóa đơn</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Mở queue, import và backup tại workspace chuyên trách.</p>
                        </a>
                    @endif

                    @if ($capabilities['configure'])
                        <a href="{{ route('admin.invoices.create-token') }}"
                           class="rounded-2xl border border-amber-200 bg-amber-50/60 p-5 transition hover:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500">
                            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Tích hợp</p>
                            <h3 class="mt-2 text-lg font-bold text-slate-950">Kết nối GDT</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Quản lý cấu hình và phiên GDT theo capability riêng.</p>
                        </a>
                    @endif
                </div>
            </section>

            <section class="min-w-0" aria-labelledby="invoice-processing-heading">
                <div class="mb-4">
                    <h2 id="invoice-processing-heading" class="text-lg font-semibold text-slate-900">Tình trạng xử lý</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-500">Trạng thái an toàn tại thời điểm tải trang.</p>
                </div>
                <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-medium text-slate-500">Theo dõi đồng bộ toàn cục</p>
                        <p class="mt-1 font-semibold text-slate-900">Xem tại workspace đồng bộ</p>
                        <p class="mt-1 text-xs leading-5 text-slate-500">Job hiện dùng cache key theo từng lần chạy nên Dashboard không suy đoán trạng thái toàn cục.</p>
                    </div>

                    @if ($processing['gdt']['visible'])
                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-xs font-medium text-slate-500">Kết nối GDT</p>
                            @if ($processing['gdt']['available'])
                                <p class="mt-1 font-semibold {{ $processing['gdt']['configured'] ? 'text-emerald-700' : 'text-amber-700' }}">
                                    {{ $processing['gdt']['configured'] ? 'Đã cấu hình tài khoản' : 'Cấu hình chưa đầy đủ' }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Phiên server: {{ $processing['gdt']['session_available'] ? 'Đang khả dụng' : 'Chưa khả dụng' }}
                                </p>
                            @else
                                <p class="mt-1 font-semibold text-amber-700">Tạm thời không đọc được trạng thái</p>
                            @endif
                        </div>
                    @endif

                    @if ($processing['backup']['visible'])
                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-xs font-medium text-slate-500">Backup PDF</p>
                            <p class="mt-1 font-semibold {{ $processing['backup']['automatic_enabled'] ? 'text-emerald-700' : 'text-slate-700' }}">
                                {{ $processing['backup']['automatic_enabled'] ? 'Lịch tự động đang bật' : 'Lịch tự động đang tắt' }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                @if ($processing['backup']['schedule_time'])
                                    Ngày {{ $processing['backup']['schedule_day'] }} hàng tháng lúc {{ $processing['backup']['schedule_time'] }}
                                @else
                                    Thời gian chạy chưa hợp lệ
                                @endif
                            </p>
                        </div>
                    @endif
                </div>
            </section>
        </div>

        <div class="grid gap-6 {{ $processing['backup']['visible'] ? 'xl:grid-cols-2' : '' }}">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="recent-invoices-heading">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 p-5">
                    <div>
                        <h2 id="recent-invoices-heading" class="font-bold text-slate-950">Hoạt động hóa đơn gần đây</h2>
                        <p class="mt-1 text-sm text-slate-500">Tối đa 5 bản ghi; không tải định danh, đối tác hoặc số tiền.</p>
                    </div>
                    <a href="{{ route('admin.invoices.hoadon-list') }}" class="shrink-0 text-sm font-semibold text-indigo-700 hover:text-indigo-800">Mở danh sách</a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($dashboard->recentInvoices as $invoice)
                        <div class="p-5">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <p class="font-semibold text-slate-900">{{ $invoiceTypeLabels[$invoice['type']] }}</p>
                                <time datetime="{{ $invoice['created_at'] }}" class="text-xs text-slate-500">{{ $formatDate($invoice['created_at']) }}</time>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">Đã được ghi nhận trong kho dữ liệu cục bộ.</p>
                        </div>
                    @empty
                        <div class="p-8 text-center text-sm text-slate-500">Chưa có hoạt động hóa đơn để hiển thị.</div>
                    @endforelse
                </div>
            </section>

            @if ($processing['backup']['visible'])
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="recent-backups-heading">
                    <div class="border-b border-slate-200 p-5">
                        <h2 id="recent-backups-heading" class="font-bold text-slate-950">Backup gần đây</h2>
                        <p class="mt-1 text-sm text-slate-500">Tối đa 5 lần chạy; không tải recipient, file list hoặc thông báo lỗi thô.</p>
                    </div>
                    @if (! $processing['backup']['history_available'])
                        <div class="p-8 text-center text-sm text-amber-700">Lịch sử backup chưa sẵn sàng. Hãy kiểm tra migration của Module.</div>
                    @else
                        <div class="divide-y divide-slate-100">
                            @forelse ($dashboard->recentBackupRuns as $run)
                                <div class="p-5">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <p class="font-semibold text-slate-900">{{ $run['mode'] === 'automatic' ? 'Backup tự động' : ($run['mode'] === 'manual' ? 'Backup thủ công' : 'Backup') }}</p>
                                            <p class="mt-1 text-sm text-slate-500">{{ number_format($run['files_count']) }} file · {{ number_format($run['emails_sent']) }} email</p>
                                        </div>
                                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $backupStatusClasses[$run['status']] }}">
                                            {{ $backupStatusLabels[$run['status']] }}
                                        </span>
                                    </div>
                                    <time datetime="{{ $run['started_at'] }}" class="mt-2 block text-xs text-slate-500">{{ $formatDate($run['started_at']) }}</time>
                                </div>
                            @empty
                                <div class="p-8 text-center text-sm text-slate-500">Chưa có lịch sử backup để hiển thị.</div>
                            @endforelse
                        </div>
                    @endif
                </section>
            @endif
        </div>
    </div>
@endsection
