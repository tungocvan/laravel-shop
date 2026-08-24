@extends('Admin::layouts.master')
@section('title', __('Request::operations.title'))
@section('content')
<div class="mx-auto w-full max-w-6xl p-3 sm:p-4 lg:p-6">
    @include('Request::partials.dashboard-back')

    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">MR-09 · Operations</p>
        <h1 class="mt-1 text-2xl font-semibold text-slate-900 sm:text-3xl">{{ __('Request::operations.title') }}</h1>
        <p class="mt-2 max-w-3xl text-sm text-slate-600">{{ __('Request::operations.description') }}</p>
        <p class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">{{ __('Request::operations.allowlist_help') }}</p>
    </div>

    @if(session('request_success'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('request_success') }}</div>
    @endif

    @if($failures->isEmpty())
        <div class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-600">{{ __('Request::operations.empty') }}</div>
    @else
        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-4 py-3">{{ __('Request::operations.kind') }}</th>
                        <th class="px-4 py-3">{{ __('Request::operations.target') }}</th>
                        <th class="px-4 py-3">{{ __('Request::operations.error') }}</th>
                        <th class="px-4 py-3">{{ __('Request::operations.attempts') }}</th>
                        <th class="px-4 py-3">{{ __('Request::operations.updated_at') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($failures as $failure)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ __('Request::operations.kinds.'.$failure['kind']) }}</td>
                            <td class="px-4 py-3 text-slate-700"><span class="break-all">{{ $failure['label'] }}</span></td>
                            <td class="px-4 py-3"><code class="rounded bg-slate-100 px-2 py-1 text-xs text-slate-700">{{ $failure['error_code'] ?: '—' }}</code></td>
                            <td class="px-4 py-3 text-slate-700">{{ $failure['attempt_count'] }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $failure['updated_at']?->format('d/m/Y H:i') ?: '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="{{ route('request.admin.operations.retry') }}">
                                    @csrf
                                    <input type="hidden" name="kind" value="{{ $failure['kind'] }}">
                                    <input type="hidden" name="public_id" value="{{ $failure['public_id'] }}">
                                    <button type="submit" class="min-h-11 rounded-lg border border-indigo-300 bg-white px-4 py-2 font-medium text-indigo-700 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ __('Request::operations.retry') }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
