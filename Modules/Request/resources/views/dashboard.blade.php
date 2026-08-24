@extends('Admin::layouts.master')
@section('title', 'Request UI Test Dashboard')
@section('content')
<div class="p-4 sm:p-6 space-y-6">
    @include('Request::partials.offline-runtime')

    <div>
        <h1 class="text-2xl font-semibold">Request UI Test Dashboard</h1>
        <p class="mt-1 text-sm text-gray-600">Shortcut hub for MR-08 UI-01..UI-07 acceptance testing.</p>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
        @foreach ($counts as $table => $count)
            <div class="rounded-lg border bg-white p-4">
                <div class="text-xs uppercase tracking-wide text-gray-500">{{ str_replace('request_', '', $table) }}</div>
                <div class="mt-1 text-2xl font-semibold">{{ $count }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <a class="min-h-11 rounded-lg border bg-white p-4 hover:bg-gray-50 focus:outline-none focus:ring-2" href="{{ route('request.catalog') }}">
            <div class="font-semibold">UI-01 · Catalog / Create</div>
            <div class="mt-1 text-sm text-gray-600">Mobile catalog and create flow.</div>
        </a>
        <a class="min-h-11 rounded-lg border bg-white p-4 hover:bg-gray-50 focus:outline-none focus:ring-2" href="{{ route('request.mine') }}">
            <div class="font-semibold">UI-01 / UI-05 · My Requests</div>
            <div class="mt-1 text-sm text-gray-600">Resume drafts and local draft restore.</div>
        </a>
        <a class="min-h-11 rounded-lg border bg-white p-4 hover:bg-gray-50 focus:outline-none focus:ring-2" href="{{ route('request.inbox') }}">
            <div class="font-semibold">UI-02 · Inbox / Decision</div>
            <div class="mt-1 text-sm text-gray-600">Keyboard, focus and decision workflow.</div>
        </a>
        <a class="min-h-11 rounded-lg border bg-white p-4 hover:bg-gray-50 focus:outline-none focus:ring-2" href="{{ route('request.admin.types') }}">
            <div class="font-semibold">UI-03 · Type Manager</div>
            <div class="mt-1 text-sm text-gray-600">Open designer and version review.</div>
        </a>
        @if ($demoType)
            <a class="min-h-11 rounded-lg border bg-white p-4 hover:bg-gray-50 focus:outline-none focus:ring-2" href="{{ route('request.admin.types.designer', $demoType->public_id) }}">
                <div class="font-semibold">UI-03 · DEMO Designer</div>
                <div class="mt-1 text-sm text-gray-600">Direct link to seeded designer.</div>
            </a>
            <a class="min-h-11 rounded-lg border bg-white p-4 hover:bg-gray-50 focus:outline-none focus:ring-2" href="{{ route('request.admin.types.versions', $demoType->public_id) }}">
                <div class="font-semibold">UI-03 · DEMO Versions</div>
                <div class="mt-1 text-sm text-gray-600">Direct link to version diff.</div>
            </a>
        @endif
        @if ($draftRequest)
            <a class="min-h-11 rounded-lg border bg-white p-4 hover:bg-gray-50 focus:outline-none focus:ring-2" href="{{ route('request.show', $draftRequest->public_id) }}">
                <div class="font-semibold">UI-04..06 · DEMO Draft</div>
                <div class="mt-1 text-sm text-gray-600">Offline/local draft/confidential storage checks.</div>
            </a>
        @endif
        @if ($pendingRequest)
            <a class="min-h-11 rounded-lg border bg-white p-4 hover:bg-gray-50 focus:outline-none focus:ring-2" href="{{ route('request.show', $pendingRequest->public_id) }}">
                <div class="font-semibold">UI-02 / UI-04 · DEMO Pending</div>
                <div class="mt-1 text-sm text-gray-600">Detail, decision and offline mutation lock.</div>
            </a>
        @endif
    </div>

    <div class="rounded-lg border bg-amber-50 p-4 text-sm">
        <strong>UI-07:</strong> create local Request data, then logout and verify <code>Clear-Site-Data</code> plus IndexedDB cleanup.
    </div>
</div>
@endsection
