@extends('Admin::layouts.master')

@section('title', __('Request::request.reports.title'))

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    @include('Request::partials.workspace-navigation')

    <header class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Request::request.reports.title') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('Request::request.reports.description') }}</p>
        </div>
        <div class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-900">
            {{ __('Request::request.reports.export_limits', ['sync' => number_format($syncRowLimit), 'max' => number_format($maxRows)]) }}
        </div>
    </header>

    @if(session('request_export_message'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">
            {{ session('request_export_message') }}
        </div>
    @endif

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6" aria-label="{{ __('Request::request.reports.status_summary') }}">
        @foreach($statuses as $status)
            <a href="{{ route('request.admin.reports', ['status' => $status->value]) }}" class="rounded-2xl border p-4 shadow-sm transition {{ $selectedStatus === $status->value ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 bg-white hover:border-indigo-300' }}">
                <div class="text-sm font-medium text-gray-600">{{ __('Request::request.statuses.'.$status->value) }}</div>
                <div class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($statusCounts[$status->value] ?? 0) }}</div>
            </a>
        @endforeach
    </section>

    <section class="rounded-2xl border border-indigo-200 bg-indigo-50/50 p-4 shadow-sm sm:p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h2 class="font-bold text-gray-900">{{ __('Request::exports.create_title') }}</h2>
                <p class="mt-1 text-sm text-gray-600">{{ __('Request::exports.create_help') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach(['csv', 'xlsx'] as $format)
                    <form method="POST" action="{{ route('request.admin.reports.exports.store') }}">
                        @csrf
                        <input type="hidden" name="format" value="{{ $format }}">
                        <input type="hidden" name="status" value="{{ $selectedStatus }}">
                        <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                        <button type="submit" class="min-h-11 rounded-lg border border-indigo-600 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            {{ __('Request::exports.'.$format) }}
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-gray-200 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-bold text-gray-900">{{ __('Request::request.reports.register') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Request::request.reports.authorized_scope_help') }}</p>
            </div>
            @if($selectedStatus !== '')
                <a href="{{ route('request.admin.reports') }}" class="text-sm font-semibold text-indigo-700">{{ __('Request::request.reset_filters') }}</a>
            @endif
        </div>

        <div class="divide-y divide-gray-100">
            @forelse($requests as $item)
                <article class="grid gap-2 p-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
                    <div class="min-w-0">
                        <div class="font-mono text-xs text-gray-500">{{ $item->request_number }}</div>
                        <div class="mt-1 truncate font-semibold text-gray-900">{{ $item->title_snapshot }}</div>
                        <div class="mt-1 text-sm text-gray-500">{{ $item->type?->name ?? __('Request::request.reports.unknown_type') }}</div>
                    </div>
                    <div class="flex items-center gap-3 sm:justify-end">
                        <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">{{ __('Request::request.statuses.'.$item->status->value) }}</span>
                        <time class="text-xs text-gray-500" datetime="{{ $item->created_at?->toIso8601String() }}">{{ $item->created_at?->format('d/m/Y H:i') }}</time>
                    </div>
                </article>
            @empty
                <div class="p-8 text-center text-sm text-gray-500">{{ __('Request::request.reports.empty') }}</div>
            @endforelse
        </div>

        @if($requests->hasPages())
            <div class="border-t border-gray-200 p-4">{{ $requests->links() }}</div>
        @endif
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 p-4">
            <h2 class="font-bold text-gray-900">{{ __('Request::exports.recent_title') }}</h2>
            <p class="mt-1 text-sm text-gray-500">{{ __('Request::exports.recent_help') }}</p>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($exports as $export)
                <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="font-mono text-xs text-gray-500">{{ $export->public_id }} · {{ strtoupper($export->format) }}</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900">{{ __('Request::exports.statuses.'.$export->status->value) }}</div>
                        <div class="mt-1 text-xs text-gray-500">
                            {{ __('Request::exports.rows', ['count' => number_format($export->row_count ?? 0)]) }}
                            @if($export->expires_at)
                                · {{ __('Request::exports.expires', ['time' => $export->expires_at->format('d/m/Y H:i')]) }}
                            @endif
                        </div>
                    </div>
                    @if($export->status->value === 'ready' && $export->expires_at?->isFuture())
                        <a href="{{ route('request.exports.download', $export->public_id) }}" class="min-h-11 rounded-lg bg-indigo-600 px-4 py-2 text-center text-sm font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            {{ __('Request::exports.download') }}
                        </a>
                    @endif
                </div>
            @empty
                <div class="p-8 text-center text-sm text-gray-500">{{ __('Request::exports.no_exports') }}</div>
            @endforelse
        </div>
    </section>
</div>
@endsection
