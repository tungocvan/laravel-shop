@extends('Admin::layouts.master')
@section('title', __('Request::request.groups.title'))
@section('content')
<div class="p-4 sm:p-6">
    <h1 class="text-2xl font-semibold">{{ __('Request::request.groups.title') }}</h1>
    <p class="mt-1 text-sm text-gray-600">{{ __('Request::request.groups.description') }}</p>
    <div class="mt-6 overflow-x-auto rounded-xl border border-gray-200 bg-white">
        <table class="min-w-full text-sm"><thead><tr class="border-b bg-gray-50"><th class="p-3 text-left">{{ __('Request::request.code') }}</th><th class="p-3 text-left">{{ __('Request::request.name') }}</th><th class="p-3 text-left">{{ __('Request::request.types_count') }}</th></tr></thead>
        <tbody>@forelse($groups as $group)<tr class="border-b"><td class="p-3">{{ $group->code }}</td><td class="p-3">{{ $group->name }}</td><td class="p-3">{{ $group->types_count }}</td></tr>@empty<tr><td colspan="3" class="p-6 text-center text-gray-500">{{ __('Request::request.empty') }}</td></tr>@endforelse</tbody></table>
    </div><div class="mt-4">{{ $groups->links() }}</div>
</div>
@endsection
