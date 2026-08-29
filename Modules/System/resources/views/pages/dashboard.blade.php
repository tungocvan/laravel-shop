@extends('Admin::layouts.master')

@section('title', 'Dashboard hệ thống')

@section('content')
    @php
        $configuration = $dashboard->metrics['configuration'];
        $queueMetrics = $dashboard->metrics['queues'];
        $formatDate = static fn (?string $value): string => $value
            ? \Illuminate\Support\Carbon::parse($value)->timezone(config('app.timezone'))->format('d/m/Y H:i')
            : 'Chưa có dữ liệu';
        $metricCards = [
            [
                'label' => 'Workspace được phép',
                'value' => number_format($dashboard->metrics['workspaces']['visible']),
                'meta' => 'Tối đa '.$dashboard->metrics['workspaces']['maximum'].' workspace theo capability',
                'accent' => 'text-indigo-700',
            ],
        ];

        if ($configuration['visible']) {
            $metricCards[] = [
                'label' => 'Nhóm cấu hình lõi',
                'value' => $configuration['available']
                    ? number_format($configuration['ready']).'/'.number_format($configuration['total'])
                    : '—',
                'meta' => $configuration['available']
                    ? 'Chỉ kiểm tra trạng thái đầy đủ, không tải giá trị'
                    : 'Trạng thái cấu hình chưa sẵn sàng',
                'accent' => $configuration['available'] && $configuration['ready'] === $configuration['total']
                    ? 'text-emerald-700'
                    : 'text-amber-700',
            ];
        }

        if ($queueMetrics['visible']) {
            $metricCards[] = [
                'label' => 'Job trong queue',
                'value' => $queueMetrics['available']
                    ? number_format($queueMetrics['pending'] + $queueMetrics['reserved'])
                    : '—',
                'meta' => $queueMetrics['available']
                    ? number_format($queueMetrics['pending']).' chờ · '.number_format($queueMetrics['failed']).' lỗi'
                    : 'Kho queue chưa sẵn sàng',
                'accent' => $queueMetrics['failed'] > 0 ? 'text-red-700' : 'text-sky-700',
            ];
        }

        $metricCards[] = [
            'label' => 'Cảnh báo hiện tại',
            'value' => number_format($dashboard->metrics['warnings']['count']),
            'meta' => 'Tối đa 5 cảnh báo đã được rút gọn an toàn',
            'accent' => $dashboard->metrics['warnings']['count'] > 0 ? 'text-amber-700' : 'text-emerald-700',
        ];

        $warningClasses = [
            'warning' => 'border-amber-300 bg-amber-50 text-amber-900',
            'danger' => 'border-red-300 bg-red-50 text-red-900',
        ];
        $stateLabels = [
            'ready' => 'Sẵn sàng',
            'attention' => 'Cần hoàn thiện',
            'danger' => 'Cần xử lý',
            'idle' => 'Đang tắt',
            'empty' => 'Chưa khai báo',
            'unavailable' => 'Chưa sẵn sàng',
        ];
        $stateClasses = [
            'ready' => 'bg-emerald-100 text-emerald-700',
            'attention' => 'bg-amber-100 text-amber-700',
            'danger' => 'bg-red-100 text-red-700',
            'idle' => 'bg-slate-100 text-slate-700',
            'empty' => 'bg-slate-100 text-slate-700',
            'unavailable' => 'bg-amber-100 text-amber-700',
        ];
        $subsystemLabels = [
            'settings' => ['label' => 'Kho thiết lập', 'description' => 'Bảng thiết lập canonical dùng cho cấu hình ứng dụng.'],
            'queue' => ['label' => 'Queue runtime', 'description' => 'Tổng hợp queue đã đăng ký và trạng thái job cục bộ.'],
            'database' => ['label' => 'Database metadata', 'description' => 'Chỉ kiểm tra khả năng đọc metadata migration.'],
            'google_drive' => ['label' => 'Google Drive', 'description' => 'Kiểm tra cấu hình và token đã lưu; không gọi Google API.'],
            'cloud_backup' => ['label' => 'Cloud backup', 'description' => 'Trạng thái lịch tự động và lần chạy gần nhất đã rút gọn.'],
        ];
        $workspaceRoutes = [
            'system' => 'admin.system.index',
            'settings' => 'admin.system.settings.index',
            'environment' => 'admin.system.settings.env',
            'modules' => 'admin.system.modules',
            'artisan' => 'admin.system.artisan',
            'scripts' => 'admin.system.scripts',
            'database' => 'admin.system.database.index',
            'backup' => 'admin.system.database.backup-restore',
        ];
    @endphp

    <div class="space-y-8">
        <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">System</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Dashboard hệ thống</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                    Tổng quan read-only về workspace, cấu hình và hạ tầng vận hành theo đúng quyền được cấp.
                </p>
                <p class="mt-2 text-xs text-slate-500">
                    Cập nhật lúc <time datetime="{{ $dashboard->generatedAt }}">{{ $formatDate($dashboard->generatedAt) }}</time>
                </p>
            </div>
            <a href="{{ route('admin.system.index') }}"
               class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Mở System workspace
            </a>
        </header>

        @forelse ($dashboard->warnings as $warning)
            <div role="alert" class="rounded-2xl border px-5 py-4 text-sm {{ $warningClasses[$warning['level']] ?? $warningClasses['warning'] }}">
                <p class="font-semibold">Cần chú ý</p>
                <p class="mt-1">{{ $warning['message'] }}</p>
            </div>
        @empty
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-900" role="status">
                <p class="font-semibold">Không có cảnh báo trong phạm vi Dashboard</p>
                <p class="mt-1">Các thao tác vận hành chi tiết vẫn cần được kiểm tra trong workspace chuyên trách.</p>
            </div>
        @endforelse

        <section aria-labelledby="system-summary-heading">
            <h2 id="system-summary-heading" class="sr-only">Tóm tắt hệ thống</h2>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($metricCards as $card)
                    <article class="min-w-0 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm font-medium text-slate-600">{{ $card['label'] }}</p>
                        <p class="mt-2 text-3xl font-bold {{ $card['accent'] }}">{{ $card['value'] }}</p>
                        <p class="mt-2 text-xs leading-5 text-slate-500">{{ $card['meta'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-3">
            <section class="min-w-0 xl:col-span-2" aria-labelledby="system-workspaces-heading">
                <div class="mb-4">
                    <h2 id="system-workspaces-heading" class="text-lg font-semibold text-slate-900">Không gian quản trị</h2>
                    <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">
                        Dashboard chỉ điều hướng. Thay đổi cấu hình, chạy operation, backup hoặc restore vẫn nằm trong workspace có authorization riêng.
                    </p>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    @forelse ($dashboard->workspaces as $workspace)
                        <a href="{{ route($workspaceRoutes[$workspace['code']]) }}"
                           class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">{{ $workspace['category'] }}</p>
                            <h3 class="mt-2 text-lg font-bold text-slate-950 group-hover:text-indigo-700">{{ $workspace['label'] }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $workspace['description'] }}</p>
                            <span class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-indigo-700">
                                Mở workspace <span aria-hidden="true">→</span>
                            </span>
                        </a>
                    @empty
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-600 md:col-span-2">
                            Không có workspace phù hợp với quyền hiện tại.
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="min-w-0" aria-labelledby="system-status-heading">
                <div class="mb-4">
                    <h2 id="system-status-heading" class="text-lg font-semibold text-slate-900">Trạng thái subsystem</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-500">Chỉ đọc trạng thái cục bộ đã sanitize.</p>
                </div>
                <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    @if (collect($dashboard->subsystems)->contains(fn (array $subsystem): bool => $subsystem['visible']))
                        @foreach ($dashboard->subsystems as $code => $subsystem)
                            @continue(! $subsystem['visible'])
                            <article class="rounded-xl bg-slate-50 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h3 class="font-semibold text-slate-900">{{ $subsystemLabels[$code]['label'] }}</h3>
                                        <p class="mt-1 text-xs leading-5 text-slate-500">{{ $subsystemLabels[$code]['description'] }}</p>
                                    </div>
                                    <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold {{ $stateClasses[$subsystem['state']] ?? $stateClasses['unavailable'] }}">
                                        {{ $stateLabels[$subsystem['state']] ?? $stateLabels['unavailable'] }}
                                    </span>
                                </div>

                                @if ($code === 'queue' && $subsystem['available'])
                                    <p class="mt-3 text-xs text-slate-600">
                                        {{ number_format($queueMetrics['pending']) }} chờ ·
                                        {{ number_format($queueMetrics['reserved']) }} đang xử lý ·
                                        {{ number_format($queueMetrics['failed']) }} lỗi
                                    </p>
                                @elseif ($code === 'google_drive' && $subsystem['available'])
                                    <p class="mt-3 text-xs text-slate-600">
                                        OAuth: {{ $subsystem['configured'] ? 'đã cấu hình' : 'chưa đầy đủ' }} ·
                                        Kết nối: {{ $subsystem['connected'] ? 'đã lưu' : 'chưa có' }}
                                    </p>
                                @elseif ($code === 'cloud_backup' && $subsystem['available'])
                                    <p class="mt-3 text-xs text-slate-600">
                                        Lịch: {{ $subsystem['enabled'] ? 'đang bật' : 'đang tắt' }} ·
                                        Lần gần nhất: {{ $formatDate($subsystem['last_run_at']) }}
                                    </p>
                                @endif
                            </article>
                        @endforeach
                    @else
                        <p class="rounded-xl bg-slate-50 px-4 py-6 text-center text-sm text-slate-600">
                            Không có trạng thái subsystem phù hợp với quyền hiện tại.
                        </p>
                    @endif
                </div>
            </section>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-slate-50 p-5" aria-labelledby="system-safety-heading">
            <h2 id="system-safety-heading" class="font-semibold text-slate-900">Boundary an toàn</h2>
            <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600">
                Dashboard không gọi dịch vụ bên ngoài, không chạy Artisan hoặc shell, không tải secret, raw config, đường dẫn riêng hay exception thô. Trạng thái worker thực tế vẫn cần xác nhận bằng probe tại Queue Manager.
            </p>
        </section>
    </div>
@endsection
