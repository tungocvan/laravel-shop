@extends('Admin::layouts.master')
@section('title', __('Request::request.versions'))
@section('content')
<div class="mx-auto w-full max-w-6xl p-3 sm:p-4 lg:p-6">
    @include('Request::partials.offline-runtime')
    @include('Request::partials.dashboard-back')

    @php
        $versions = $type->versions->values();
        $draftVersion = $type->activeDraft;
        $publishedVersion = $type->currentPublishedVersion;
        $versionStatuses = [
            'draft' => 'Bản nháp',
            'published' => 'Đã phát hành',
            'retired' => 'Đã ngừng sử dụng',
        ];
    @endphp

    <header class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Release review</p>
                <h1 class="mt-1 break-words text-2xl font-semibold text-slate-900 sm:text-3xl">Lịch sử phiên bản · {{ $type->name }}</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Theo dõi dòng thời gian phát hành, đối chiếu thay đổi và kiểm tra định nghĩa chuẩn hóa. Phiên bản đã phát hành là bất biến và màn hình này chỉ dùng để xem lại.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('request.admin.types') }}" class="flex min-h-11 items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700">Quay lại quản lý loại đề nghị</a>
                <a href="{{ route('request.admin.types.designer', $type->public_id) }}" class="flex min-h-11 items-center rounded-lg border border-indigo-300 bg-indigo-50 px-4 py-2 text-sm font-medium text-indigo-700">Quay lại trình thiết kế</a>
            </div>
        </div>

        <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4"><div class="text-xs font-semibold uppercase tracking-wide text-amber-700">Bản nháp hiện tại</div><div class="mt-1 text-xl font-bold text-amber-950">{{ $draftVersion ? 'v'.$draftVersion->version_number : 'Không có' }}</div></div>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4"><div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Phiên bản hiện hành</div><div class="mt-1 text-xl font-bold text-emerald-950">{{ $publishedVersion ? 'v'.$publishedVersion->version_number : 'Chưa phát hành' }}</div></div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tổng phiên bản</div><div class="mt-1 text-xl font-bold text-slate-900">{{ $versions->count() }}</div></div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nguyên tắc</div><div class="mt-1 text-sm font-semibold text-slate-900">Published = read-only</div></div>
        </div>
    </header>

    <section class="mt-6" aria-labelledby="release-timeline-title">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div><h2 id="release-timeline-title" class="text-lg font-semibold text-slate-900">Dòng thời gian phát hành</h2><p class="mt-1 text-sm text-slate-600">Phiên bản mới nhất ở trên. Mỗi phiên bản có thể được so sánh với phiên bản ngay trước đó.</p></div>
            <a href="{{ route('request.admin.types.package', $type->public_id) }}" class="flex min-h-11 items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700">Gói định nghĩa</a>
        </div>

        <div class="mt-4 space-y-4">
            @if($versions->isEmpty())
                <div class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-600">Chưa có phiên bản nào.</div>
            @endif

            @foreach($versions as $index => $version)
                @php
                    $previous = $versions->get($index + 1);
                    $schema = (array) $version->form_schema_json;
                    $previousSchema = (array) ($previous?->form_schema_json ?? []);
                    $sectionCount = count((array) ($schema['sections'] ?? []));
                    $previousSectionCount = count((array) ($previousSchema['sections'] ?? []));
                    $fieldCount = collect((array) ($schema['sections'] ?? []))->sum(fn ($section) => count((array) ($section['fields'] ?? [])));
                    $previousFieldCount = collect((array) ($previousSchema['sections'] ?? []))->sum(fn ($section) => count((array) ($section['fields'] ?? [])));
                    $statusValue = $version->status->value;
                    $creator = $version->created_by ? $versionActors->get($version->created_by) : null;
                    $publisher = $version->published_by ? $versionActors->get($version->published_by) : null;
                    $isCurrent = $publishedVersion?->id === $version->id;
                    $isDraft = $draftVersion?->id === $version->id;
                @endphp

                <article class="overflow-hidden rounded-xl border {{ $isCurrent ? 'border-emerald-300 ring-1 ring-emerald-100' : ($isDraft ? 'border-amber-300' : 'border-slate-200') }} bg-white" aria-labelledby="version-{{ $version->version_number }}-title">
                    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 p-4 sm:p-5">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 id="version-{{ $version->version_number }}-title" class="text-lg font-semibold text-slate-900">Phiên bản {{ $version->version_number }}</h3>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $versionStatuses[$statusValue] ?? $statusValue }}</span>
                                @if($isCurrent)<span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">Phiên bản hiện hành</span>@endif
                                @if($isDraft)<span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Bản nháp hiện tại</span>@endif
                            </div>
                            <dl class="mt-3 grid gap-x-6 gap-y-1 text-xs text-slate-600 sm:grid-cols-2">
                                <div><dt class="inline font-semibold text-slate-700">Người tạo:</dt> <dd class="inline">{{ $creator?->displayName ?? ($version->created_by ? 'User #'.$version->created_by : 'Không ghi nhận') }}</dd></div>
                                <div><dt class="inline font-semibold text-slate-700">Người phát hành:</dt> <dd class="inline">{{ $publisher?->displayName ?? ($version->published_by ? 'User #'.$version->published_by : 'Chưa phát hành') }}</dd></div>
                                @if($version->published_at)<div class="sm:col-span-2"><dt class="inline font-semibold text-slate-700">Phát hành lúc:</dt> <dd class="inline">{{ $version->published_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</dd></div>@endif
                            </dl>
                        </div>
                        @if($version->canonical_checksum)<code class="max-w-full break-all rounded-lg bg-slate-50 px-2 py-1 text-xs text-slate-600">{{ $version->canonical_checksum }}</code>@endif
                    </div>

                    <div class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4 sm:p-5">
                        @foreach([
                            ['label' => 'Số phần', 'value' => $sectionCount, 'previous' => $previousSectionCount],
                            ['label' => 'Số trường', 'value' => $fieldCount, 'previous' => $previousFieldCount],
                            ['label' => 'Cấp duyệt', 'value' => $version->stages->count(), 'previous' => $previous?->stages->count()],
                            ['label' => 'Đối tượng', 'value' => $version->audiences->count(), 'previous' => $previous?->audiences->count()],
                        ] as $metric)
                            <div class="rounded-lg bg-slate-50 p-3"><div class="text-xs text-slate-500">{{ $metric['label'] }}</div><div class="mt-1 text-xl font-semibold">{{ $metric['value'] }}</div>@if($previous)<div class="text-xs text-slate-500">{{ $metric['value'] - $metric['previous'] >= 0 ? '+' : '' }}{{ $metric['value'] - $metric['previous'] }} so với v{{ $previous->version_number }}</div>@endif</div>
                        @endforeach
                    </div>

                    @if($previous)
                        <div class="border-t border-slate-100 bg-slate-50/60 px-4 py-3 text-xs text-slate-600 sm:px-5"><strong class="text-slate-800">So sánh với phiên bản trước:</strong> các chỉ số phía trên hiển thị chênh lệch so với v{{ $previous->version_number }}.</div>
                    @endif

                    <details class="border-t border-slate-200">
                        <summary class="min-h-11 cursor-pointer px-4 py-3 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 sm:px-5">Xem chi tiết định nghĩa chuẩn hóa</summary>
                        <div class="grid gap-4 border-t border-slate-100 p-4 lg:grid-cols-2 sm:p-5">
                            <div class="min-w-0"><h4 class="text-sm font-semibold text-slate-900">Schema biểu mẫu</h4><pre class="mt-2 max-h-96 overflow-auto rounded-lg bg-slate-950 p-3 text-xs text-slate-100"><code>{{ json_encode($version->form_schema_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</code></pre></div>
                            <div class="min-w-0"><h4 class="text-sm font-semibold text-slate-900">Các cấp phê duyệt</h4><pre class="mt-2 max-h-96 overflow-auto rounded-lg bg-slate-950 p-3 text-xs text-slate-100"><code>{{ json_encode($version->stages->map->only(['stage_key', 'name', 'position', 'mode', 'resolver_key', 'resolver_config_json', 'allow_reassignment'])->values()->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</code></pre></div>
                        </div>
                    </details>
                </article>
            @endforeach
        </div>
    </section>
</div>
@endsection
