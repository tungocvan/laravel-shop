@extends('Admin::layouts.master')

@section('title', __('Request::definition_package.title'))

@section('content')
<div class="mx-auto max-w-5xl space-y-6 p-3 sm:p-4 lg:p-6">
    @include('Request::partials.dashboard-back')

    <header class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Definition package</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-900 sm:text-3xl">Gói định nghĩa · {{ $type->name }}</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Xuất bản phát hành hiện hành hoặc nhập một gói JSON theo quy trình kiểm tra trước. Preview không ghi dữ liệu; import hợp lệ chỉ tạo bản nháp mới để tiếp tục review trong Designer.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('request.admin.types.versions', $type->public_id) }}" class="flex min-h-11 items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700">Quay lại lịch sử phiên bản</a>
                <a href="{{ route('request.admin.types.designer', $type->public_id) }}" class="flex min-h-11 items-center rounded-lg border border-indigo-300 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700">Quay lại trình thiết kế</a>
            </div>
        </div>
        <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl bg-slate-50 p-4"><div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Mã loại</div><div class="mt-1 font-mono text-sm font-semibold text-slate-900">{{ $type->code }}</div></div>
            <div class="rounded-xl bg-emerald-50 p-4"><div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Phiên bản hiện hành</div><div class="mt-1 text-lg font-bold text-emerald-950">{{ $type->currentPublishedVersion ? 'v'.$type->currentPublishedVersion->version_number : 'Chưa phát hành' }}</div></div>
            <div class="rounded-xl bg-amber-50 p-4"><div class="text-xs font-semibold uppercase tracking-wide text-amber-700">Bản nháp hiện tại</div><div class="mt-1 text-lg font-bold text-amber-950">{{ $type->activeDraft ? 'v'.$type->activeDraft->version_number : 'Không có' }}</div></div>
            <div class="rounded-xl bg-slate-50 p-4"><div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Giới hạn tệp</div><div class="mt-1 text-sm font-semibold text-slate-900">Tệp JSON tối đa 256 KB</div></div>
        </div>
    </header>

    @if($errors->any())
        <section class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
            <div class="font-semibold">{{ __('Request::definition_package.invalid') }}</div>
            <ul class="mt-2 list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </section>
    @endif

    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm" aria-labelledby="definition-package-export-title">
        <h2 id="definition-package-export-title" class="font-bold text-gray-900">Xuất bản phát hành hiện hành</h2>
        <p class="mt-1 text-sm text-gray-600">Chỉ phiên bản đã phát hành hiện hành mới được đóng gói để tải xuống. Bản nháp không được export như một release package.</p>
        <div class="mt-4">@can('exportDefinition', $type) @if($type->currentPublishedVersion)<a href="{{ route('request.admin.types.package.download', $type->public_id) }}" class="inline-flex min-h-11 items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">{{ __('Request::definition_package.download') }}</a>@else<p class="text-sm text-amber-700">{{ __('Request::definition_package.published_required') }}</p>@endif @endcan</div>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm" aria-labelledby="definition-package-import-title">
        <div><h2 id="definition-package-import-title" class="font-bold text-gray-900">Nhập gói định nghĩa</h2><p class="mt-1 text-sm text-gray-600">Quy trình bắt buộc: tải tệp → Kiểm tra trước khi nhập → xem phạm vi thay đổi và ánh xạ → xác nhận import.</p></div>
        <div class="mt-4 rounded-xl border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800"><strong>Preview không ghi dữ liệu.</strong> Hệ thống chỉ validate package, resolve mapping và tính diff với phiên bản hiện hành.</div>
        <div class="mt-3 rounded-xl border border-indigo-200 bg-indigo-50 p-3 text-sm text-indigo-800"><strong>Import chỉ tạo bản nháp mới.</strong> Sau import bạn vẫn phải review và publish qua Designer theo workflow bình thường.</div>

        @if($type->active_draft_version_id !== null)
            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">{{ __('Request::definition_package.active_draft_warning') }}</div>
        @elseif($type->current_published_version_id === null)
            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">{{ __('Request::definition_package.published_required') }}</div>
        @else
            @can('importDefinition', $type)
                <form method="POST" action="{{ route('request.admin.types.package.preview', $type->public_id) }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                    @csrf
                    <div><label for="definition-package" class="mb-1 block text-sm font-semibold text-gray-800">{{ __('Request::definition_package.package_file') }}</label><input id="definition-package" type="file" name="package" accept="application/json,.json" required class="block min-h-11 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm"><p class="mt-1 text-xs text-gray-500">Tệp JSON tối đa 256 KB.</p></div>
                    <details class="rounded-xl border border-slate-200 bg-slate-50 p-3"><summary class="cursor-pointer text-sm font-semibold text-slate-700">Ánh xạ bắt buộc / cấu hình nâng cao</summary><div class="mt-3"><label for="definition-mappings" class="mb-1 block text-sm font-semibold text-gray-800">{{ __('Request::definition_package.mappings') }}</label><textarea id="definition-mappings" name="mappings_json" rows="5" spellcheck="false" class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 font-mono text-sm">{{ old('mappings_json', $mappingsJson) }}</textarea><p class="mt-1 text-xs text-gray-500">{{ __('Request::definition_package.mappings_help') }}</p></div></details>
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Kiểm tra trước khi nhập</button>
                </form>
            @endcan
        @endif
    </section>

    @if(is_array($preview))
        <section class="rounded-2xl border {{ $preview['valid'] ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50' }} p-5" aria-labelledby="definition-package-preview-title">
            <div class="flex flex-wrap items-start justify-between gap-3"><div><h2 id="definition-package-preview-title" class="font-bold text-gray-900">Kiểm tra trước khi nhập</h2><p class="mt-1 text-sm {{ $preview['valid'] ? 'text-emerald-800' : 'text-red-800' }}">{{ $preview['valid'] ? __('Request::definition_package.valid') : __('Request::definition_package.invalid') }}</p></div>@if($previewChecksum)<div class="max-w-full rounded-lg bg-white/80 px-3 py-2 text-xs text-slate-600"><div class="font-semibold">Checksum gói</div><code class="break-all">{{ $previewChecksum }}</code></div>@endif</div>

            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-white/80 bg-white p-4"><h3 class="text-sm font-bold text-gray-800">Phạm vi thay đổi</h3>@if(($preview['changed_sections'] ?? []) === [])<p class="mt-2 text-sm text-gray-500">{{ __('Request::definition_package.no_changes') }}</p>@else<ul class="mt-2 space-y-1 text-sm text-gray-700">@foreach($preview['changed_sections'] as $section)<li>• {{ __('Request::definition_package.section_labels.'.$section) }}</li>@endforeach</ul>@endif</div>
                <div class="rounded-xl border border-white/80 bg-white p-4"><h3 class="text-sm font-bold text-gray-800">Ánh xạ bắt buộc</h3>@forelse(($preview['required_mappings'] ?? []) as $mapping)<div class="mt-2 font-mono text-xs text-gray-700">{{ $mapping['ref'] }} → ID local</div>@empty<p class="mt-2 text-sm text-gray-500">Không có ánh xạ user/role bắt buộc.</p>@endforelse</div>
            </div>

            @if(($preview['warnings'] ?? []) !== [])<div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4"><h3 class="text-sm font-bold text-amber-900">{{ __('Request::definition_package.warnings') }}</h3><ul class="mt-2 space-y-1 text-sm text-amber-800">@foreach($preview['warnings'] as $warning)<li>• {{ __('Request::definition_package.'.$warning) }}</li>@endforeach</ul></div>@endif
            @if(($preview['errors'] ?? []) !== [])<div class="mt-4 rounded-xl border border-red-200 bg-white p-4"><h3 class="text-sm font-bold text-red-800">{{ __('Request::definition_package.errors') }}</h3><ul class="mt-2 space-y-1 text-sm text-red-700">@foreach($preview['errors'] as $field => $messages)<li>• <span class="font-mono">{{ $field }}</span>: cần kiểm tra lại {{ count((array) $messages) }} điều kiện.</li>@endforeach</ul></div>@endif
        </section>

        @if($preview['valid'] === true && $previewChecksum)
            <section class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5" aria-labelledby="definition-package-confirm-title">
                <h2 id="definition-package-confirm-title" class="font-bold text-indigo-950">Xác nhận tạo bản nháp mới</h2>
                <p class="mt-1 text-sm text-indigo-800">Checksum của tệp import phải khớp đúng checksum vừa preview. Import thành công chỉ tạo draft mới; không publish trực tiếp.</p>
                <form method="POST" action="{{ route('request.admin.types.package.import', $type->public_id) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                    @csrf
                    <input type="hidden" name="preview_checksum" value="{{ $previewChecksum }}"><input type="hidden" name="mappings_json" value="{{ $mappingsJson }}">
                    <div><label for="definition-package-confirm" class="mb-1 block text-sm font-semibold text-indigo-950">Chọn lại đúng tệp đã preview</label><input id="definition-package-confirm" type="file" name="package" accept="application/json,.json" required class="block min-h-11 w-full rounded-xl border border-indigo-300 bg-white px-3 py-2 text-sm"></div>
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-indigo-700 px-4 py-2 text-sm font-semibold text-white">Xác nhận tạo bản nháp mới</button>
                </form>
            </section>
        @endif
    @endif
</div>
@endsection
