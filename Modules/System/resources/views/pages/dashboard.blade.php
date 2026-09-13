@extends('Admin::layouts.master')

@section('title', 'Dashboard hệ thống')

@section('content')
    @php
        $configuration = $dashboard->metrics['configuration'];
        $queueMetrics = $dashboard->metrics['queues'];
        $formatDate = static fn (?string $value): string => $value
            ? \Illuminate\Support\Carbon::parse($value)->timezone(config('app.timezone'))->format('d/m/Y H:i')
            : 'Chưa có dữ liệu';

        $workspaceUrls = [
            'modules' => route('admin.system.modules'),
            'queue' => route('admin.system.index', ['tab' => 'queues']),
            'database' => route('admin.system.database.index'),
            'backup' => route('admin.system.database.backup-restore'),
            'environment' => route('admin.system.settings.env'),
            'login' => route('admin.system.settings.login-theme'),
            'artisan' => route('admin.system.artisan'),
            'scripts' => route('admin.system.scripts'),
        ];

        $workspaceGroups = collect($dashboard->workspaces)->groupBy('category');
        $categoryDescriptions = [
            'Vận hành' => 'Runtime, Module lifecycle và hàng đợi xử lý nền.',
            'Dữ liệu & Khôi phục' => 'Database, backup và quy trình phục hồi có kiểm soát.',
            'Cấu hình hạ tầng' => 'Môi trường, tích hợp và các dịch vụ nền của ứng dụng.',
            'Quản trị truy cập' => 'Branding đăng nhập và điều hướng sau xác thực.',
            'Công cụ kỹ thuật' => 'Công cụ dành cho quản trị viên kỹ thuật có quyền phù hợp.',
        ];

        $metricCards = [
            [
                'label' => 'Workspace khả dụng',
                'value' => number_format($dashboard->metrics['workspaces']['visible']),
                'meta' => 'Chỉ hiển thị theo capability hiện tại',
                'accent' => 'text-indigo-700',
            ],
        ];

        if ($queueMetrics['visible']) {
            $metricCards[] = [
                'label' => 'Queue workload',
                'value' => $queueMetrics['available'] ? number_format($queueMetrics['pending'] + $queueMetrics['reserved']) : '—',
                'meta' => $queueMetrics['available']
                    ? number_format($queueMetrics['pending']).' pending · '.number_format($queueMetrics['failed']).' failed'
                    : 'Queue storage chưa sẵn sàng',
                'accent' => $queueMetrics['failed'] > 0 ? 'text-red-700' : 'text-sky-700',
            ];
        }

        if ($configuration['visible']) {
            $metricCards[] = [
                'label' => 'Runtime config',
                'value' => $configuration['available'] ? number_format($configuration['ready']).'/'.number_format($configuration['total']) : '—',
                'meta' => 'App key · Database · Mail',
                'accent' => $configuration['available'] && $configuration['ready'] === $configuration['total'] ? 'text-emerald-700' : 'text-amber-700',
            ];
        }

        $metricCards[] = [
            'label' => 'Cảnh báo',
            'value' => number_format($dashboard->metrics['warnings']['count']),
            'meta' => 'Tối đa 5 cảnh báo vận hành quan trọng',
            'accent' => $dashboard->metrics['warnings']['count'] > 0 ? 'text-amber-700' : 'text-emerald-700',
        ];

        $warningClasses = [
            'warning' => 'border-amber-300 bg-amber-50 text-amber-900',
            'danger' => 'border-red-300 bg-red-50 text-red-900',
        ];

        $stateLabels = [
            'ready' => 'Sẵn sàng',
            'attention' => 'Cần chú ý',
            'danger' => 'Cần xử lý',
            'idle' => 'Đang tắt',
            'empty' => 'Không có workload',
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
            'settings' => ['label' => 'Settings store', 'description' => 'Kho thiết lập canonical của hệ thống.'],
            'queue' => ['label' => 'Queue runtime', 'description' => 'Trạng thái workload cục bộ của queue.'],
            'database' => ['label' => 'Database metadata', 'description' => 'Khả năng đọc migration metadata.'],
            'google_drive' => ['label' => 'Google Drive', 'description' => 'Trạng thái cấu hình và kết nối đã lưu.'],
            'cloud_backup' => ['label' => 'Cloud backup', 'description' => 'Lịch tự động và lần chạy gần nhất.'],
        ];
    @endphp

    <div class="space-y-8">
        <header class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-indigo-600">System Control Center</p>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Dashboard hệ thống</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                        Một điểm vào duy nhất cho vận hành, dữ liệu, tích hợp và quản trị truy cập. Dashboard chỉ tổng hợp trạng thái và điều hướng; mutation vẫn nằm trong workspace chuyên trách.
                    </p>
                    <p class="mt-2 text-xs text-slate-500">Cập nhật lúc <time datetime="{{ $dashboard->generatedAt }}">{{ $formatDate($dashboard->generatedAt) }}</time></p>
                </div>
                <div class="rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm text-indigo-800">
                    <span class="font-semibold">IA mới:</span> bỏ các entry trùng lặp như Themes, Hình ảnh và Cấu hình chung.
                </div>
            </div>
        </header>

        <section aria-labelledby="system-summary-heading">
            <h2 id="system-summary-heading" class="sr-only">Tóm tắt hệ thống</h2>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($metricCards as $card)
                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm font-medium text-slate-600">{{ $card['label'] }}</p>
                        <p class="mt-2 text-3xl font-bold {{ $card['accent'] }}">{{ $card['value'] }}</p>
                        <p class="mt-2 text-xs leading-5 text-slate-500">{{ $card['meta'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section aria-labelledby="system-alerts-heading">
            <div class="mb-3 flex items-center justify-between">
                <div>
                    <h2 id="system-alerts-heading" class="text-lg font-semibold text-slate-900">Cần chú ý</h2>
                    <p class="mt-1 text-sm text-slate-500">Các cảnh báo vận hành cần ưu tiên xử lý.</p>
                </div>
            </div>
            <div class="space-y-3">
                @forelse ($dashboard->warnings as $warning)
                    <div role="alert" class="rounded-2xl border px-5 py-4 text-sm {{ $warningClasses[$warning['level']] ?? $warningClasses['warning'] }}">
                        <p class="font-semibold">{{ $warning['message'] }}</p>
                    </div>
                @empty
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-900" role="status">
                        <p class="font-semibold">Không có cảnh báo trong phạm vi Dashboard</p>
                        <p class="mt-1">Các subsystem đang ở trạng thái không yêu cầu xử lý ngay.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_320px]">
            <div class="space-y-8">
                @foreach (['Vận hành', 'Dữ liệu & Khôi phục', 'Cấu hình hạ tầng', 'Quản trị truy cập', 'Công cụ kỹ thuật'] as $category)
                    @php $items = $workspaceGroups->get($category, collect()); @endphp
                    @continue($items->isEmpty())

                    <section aria-labelledby="workspace-{{ \Illuminate\Support\Str::slug($category) }}">
                        <div class="mb-4">
                            <h2 id="workspace-{{ \Illuminate\Support\Str::slug($category) }}" class="text-lg font-semibold text-slate-900">{{ $category }}</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-500">{{ $categoryDescriptions[$category] ?? '' }}</p>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            @foreach ($items as $workspace)
                                <a href="{{ $workspaceUrls[$workspace['code']] }}"
                                   class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">{{ $workspace['category'] }}</p>
                                            <h3 class="mt-2 text-lg font-bold text-slate-950 group-hover:text-indigo-700">{{ $workspace['label'] }}</h3>
                                        </div>
                                        <span class="text-xl text-slate-300 transition group-hover:translate-x-1 group-hover:text-indigo-500" aria-hidden="true">→</span>
                                    </div>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $workspace['description'] }}</p>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>

            <aside class="min-w-0" aria-labelledby="system-status-heading">
                <div class="sticky top-4">
                    <div class="mb-4">
                        <h2 id="system-status-heading" class="text-lg font-semibold text-slate-900">Subsystem health</h2>
                        <p class="mt-1 text-sm text-slate-500">Read-only, sanitized.</p>
                    </div>
                    <div class="space-y-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        @foreach ($dashboard->subsystems as $code => $subsystem)
                            @continue(! $subsystem['visible'])
                            <article class="rounded-xl bg-slate-50 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h3 class="text-sm font-semibold text-slate-900">{{ $subsystemLabels[$code]['label'] }}</h3>
                                        <p class="mt-1 text-xs leading-5 text-slate-500">{{ $subsystemLabels[$code]['description'] }}</p>
                                    </div>
                                    <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $stateClasses[$subsystem['state']] ?? $stateClasses['unavailable'] }}">
                                        {{ $stateLabels[$subsystem['state']] ?? $stateLabels['unavailable'] }}
                                    </span>
                                </div>
                                @if ($code === 'queue' && $subsystem['available'])
                                    <p class="mt-3 text-xs text-slate-600">{{ number_format($queueMetrics['pending']) }} pending · {{ number_format($queueMetrics['reserved']) }} processing · {{ number_format($queueMetrics['failed']) }} failed</p>
                                @elseif ($code === 'google_drive' && $subsystem['available'])
                                    <p class="mt-3 text-xs text-slate-600">OAuth: {{ $subsystem['configured'] ? 'đã cấu hình' : 'chưa đầy đủ' }} · Kết nối: {{ $subsystem['connected'] ? 'đã lưu' : 'chưa có' }}</p>
                                @elseif ($code === 'cloud_backup' && $subsystem['available'])
                                    <p class="mt-3 text-xs text-slate-600">Lịch: {{ $subsystem['enabled'] ? 'đang bật' : 'đang tắt' }} · Gần nhất: {{ $formatDate($subsystem['last_run_at']) }}</p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </div>
            </aside>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-slate-50 p-5" aria-labelledby="system-safety-heading">
            <h2 id="system-safety-heading" class="font-semibold text-slate-900">Boundary an toàn</h2>
            <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600">
                Dashboard không gọi dịch vụ ngoài, không chạy Artisan hoặc shell, không tải secret hay raw exception. Các thao tác thay đổi trạng thái vẫn yêu cầu permission trong workspace đích.
            </p>
        </section>
    </div>
@endsection
