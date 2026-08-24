@extends('Admin::layouts.master')
@section('title', __('Request::request.versions'))
@section('content')
<div class="mx-auto w-full max-w-6xl p-3 sm:p-4 lg:p-6">
    @include('Request::partials.offline-runtime')
    @include('Request::partials.dashboard-back')

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Xem lại phiên bản</p>
            <h1 class="mt-1 break-words text-2xl font-semibold text-slate-900 sm:text-3xl">{{ $type->name }}</h1>
            <p class="mt-1 text-sm text-slate-600">Phiên bản đã phát hành là bất biến. Mỗi thẻ được so sánh với phiên bản ngay trước đó nếu có.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('request.admin.types.package', $type->public_id) }}" class="flex min-h-11 items-center rounded-lg border border-indigo-300 bg-indigo-50 px-4 py-2 text-sm font-medium text-indigo-700">Gói định nghĩa</a>
            <a href="{{ route('request.admin.types.designer', $type->public_id) }}" class="flex min-h-11 items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700">Quay lại trình thiết kế</a>
        </div>
    </div>

    @php
        $versions = $type->versions->values();
        $versionStatuses = [
            'draft' => 'Bản nháp',
            'published' => 'Đã phát hành',
            'retired' => 'Đã ngừng sử dụng',
        ];
    @endphp

    <div class="mt-6 space-y-4">
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
            @endphp

            <article class="overflow-hidden rounded-xl border border-slate-200 bg-white" aria-labelledby="version-{{ $version->version_number }}-title">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 p-4 sm:p-5">
                    <div>
                        <h2 id="version-{{ $version->version_number }}-title" class="text-lg font-semibold text-slate-900">Phiên bản {{ $version->version_number }}</h2>
                        <div class="mt-1 flex flex-wrap gap-2 text-xs text-slate-600">
                            <span class="rounded-full bg-slate-100 px-2 py-1">{{ $versionStatuses[$statusValue] ?? $statusValue }}</span>
                            @if($version->published_at)
                                <span>Phát hành lúc {{ $version->published_at->format('d/m/Y H:i') }}</span>
                            @endif
                        </div>
                    </div>
                    @if($version->canonical_checksum)
                        <code class="max-w-full break-all rounded-lg bg-slate-50 px-2 py-1 text-xs text-slate-600">{{ $version->canonical_checksum }}</code>
                    @endif
                </div>

                <div class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4 sm:p-5">
                    <div class="rounded-lg bg-slate-50 p-3">
                        <div class="text-xs text-slate-500">Số phần</div>
                        <div class="mt-1 text-xl font-semibold">{{ $sectionCount }}</div>
                        @if($previous)
                            <div class="text-xs text-slate-500">{{ $sectionCount - $previousSectionCount >= 0 ? '+' : '' }}{{ $sectionCount - $previousSectionCount }} so với v{{ $previous->version_number }}</div>
                        @endif
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3">
                        <div class="text-xs text-slate-500">Số trường</div>
                        <div class="mt-1 text-xl font-semibold">{{ $fieldCount }}</div>
                        @if($previous)
                            <div class="text-xs text-slate-500">{{ $fieldCount - $previousFieldCount >= 0 ? '+' : '' }}{{ $fieldCount - $previousFieldCount }} so với v{{ $previous->version_number }}</div>
                        @endif
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3">
                        <div class="text-xs text-slate-500">Cấp duyệt</div>
                        <div class="mt-1 text-xl font-semibold">{{ $version->stages->count() }}</div>
                        @if($previous)
                            <div class="text-xs text-slate-500">{{ $version->stages->count() - $previous->stages->count() >= 0 ? '+' : '' }}{{ $version->stages->count() - $previous->stages->count() }} so với v{{ $previous->version_number }}</div>
                        @endif
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3">
                        <div class="text-xs text-slate-500">Đối tượng</div>
                        <div class="mt-1 text-xl font-semibold">{{ $version->audiences->count() }}</div>
                        @if($previous)
                            <div class="text-xs text-slate-500">{{ $version->audiences->count() - $previous->audiences->count() >= 0 ? '+' : '' }}{{ $version->audiences->count() - $previous->audiences->count() }} so với v{{ $previous->version_number }}</div>
                        @endif
                    </div>
                </div>

                <details class="border-t border-slate-200">
                    <summary class="min-h-11 cursor-pointer px-4 py-3 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 sm:px-5">Xem chi tiết định nghĩa chuẩn hóa</summary>
                    <div class="grid gap-4 border-t border-slate-100 p-4 lg:grid-cols-2 sm:p-5">
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-slate-900">Schema biểu mẫu</h3>
                            <pre class="mt-2 max-h-96 overflow-auto rounded-lg bg-slate-950 p-3 text-xs text-slate-100"><code>{{ json_encode($version->form_schema_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</code></pre>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-slate-900">Các cấp phê duyệt</h3>
                            <pre class="mt-2 max-h-96 overflow-auto rounded-lg bg-slate-950 p-3 text-xs text-slate-100"><code>{{ json_encode($version->stages->map->only(['stage_key', 'name', 'position', 'mode', 'resolver_key', 'resolver_config_json', 'allow_reassignment'])->values()->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</code></pre>
                        </div>
                    </div>
                </details>
            </article>
        @endforeach
    </div>
</div>
@endsection
