@extends('Admin::layouts.master')
@section('title', 'Tổng quan Đề nghị')
@section('content')
<div class="mx-auto max-w-7xl space-y-6 p-4 sm:p-6">
    @include('Request::partials.offline-runtime')
    @include('Request::partials.workspace-navigation')

    @php
        $capabilities = $dashboard['capabilities'];
        $ownCounts = $dashboard['own_counts'];
        $approvalCounts = $dashboard['approval_counts'];
    @endphp

    <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Không gian làm việc</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900 sm:text-3xl">Tổng quan Đề nghị</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-600">Theo dõi các đề nghị của bạn, công việc cần xử lý và truy cập nhanh các chức năng được cấp quyền.</p>
        </div>

        @if($capabilities['create'])
            <a href="{{ route('request.catalog') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                + Tạo đề nghị
            </a>
        @endif
    </header>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Tóm tắt công việc">
        @if($capabilities['view_own'])
            <a href="{{ route('request.mine', ['status' => 'pending']) }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-300">
                <div class="text-sm font-medium text-slate-600">Đề nghị đang xử lý</div>
                <div class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($ownCounts['active']) }}</div>
                <div class="mt-2 text-xs text-slate-500">Các đề nghị của bạn đang chờ xử lý.</div>
            </a>

            <a href="{{ route('request.mine', ['status' => 'returned']) }}" class="rounded-2xl border {{ $ownCounts['returned'] > 0 ? 'border-orange-300 bg-orange-50' : 'border-slate-200 bg-white' }} p-5 shadow-sm transition hover:border-orange-400">
                <div class="text-sm font-medium text-slate-600">Cần bạn bổ sung</div>
                <div class="mt-2 text-3xl font-bold {{ $ownCounts['returned'] > 0 ? 'text-orange-700' : 'text-slate-900' }}">{{ number_format($ownCounts['returned']) }}</div>
                <div class="mt-2 text-xs text-slate-500">Ưu tiên hoàn thiện các đề nghị đã được trả lại.</div>
            </a>
        @endif

        @if($capabilities['approve'])
            <a href="{{ route('request.inbox') }}" class="rounded-2xl border {{ $approvalCounts['pending'] > 0 ? 'border-indigo-300 bg-indigo-50' : 'border-slate-200 bg-white' }} p-5 shadow-sm transition hover:border-indigo-400">
                <div class="text-sm font-medium text-slate-600">Chờ bạn duyệt</div>
                <div class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($approvalCounts['pending']) }}</div>
                <div class="mt-2 text-xs text-slate-500">Tác vụ phê duyệt đang được giao cho bạn.</div>
            </a>

            <a href="{{ route('request.inbox') }}" class="rounded-2xl border {{ $approvalCounts['overdue'] > 0 ? 'border-red-300 bg-red-50' : ($approvalCounts['warning'] > 0 ? 'border-amber-300 bg-amber-50' : 'border-slate-200 bg-white') }} p-5 shadow-sm transition hover:border-amber-400">
                <div class="text-sm font-medium text-slate-600">SLA cần chú ý</div>
                <div class="mt-2 flex items-baseline gap-3">
                    <span class="text-3xl font-bold text-slate-900">{{ number_format($approvalCounts['warning'] + $approvalCounts['overdue']) }}</span>
                    <span class="text-xs font-semibold text-slate-500">{{ number_format($approvalCounts['overdue']) }} quá hạn</span>
                </div>
                <div class="mt-2 text-xs text-slate-500">Sắp quá hạn hoặc đã quá hạn xử lý.</div>
            </a>
        @endif
    </section>

    <div class="grid gap-6 xl:grid-cols-2">
        @if($capabilities['approve'])
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 p-5">
                    <div>
                        <h2 class="font-bold text-slate-900">Việc cần bạn xử lý</h2>
                        <p class="mt-1 text-sm text-slate-500">Ưu tiên theo hạn xử lý gần nhất.</p>
                    </div>
                    <a href="{{ route('request.inbox') }}" class="text-sm font-semibold text-indigo-700">Xem tất cả</a>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse($dashboard['pending_tasks'] as $task)
                        @php($item = $task->run?->requestInstance)
                        @if($item)
                            <a href="{{ route('request.show', $item->public_id) }}" class="block p-5 transition hover:bg-slate-50">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="font-mono text-xs text-slate-500">{{ $item->request_number }}</span>
                                    @if($task->due_at)
                                        <time datetime="{{ $task->due_at->toIso8601String() }}" class="text-xs font-medium text-slate-600">
                                            Hạn {{ $task->due_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                                        </time>
                                    @endif
                                </div>
                                <div class="mt-2 font-semibold text-slate-900">{{ $item->title_snapshot }}</div>
                                <div class="mt-1 text-sm text-slate-500">{{ $task->stage_name_snapshot }}</div>
                            </a>
                        @endif
                    @empty
                        <div class="p-8 text-center text-sm text-slate-500">Bạn không có đề nghị nào đang chờ duyệt.</div>
                    @endforelse
                </div>
            </section>
        @endif

        @if($capabilities['view_own'])
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 p-5">
                    <div>
                        <h2 class="font-bold text-slate-900">Đề nghị gần đây của tôi</h2>
                        <p class="mt-1 text-sm text-slate-500">Theo dõi các thay đổi mới nhất.</p>
                    </div>
                    <a href="{{ route('request.mine') }}" class="text-sm font-semibold text-indigo-700">Xem tất cả</a>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse($dashboard['recent_requests'] as $item)
                        <a href="{{ route('request.show', $item->public_id) }}" class="block p-5 transition hover:bg-slate-50">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="font-mono text-xs text-slate-500">{{ $item->request_number }}</span>
                                <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">{{ __('Request::request.statuses.'.$item->status->value) }}</span>
                            </div>
                            <div class="mt-2 font-semibold text-slate-900">{{ $item->title_snapshot }}</div>
                            <time datetime="{{ $item->updated_at?->toIso8601String() }}" class="mt-1 block text-xs text-slate-500">Cập nhật {{ $item->updated_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</time>
                        </a>
                    @empty
                        <div class="p-8 text-center text-sm text-slate-500">Bạn chưa có đề nghị nào.</div>
                    @endforelse
                </div>
            </section>
        @endif
    </div>

    @if($capabilities['manage_groups'] || $capabilities['manage_types'] || $capabilities['reports'] || $capabilities['operations'])
        <section class="rounded-2xl border border-slate-200 bg-slate-50 p-5 sm:p-6">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Quản trị</p>
                <h2 class="mt-1 text-lg font-bold text-slate-900">Quản trị Đề nghị</h2>
                <p class="mt-1 text-sm text-slate-600">Các chức năng quản trị chỉ xuất hiện khi tài khoản có quyền tương ứng.</p>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @if($capabilities['manage_groups'])
                    <a href="{{ route('request.admin.groups') }}" class="min-h-24 rounded-xl border border-slate-200 bg-white p-4 font-semibold text-slate-800 shadow-sm hover:border-indigo-300">Nhóm đề nghị</a>
                @endif
                @if($capabilities['manage_types'])
                    <a href="{{ route('request.admin.types') }}" class="min-h-24 rounded-xl border border-slate-200 bg-white p-4 font-semibold text-slate-800 shadow-sm hover:border-indigo-300">Loại đề nghị</a>
                @endif
                @if($capabilities['reports'])
                    <a href="{{ route('request.admin.reports') }}" class="min-h-24 rounded-xl border border-slate-200 bg-white p-4 font-semibold text-slate-800 shadow-sm hover:border-indigo-300">Báo cáo</a>
                @endif
                @if($capabilities['operations'])
                    <a href="{{ route('request.admin.operations') }}" class="min-h-24 rounded-xl border border-slate-200 bg-white p-4 font-semibold text-slate-800 shadow-sm hover:border-indigo-300">Vận hành</a>
                @endif
            </div>
        </section>
    @endif
</div>
@endsection
