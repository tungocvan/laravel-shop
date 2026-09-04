@extends('Admin::layouts.master')

@section('title', 'Dashboard KQLCNT - Mua sắm công')

@section('content')
    @php
        $capabilities = $dashboard['capabilities'];
        $kqlcnt = $dashboard['metrics']['kqlcnt'];
        $pricing = $dashboard['metrics'];
        $attention = $dashboard['attention'];
        $queue = $dashboard['queue'];
        $health = $dashboard['health'];
        $formatDate = static fn (?string $value): string => $value
            ? \Illuminate\Support\Carbon::parse($value)->timezone(config('app.timezone'))->format('d/m/Y H:i')
            : 'Chưa có dữ liệu';
        $formatMoney = static fn (float|int|null $value): string => number_format((float) ($value ?? 0), 0, ',', '.').' đ';

        $metricCards = [
            [
                'label' => 'TBMT có KQLCNT',
                'value' => $kqlcnt['available'] ? number_format($kqlcnt['notifications']) : '—',
                'meta' => 'Có dữ liệu canonical đang hoạt động',
                'accent' => 'text-indigo-700',
            ],
            [
                'label' => 'Dòng trúng thầu chuẩn hóa',
                'value' => $kqlcnt['available'] ? number_format($kqlcnt['award_items']) : '—',
                'meta' => 'Không cộng trùng nguồn API / Smart Pricing / Import',
                'accent' => 'text-violet-700',
            ],
            [
                'label' => 'Nhà thầu trúng',
                'value' => $kqlcnt['available'] ? number_format($kqlcnt['contractors']) : '—',
                'meta' => number_format($kqlcnt['investors']).' chủ đầu tư trong kho chuẩn hóa',
                'accent' => 'text-sky-700',
            ],
            [
                'label' => 'Tổng giá trị trúng thầu',
                'value' => $kqlcnt['available'] ? $formatMoney($kqlcnt['total_amount']) : '—',
                'meta' => 'Theo các dòng canonical đang hoạt động',
                'accent' => 'text-emerald-700',
            ],
            [
                'label' => 'Cần bổ sung KQLCNT',
                'value' => number_format($attention['missing_detail']),
                'meta' => 'TBMT trong lịch sử chưa có snapshot KQLCNT',
                'accent' => $attention['missing_detail'] > 0 ? 'text-amber-700' : 'text-emerald-700',
            ],
            [
                'label' => 'Đồng bộ canonical gần nhất',
                'value' => $kqlcnt['latest_synced_at'] ? $formatDate($kqlcnt['latest_synced_at']) : '—',
                'meta' => 'Cập nhật từ dữ liệu đã lưu, không gọi upstream',
                'accent' => 'text-slate-800',
            ],
        ];
    @endphp

    <div class="space-y-7">
        <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 xl:flex-row xl:items-end xl:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Mua sắm công · KQLCNT</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Dashboard dữ liệu KQLCNT</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                    Trung tâm vận hành dữ liệu kết quả lựa chọn nhà thầu: theo dõi kho chuẩn hóa, phát hiện dữ liệu cần bổ sung và truy cập nhanh quy trình tra cứu → recovery → canonical.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('muasamcong.contractors') }}"
                   class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2">
                    Tra cứu nhà thầu
                </a>
                <a href="{{ route('muasamcong.kqlcnt-awards.index') }}"
                   class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Mở KQLCNT chuẩn hóa
                </a>
            </div>
        </header>

        @unless ($health['tables_ready'])
            <div role="alert" class="rounded-2xl border border-amber-300 bg-amber-50 px-5 py-4 text-sm text-amber-900">
                <p class="font-semibold">Dữ liệu Dashboard chưa đầy đủ</p>
                <p class="mt-1">Có {{ $health['missing_table_count'] }} nhóm dữ liệu chưa sẵn sàng. Hãy kiểm tra migration của Module.</p>
            </div>
        @endunless

        <section aria-labelledby="kqlcnt-metrics-heading">
            <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 id="kqlcnt-metrics-heading" class="text-lg font-semibold text-slate-900">Tổng quan KQLCNT</h2>
                    <p class="mt-1 text-sm text-slate-500">Số liệu ưu tiên lấy từ kho canonical KQLCNT đang hoạt động.</p>
                </div>
                <p class="text-xs text-slate-400">Giá trị 30 ngày gần nhất: <span class="font-semibold text-slate-600">{{ $formatMoney($kqlcnt['last_30_days_amount']) }}</span></p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($metricCards as $card)
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm font-medium text-slate-600">{{ $card['label'] }}</p>
                        <p class="mt-2 text-2xl font-bold tracking-tight {{ $card['accent'] }}">{{ $card['value'] }}</p>
                        <p class="mt-2 text-xs leading-5 text-slate-500">{{ $card['meta'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-3">
            <section class="min-w-0 xl:col-span-2" aria-labelledby="canonical-workspace-heading">
                <div class="rounded-2xl border border-indigo-200 bg-gradient-to-br from-indigo-50 via-white to-violet-50 p-6 shadow-sm">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Workspace chính</p>
                            <h2 id="canonical-workspace-heading" class="mt-2 text-xl font-bold text-slate-950">Dữ liệu chi tiết KQLCNT đã chuẩn hóa</h2>
                            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                                Kho canonical hợp nhất dữ liệu API, Smart Pricing, Manual và Excel; dùng để tra cứu, chỉnh sửa, export và làm nguồn thống kê mà không double-count giữa các nguồn vật lý.
                            </p>
                            <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold">
                                <span class="rounded-full bg-white px-3 py-1.5 text-indigo-700 ring-1 ring-indigo-200">{{ number_format($kqlcnt['award_items']) }} dòng</span>
                                <span class="rounded-full bg-white px-3 py-1.5 text-sky-700 ring-1 ring-sky-200">{{ number_format($kqlcnt['notifications']) }} TBMT</span>
                                <span class="rounded-full bg-white px-3 py-1.5 text-emerald-700 ring-1 ring-emerald-200">{{ $formatMoney($kqlcnt['total_amount']) }}</span>
                            </div>
                        </div>
                        <a href="{{ route('muasamcong.kqlcnt-awards.index') }}"
                           class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            Mở danh mục KQLCNT
                        </a>
                    </div>
                </div>

                <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-col gap-2 border-b border-slate-200 p-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="font-bold text-slate-950">KQLCNT đồng bộ gần đây</h3>
                            <p class="mt-1 text-sm text-slate-500">Tối đa 5 dòng canonical gần nhất; không tải raw payload.</p>
                        </div>
                        <a href="{{ route('muasamcong.kqlcnt-awards.index') }}" class="text-sm font-semibold text-indigo-700 hover:text-indigo-800">Xem toàn bộ</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">TBMT</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Chủ đầu tư</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Nhà thầu / Thuốc</th>
                                    <th class="px-4 py-3 text-right font-semibold text-slate-600">Giá trị</th>
                                    <th class="px-4 py-3 text-right font-semibold text-slate-600">Đồng bộ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($dashboard['recent_kqlcnt'] as $row)
                                    <tr class="align-top hover:bg-slate-50/70">
                                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $row['notify_no'] ?: '—' }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $row['investor_name'] ?: '—' }}</td>
                                        <td class="px-4 py-3 text-slate-700">
                                            <div class="font-medium text-slate-900">{{ $row['contractor_name'] ?: '—' }}</div>
                                            <div class="mt-1 text-xs text-slate-500">{{ $row['medicine_name'] ?: '—' }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-right font-semibold tabular-nums text-slate-800">{{ $formatMoney($row['amount']) }}</td>
                                        <td class="px-4 py-3 text-right text-xs text-slate-500">{{ $formatDate($row['synced_at']) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">Chưa có dữ liệu KQLCNT canonical để hiển thị.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <aside class="min-w-0" aria-labelledby="attention-heading">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Vận hành</p>
                        <h2 id="attention-heading" class="mt-1 text-lg font-bold text-slate-950">Cần xử lý</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-500">Ưu tiên các khoảng trống dữ liệu trước khi thống kê hoặc export.</p>
                    </div>
                    <div class="mt-5 space-y-3">
                        <a href="{{ route('muasamcong.contractors.history') }}" class="flex items-center justify-between gap-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 hover:border-amber-300">
                            <span class="text-sm font-medium text-amber-900">TBMT chưa có chi tiết KQLCNT</span>
                            <span class="text-lg font-bold text-amber-800">{{ number_format($attention['missing_detail']) }}</span>
                        </a>
                        <a href="{{ route('muasamcong.contractors.history') }}" class="flex items-center justify-between gap-4 rounded-xl border border-violet-200 bg-violet-50 px-4 py-3 hover:border-violet-300">
                            <span class="text-sm font-medium text-violet-900">Có snapshot nhưng chưa canonical</span>
                            <span class="text-lg font-bold text-violet-800">{{ number_format($attention['not_persisted']) }}</span>
                        </a>
                        <a href="{{ route('muasamcong.contractors.history') }}" class="flex items-center justify-between gap-4 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 hover:border-sky-300">
                            <span class="text-sm font-medium text-sky-900">Nguồn Import / Mixed cần theo dõi</span>
                            <span class="text-lg font-bold text-sky-800">{{ number_format($attention['imported_or_mixed']) }}</span>
                        </a>
                        <a href="{{ route('muasamcong.contractors') }}" class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 hover:border-slate-300">
                            <span class="text-sm font-medium text-slate-800">Job nhà thầu thất bại</span>
                            <span class="text-lg font-bold {{ $attention['failed_jobs'] > 0 ? 'text-red-700' : 'text-emerald-700' }}">{{ number_format($attention['failed_jobs']) }}</span>
                        </a>
                    </div>
                </div>
            </aside>
        </div>

        <section aria-labelledby="workflow-heading">
            <div class="mb-4">
                <h2 id="workflow-heading" class="text-lg font-semibold text-slate-900">Quy trình nghiệp vụ</h2>
                <p class="mt-1 text-sm text-slate-500">Đi từ nguồn tra cứu đến kho KQLCNT chuẩn hóa theo đúng thứ tự vận hành.</p>
            </div>
            <div class="grid gap-3 lg:grid-cols-4">
                <a href="{{ route('muasamcong.contractors') }}" class="rounded-2xl border border-sky-200 bg-sky-50/60 p-5 hover:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <p class="text-xs font-bold text-sky-700">01</p>
                    <h3 class="mt-2 font-bold text-slate-950">Tra cứu nhà thầu</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Tìm nhà thầu và tải lịch sử TBMT qua queue.</p>
                </a>
                <a href="{{ route('muasamcong.contractors.history') }}" class="rounded-2xl border border-slate-200 bg-white p-5 hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-500">
                    <p class="text-xs font-bold text-slate-500">02</p>
                    <h3 class="mt-2 font-bold text-slate-950">Lịch sử & TBMT</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Chọn nhà thầu đã lưu và mở KQLCNT Recovery theo từng lịch sử.</p>
                </a>
                <a href="{{ route('muasamcong.contractors.history') }}" class="rounded-2xl border border-amber-200 bg-amber-50/60 p-5 hover:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <p class="text-xs font-bold text-amber-700">03</p>
                    <h3 class="mt-2 font-bold text-slate-950">Recovery / bổ sung</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Đồng bộ API, Smart Pricing hoặc bổ sung Excel khi dữ liệu thiếu.</p>
                </a>
                <a href="{{ route('muasamcong.kqlcnt-awards.index') }}" class="rounded-2xl border border-indigo-300 bg-indigo-50 p-5 hover:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <p class="text-xs font-bold text-indigo-700">04 · PRIMARY</p>
                    <h3 class="mt-2 font-bold text-slate-950">KQLCNT chuẩn hóa</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Tra cứu, chỉnh sửa, export và sử dụng làm nguồn thống kê canonical.</p>
                </a>
            </div>
        </section>

        <section aria-labelledby="tools-heading">
            <div class="mb-4">
                <h2 id="tools-heading" class="text-lg font-semibold text-slate-900">Công cụ hỗ trợ</h2>
                <p class="mt-1 text-sm text-slate-500">Các workspace phụ trợ vẫn sẵn sàng nhưng không cạnh tranh với luồng KQLCNT chính.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <a href="{{ route('muasamcong.index') }}" class="rounded-xl border border-slate-200 bg-white p-4 hover:border-indigo-300">
                    <p class="font-semibold text-slate-900">Smart Pricing</p>
                    <p class="mt-1 text-xs leading-5 text-slate-500">Tra cứu giá thuốc và dữ liệu tham chiếu.</p>
                </a>
                <a href="{{ route('muasamcong.synced') }}" class="rounded-xl border border-slate-200 bg-white p-4 hover:border-emerald-300">
                    <p class="font-semibold text-slate-900">Giá đã đồng bộ</p>
                    <p class="mt-1 text-xs leading-5 text-slate-500">{{ number_format($pricing['pricing_results']['count']) }} dòng pricing đã lưu.</p>
                </a>
                <a href="{{ route('muasamcong.hsmt') }}" class="rounded-xl border border-slate-200 bg-white p-4 hover:border-amber-300">
                    <p class="font-semibold text-slate-900">HSMT</p>
                    <p class="mt-1 text-xs leading-5 text-slate-500">Tra cứu hồ sơ và ngữ cảnh gói thầu.</p>
                </a>
                @if ($capabilities['manage_wishlist'])
                    <a href="{{ route('muasamcong.wishlist') }}" class="rounded-xl border border-slate-200 bg-white p-4 hover:border-rose-300">
                        <p class="font-semibold text-slate-900">Wishlist</p>
                        <p class="mt-1 text-xs leading-5 text-slate-500">{{ number_format((int) $pricing['wishlist']['count']) }} thuốc đang theo dõi.</p>
                    </a>
                @else
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="font-semibold text-slate-700">Lịch sử tra cứu giá</p>
                        <p class="mt-1 text-xs leading-5 text-slate-500">{{ number_format($pricing['pricing_searches']['count']) }} snapshot đã lưu.</p>
                    </div>
                @endif
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4" aria-labelledby="system-status-heading">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-slate-600">
                    <h2 id="system-status-heading" class="font-bold text-slate-900">Hệ thống</h2>
                    <span>Queue: <strong class="{{ $queue['counts']['failed'] > 0 ? 'text-red-700' : 'text-emerald-700' }}">{{ $queue['counts']['failed'] > 0 ? number_format($queue['counts']['failed']).' lỗi' : 'Bình thường' }}</strong></span>
                    <span>Đang xử lý: <strong class="text-slate-800">{{ number_format($queue['counts']['in_progress']) }}</strong></span>
                    @if ($capabilities['manage_config'] && $health['configuration'] && $health['configuration']['available'])
                        <span>Session: <strong class="{{ $health['configuration']['session']['has_session'] ? 'text-emerald-700' : 'text-amber-700' }}">{{ $health['configuration']['session']['has_session'] ? 'Đã cấu hình' : 'Chưa cấu hình' }}</strong></span>
                        <span>Environment: <strong class="{{ $health['configuration']['environment']['complete'] ? 'text-emerald-700' : 'text-amber-700' }}">{{ $health['configuration']['environment']['complete'] ? 'Đầy đủ' : 'Chưa đủ' }}</strong></span>
                    @endif
                </div>
                @if ($capabilities['manage_config'])
                    <a href="{{ route('muasamcong.config') }}" class="text-sm font-semibold text-indigo-700 hover:text-indigo-800">Mở cấu hình →</a>
                @endif
            </div>
        </section>

        <p class="text-right text-xs text-slate-400">
            Dữ liệu tổng hợp lúc <time datetime="{{ $dashboard['generated_at'] }}">{{ $formatDate($dashboard['generated_at']) }}</time>
        </p>
    </div>
@endsection
