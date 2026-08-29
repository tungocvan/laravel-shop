@extends('Admin::layouts.master')

@section('title', 'Tổng quan Mua sắm công')

@section('content')
    @php
        $capabilities = $dashboard['capabilities'];
        $metrics = $dashboard['metrics'];
        $queue = $dashboard['queue'];
        $health = $dashboard['health'];
        $formatDate = static fn (?string $value): string => $value
            ? \Illuminate\Support\Carbon::parse($value)->timezone(config('app.timezone'))->format('d/m/Y H:i')
            : 'Chưa có dữ liệu';

        $metricCards = [
            [
                'label' => 'Dữ liệu đã đồng bộ',
                'value' => $metrics['pricing_results']['available'] ? number_format($metrics['pricing_results']['count']) : '—',
                'meta' => 'Gần nhất: '.$formatDate($metrics['pricing_results']['latest_at']),
                'href' => route('muasamcong.synced'),
                'accent' => 'text-emerald-700',
            ],
            [
                'label' => 'Lịch sử tra cứu giá',
                'value' => $metrics['pricing_searches']['available'] ? number_format($metrics['pricing_searches']['count']) : '—',
                'meta' => 'Gần nhất: '.$formatDate($metrics['pricing_searches']['latest_at']),
                'href' => route('muasamcong.index'),
                'accent' => 'text-indigo-700',
            ],
            [
                'label' => 'Nhà thầu đã lưu',
                'value' => $metrics['contractor_searches']['available'] ? number_format($metrics['contractor_searches']['count']) : '—',
                'meta' => 'Gần nhất: '.$formatDate($metrics['contractor_searches']['latest_at']),
                'href' => route('muasamcong.contractors.history'),
                'accent' => 'text-sky-700',
            ],
            [
                'label' => 'Job cần chú ý',
                'value' => $queue['available'] ? number_format($queue['counts']['in_progress'] + $queue['counts']['failed']) : '—',
                'meta' => number_format($queue['counts']['in_progress']).' đang xử lý · '.number_format($queue['counts']['failed']).' thất bại',
                'href' => route('muasamcong.contractors'),
                'accent' => $queue['counts']['failed'] > 0 ? 'text-red-700' : 'text-amber-700',
            ],
        ];

        if ($metrics['wishlist']['visible']) {
            $metricCards[] = [
                'label' => 'Wishlist của tôi',
                'value' => $metrics['wishlist']['available'] ? number_format($metrics['wishlist']['count']) : '—',
                'meta' => 'Dữ liệu riêng của tài khoản hiện tại',
                'href' => route('muasamcong.wishlist'),
                'accent' => 'text-rose-700',
            ];
        }

        $statusLabels = [
            'queued' => 'Đang chờ',
            'running' => 'Đang tải',
            'saving' => 'Đang lưu',
            'completed' => 'Hoàn tất',
            'failed' => 'Thất bại',
            'unknown' => 'Không xác định',
        ];
        $statusClasses = [
            'queued' => 'bg-slate-100 text-slate-700',
            'running' => 'bg-sky-100 text-sky-700',
            'saving' => 'bg-indigo-100 text-indigo-700',
            'completed' => 'bg-emerald-100 text-emerald-700',
            'failed' => 'bg-red-100 text-red-700',
            'unknown' => 'bg-amber-100 text-amber-700',
        ];
    @endphp

    <div class="space-y-8">
        <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Mua sắm công</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Tổng quan Mua sắm công</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                    Theo dõi dữ liệu, tình trạng xử lý và truy cập các không gian quản lý của Module.
                </p>
            </div>
            <a href="{{ route('muasamcong.index') }}"
               class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Mở Smart Pricing
            </a>
        </header>

        @unless ($health['tables_ready'])
            <div role="alert" class="rounded-2xl border border-amber-300 bg-amber-50 px-5 py-4 text-sm text-amber-900">
                <p class="font-semibold">Dữ liệu Dashboard chưa đầy đủ</p>
                <p class="mt-1">Có {{ $health['missing_table_count'] }} nhóm dữ liệu chưa sẵn sàng. Hãy kiểm tra migration của Module trước khi sử dụng.</p>
            </div>
        @endunless

        <section aria-labelledby="muasamcong-summary-heading">
            <h2 id="muasamcong-summary-heading" class="sr-only">Tóm tắt dữ liệu</h2>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($metricCards as $card)
                    <a href="{{ $card['href'] }}"
                       class="group min-w-0 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        <p class="text-sm font-medium text-slate-600">{{ $card['label'] }}</p>
                        <p class="mt-2 text-3xl font-bold {{ $card['accent'] }}">{{ $card['value'] }}</p>
                        <p class="mt-2 text-xs leading-5 text-slate-500">{{ $card['meta'] }}</p>
                    </a>
                @endforeach
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-3">
            <section class="min-w-0 xl:col-span-2" aria-labelledby="muasamcong-workspaces-heading">
                <div class="mb-4">
                    <h2 id="muasamcong-workspaces-heading" class="text-lg font-semibold text-slate-900">Không gian quản lý</h2>
                    <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">
                        Các liên kết chỉ mở workspace hiện có; Dashboard không thực hiện xóa, đồng bộ hay xuất dữ liệu trực tiếp.
                    </p>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <a href="{{ route('muasamcong.index') }}"
                       class="rounded-2xl border border-indigo-200 bg-indigo-50/60 p-5 transition hover:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Thuốc & đơn giá</p>
                        <h3 class="mt-2 text-lg font-bold text-slate-950">Smart Pricing</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Tra cứu giá thuốc, xem lịch sử tìm kiếm và mở quy trình đồng bộ theo quyền được cấp.</p>
                    </a>

                    <a href="{{ route('muasamcong.synced') }}"
                       class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-5 transition hover:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Dữ liệu nội bộ</p>
                        <h3 class="mt-2 text-lg font-bold text-slate-950">Danh sách đã đồng bộ</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Quản lý dữ liệu đã lưu, cấu hình biểu mẫu và thực hiện export trong đúng workspace.</p>
                        @if ($capabilities['sync_pricing'])
                            <span class="mt-3 inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Có quyền đồng bộ</span>
                        @endif
                    </a>

                    @if ($capabilities['manage_wishlist'])
                        <a href="{{ route('muasamcong.wishlist') }}"
                           class="rounded-2xl border border-rose-200 bg-rose-50/60 p-5 transition hover:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-500">
                            <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Theo dõi cá nhân</p>
                            <h3 class="mt-2 text-lg font-bold text-slate-950">Wishlist thuốc</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Mở danh sách thuốc riêng của tài khoản hiện tại và tiếp tục tra cứu khi cần.</p>
                        </a>
                    @endif

                    <a href="{{ route('muasamcong.hsmt') }}"
                       class="rounded-2xl border border-amber-200 bg-amber-50/60 p-5 transition hover:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500">
                        <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Hồ sơ mời thầu</p>
                        <h3 class="mt-2 text-lg font-bold text-slate-950">HSMT</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Tra cứu thông tin hồ sơ và đi tiếp tới dữ liệu gói thầu theo ngữ cảnh.</p>
                    </a>

                    <a href="{{ route('muasamcong.contractors') }}"
                       class="rounded-2xl border border-sky-200 bg-sky-50/60 p-5 transition hover:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-500">
                        <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Nhà thầu</p>
                        <h3 class="mt-2 text-lg font-bold text-slate-950">Tra cứu nhà thầu</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Khởi tạo tra cứu qua queue, theo dõi tiến độ và truy cập Manual Lots từ kết quả phù hợp.</p>
                    </a>

                    <a href="{{ route('muasamcong.contractors.history') }}"
                       class="rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-500">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kho lưu trữ</p>
                        <h3 class="mt-2 text-lg font-bold text-slate-950">Nhà thầu đã tra cứu</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Xem các nhà thầu và gói thầu đã được lưu trên server, không gọi lại upstream.</p>
                    </a>
                </div>
            </section>

            <section class="min-w-0" aria-labelledby="muasamcong-queue-heading">
                <div class="mb-4">
                    <h2 id="muasamcong-queue-heading" class="text-lg font-semibold text-slate-900">Tình trạng xử lý</h2>
                    <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">Số liệu trạng thái queue tại thời điểm tải trang.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <dl class="grid grid-cols-2 gap-4">
                        @foreach ([
                            ['Đang chờ', $queue['counts']['queued']],
                            ['Đang tải', $queue['counts']['running']],
                            ['Đang lưu', $queue['counts']['saving']],
                            ['Thất bại', $queue['counts']['failed']],
                        ] as [$label, $value])
                            <div class="rounded-xl bg-slate-50 p-4">
                                <dt class="text-xs font-medium text-slate-500">{{ $label }}</dt>
                                <dd class="mt-1 text-2xl font-bold text-slate-900">{{ $queue['available'] ? number_format($value) : '—' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                    <p class="mt-4 text-xs leading-5 text-slate-500">
                        Dashboard chỉ hiển thị trạng thái tổng hợp. Retry hoặc thao tác thay đổi dữ liệu phải thực hiện tại workspace chuyên trách.
                    </p>
                </div>
            </section>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="recent-searches-heading">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 p-5">
                    <div>
                        <h2 id="recent-searches-heading" class="font-bold text-slate-950">Tra cứu giá gần đây</h2>
                        <p class="mt-1 text-sm text-slate-500">Tối đa 5 snapshot, không tải payload kết quả.</p>
                    </div>
                    <a href="{{ route('muasamcong.index') }}" class="shrink-0 text-sm font-semibold text-indigo-700 hover:text-indigo-800">Mở tra cứu</a>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse ($dashboard['recent_pricing_searches'] as $search)
                        <a href="{{ route('muasamcong.index', ['q' => $search['keyword']]) }}"
                           class="block p-5 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <p class="font-semibold text-slate-900">{{ $search['keyword'] }}</p>
                                <time datetime="{{ $search['searched_at'] }}" class="text-xs text-slate-500">{{ $formatDate($search['searched_at']) }}</time>
                            </div>
                            <p class="mt-2 text-sm text-slate-500">
                                Đã tải {{ number_format($search['loaded_total']) }}/{{ number_format($search['source_total']) }} kết quả
                                @if ($search['source_partial'])
                                    <span class="font-semibold text-amber-700">· Dữ liệu một phần</span>
                                @endif
                            </p>
                        </a>
                    @empty
                        <div class="p-8 text-center text-sm text-slate-500">Chưa có lịch sử tra cứu giá để hiển thị.</div>
                    @endforelse
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="recent-jobs-heading">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 p-5">
                    <div>
                        <h2 id="recent-jobs-heading" class="font-bold text-slate-950">Job nhà thầu gần đây</h2>
                        <p class="mt-1 text-sm text-slate-500">Tối đa 5 job và không hiển thị exception thô.</p>
                    </div>
                    <a href="{{ route('muasamcong.contractors') }}" class="shrink-0 text-sm font-semibold text-indigo-700 hover:text-indigo-800">Mở nhà thầu</a>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse ($queue['recent'] as $job)
                        <a href="{{ $job['contractor_search_id'] ? route('muasamcong.contractors.history.show', $job['contractor_search_id']) : route('muasamcong.contractors') }}"
                           class="block p-5 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-slate-900">{{ $job['contractor_name'] ?: $job['contractor_code'] }}</p>
                                    <p class="mt-1 font-mono text-xs text-slate-500">{{ $job['contractor_code'] }}</p>
                                </div>
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses[$job['status']] }}">
                                    {{ $statusLabels[$job['status']] }}
                                </span>
                            </div>
                            <div class="mt-3 flex items-center gap-3">
                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100" aria-hidden="true">
                                    <div class="h-full rounded-full bg-indigo-500" style="width: {{ $job['progress'] }}%"></div>
                                </div>
                                <span class="text-xs font-medium text-slate-500">{{ $job['progress'] }}%</span>
                            </div>
                            <time datetime="{{ $job['created_at'] }}" class="mt-2 block text-xs text-slate-500">{{ $formatDate($job['created_at']) }}</time>
                        </a>
                    @empty
                        <div class="p-8 text-center text-sm text-slate-500">Chưa có job nhà thầu để hiển thị.</div>
                    @endforelse
                </div>
            </section>
        </div>

        @if ($capabilities['manage_config'])
            @php
                $configuration = $health['configuration'];
            @endphp
            <section class="min-w-0" aria-labelledby="muasamcong-integration-heading">
                <div class="mb-4">
                    <h2 id="muasamcong-integration-heading" class="text-lg font-semibold text-slate-900">Cấu hình & Personal Session</h2>
                    <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">Khu vực vận hành chỉ hiển thị cho tài khoản có quyền quản lý cấu hình.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 sm:p-6">
                    @if ($configuration && $configuration['available'])
                        @php
                            $environment = $configuration['environment'];
                            $session = $configuration['session'];
                            $sourceLabels = ['database' => 'Database', 'env' => 'Environment', 'none' => 'Chưa cấu hình'];
                        @endphp
                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <p class="text-xs font-medium text-slate-500">Cấu hình môi trường</p>
                                <p class="mt-2 font-bold {{ $environment['complete'] ? 'text-emerald-700' : 'text-amber-700' }}">
                                    {{ $environment['complete'] ? 'Đầy đủ' : $environment['present'].'/'.$environment['total'].' biến' }}
                                </p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <p class="text-xs font-medium text-slate-500">Personal Session</p>
                                <p class="mt-2 font-bold {{ $session['has_session'] ? 'text-emerald-700' : 'text-amber-700' }}">
                                    {{ $session['has_session'] ? 'Đã cấu hình' : 'Chưa cấu hình' }}
                                </p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <p class="text-xs font-medium text-slate-500">Nguồn Session</p>
                                <p class="mt-2 font-bold text-slate-900">{{ $sourceLabels[$session['source']] }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <p class="text-xs font-medium text-slate-500">Xác minh gần nhất</p>
                                <p class="mt-2 text-sm font-bold text-slate-900">{{ $formatDate($session['verified_at']) }}</p>
                            </div>
                        </div>
                    @else
                        <div role="alert" class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                            Không thể đọc tình trạng tích hợp lúc này. Chi tiết kỹ thuật đã được ghi ở server.
                        </div>
                    @endif

                    <div class="mt-5 flex flex-wrap gap-3">
                        <a href="{{ route('muasamcong.config') }}"
                           class="inline-flex min-h-11 items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                            Mở cấu hình
                        </a>
                        <a href="{{ route('muasamcong.session-tool.windows') }}"
                           class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                            Tải công cụ Session Windows
                        </a>
                    </div>
                </div>
            </section>
        @endif

        <p class="text-right text-xs text-slate-400">
            Dữ liệu tổng hợp lúc <time datetime="{{ $dashboard['generated_at'] }}">{{ $formatDate($dashboard['generated_at']) }}</time>
        </p>
    </div>
@endsection
