@extends('Admin::layouts.master')

@section('title', __('Request::definition_package.title'))

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    @include('Request::partials.dashboard-back')

    <header>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Request::definition_package.title') }}</h1>
        <p class="mt-1 text-sm text-gray-600">{{ __('Request::definition_package.description') }}</p>
        <div class="mt-3 flex flex-wrap gap-2 text-xs text-gray-500">
            <span class="rounded-full bg-gray-100 px-3 py-1">{{ $type->code }}</span>
            @if($type->currentPublishedVersion)
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-700">
                    {{ __('Request::definition_package.current_published') }}: v{{ $type->currentPublishedVersion->version_number }}
                </span>
            @endif
        </div>
    </header>

    @if($errors->any())
        <section class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
            <div class="font-semibold">{{ __('Request::definition_package.invalid') }}</div>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="font-bold text-gray-900">{{ __('Request::definition_package.export_title') }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ __('Request::definition_package.export_help') }}</p>
        <div class="mt-4">
            @can('exportDefinition', $type)
                @if($type->currentPublishedVersion)
                    <a href="{{ route('request.admin.types.package.download', $type->public_id) }}" class="inline-flex min-h-11 items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        {{ __('Request::definition_package.download') }}
                    </a>
                @else
                    <p class="text-sm text-amber-700">{{ __('Request::definition_package.published_required') }}</p>
                @endif
            @endcan
        </div>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="font-bold text-gray-900">{{ __('Request::definition_package.import_title') }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ __('Request::definition_package.import_help') }}</p>

        @if($type->active_draft_version_id !== null)
            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                {{ __('Request::definition_package.active_draft_warning') }}
            </div>
        @elseif($type->current_published_version_id === null)
            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                {{ __('Request::definition_package.published_required') }}
            </div>
        @else
            @can('importDefinition', $type)
                <form method="POST" action="{{ route('request.admin.types.package.preview', $type->public_id) }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                    @csrf
                    <div>
                        <label for="definition-package" class="mb-1 block text-sm font-semibold text-gray-800">{{ __('Request::definition_package.package_file') }}</label>
                        <input id="definition-package" type="file" name="package" accept="application/json,.json" required class="block min-h-11 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="definition-mappings" class="mb-1 block text-sm font-semibold text-gray-800">{{ __('Request::definition_package.mappings') }}</label>
                        <textarea id="definition-mappings" name="mappings_json" rows="5" spellcheck="false" class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 font-mono text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('mappings_json', $mappingsJson) }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">{{ __('Request::definition_package.mappings_help') }}</p>
                    </div>
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        {{ __('Request::definition_package.preview') }}
                    </button>
                </form>
            @endcan
        @endif
    </section>

    @if(is_array($preview))
        <section class="rounded-2xl border {{ $preview['valid'] ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50' }} p-5">
            <h2 class="font-bold text-gray-900">{{ __('Request::definition_package.preview_title') }}</h2>
            <p class="mt-1 text-sm {{ $preview['valid'] ? 'text-emerald-800' : 'text-red-800' }}">
                {{ $preview['valid'] ? __('Request::definition_package.valid') : __('Request::definition_package.invalid') }}
            </p>

            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-white/80 bg-white p-4">
                    <h3 class="text-sm font-bold text-gray-800">{{ __('Request::definition_package.changed_sections') }}</h3>
                    @if(($preview['changed_sections'] ?? []) === [])
                        <p class="mt-2 text-sm text-gray-500">{{ __('Request::definition_package.no_changes') }}</p>
                    @else
                        <ul class="mt-2 space-y-1 text-sm text-gray-700">
                            @foreach($preview['changed_sections'] as $section)
                                <li>• {{ __('Request::definition_package.section_labels.'.$section) }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="rounded-xl border border-white/80 bg-white p-4">
                    <h3 class="text-sm font-bold text-gray-800">{{ __('Request::definition_package.required_mappings') }}</h3>
                    @forelse(($preview['required_mappings'] ?? []) as $mapping)
                        <div class="mt-2 font-mono text-xs text-gray-700">{{ $mapping['ref'] }} → ID local</div>
                    @empty
                        <p class="mt-2 text-sm text-gray-500">Không có ánh xạ user/role bắt buộc.</p>
                    @endforelse
                </div>
            </div>

            @if(($preview['warnings'] ?? []) !== [])
                <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <h3 class="text-sm font-bold text-amber-900">{{ __('Request::definition_package.warnings') }}</h3>
                    <ul class="mt-2 space-y-1 text-sm text-amber-800">
                        @foreach($preview['warnings'] as $warning)
                            <li>• {{ __('Request::definition_package.'.$warning) }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(($preview['errors'] ?? []) !== [])
                <div class="mt-4 rounded-xl border border-red-200 bg-white p-4">
                    <h3 class="text-sm font-bold text-red-800">{{ __('Request::definition_package.errors') }}</h3>
                    <ul class="mt-2 space-y-1 text-sm text-red-700">
                        @foreach($preview['errors'] as $field => $messages)
                            <li>• <span class="font-mono">{{ $field }}</span>: cần kiểm tra lại {{ count((array) $messages) }} điều kiện.</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </section>

        @if($preview['valid'] === true && $previewChecksum)
            <section class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5">
                <h2 class="font-bold text-indigo-950">{{ __('Request::definition_package.confirm_import') }}</h2>
                <p class="mt-1 text-sm text-indigo-800">{{ __('Request::definition_package.confirm_help') }}</p>
                <form method="POST" action="{{ route('request.admin.types.package.import', $type->public_id) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                    @csrf
                    <input type="hidden" name="preview_checksum" value="{{ $previewChecksum }}">
                    <input type="hidden" name="mappings_json" value="{{ $mappingsJson }}">
                    <div>
                        <label for="definition-package-confirm" class="mb-1 block text-sm font-semibold text-indigo-950">{{ __('Request::definition_package.package_file') }}</label>
                        <input id="definition-package-confirm" type="file" name="package" accept="application/json,.json" required class="block min-h-11 w-full rounded-xl border border-indigo-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-indigo-700 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        {{ __('Request::definition_package.confirm_import') }}
                    </button>
                </form>
            </section>
        @endif
    @endif
</div>
@endsection
