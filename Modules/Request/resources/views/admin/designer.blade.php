@extends('Admin::layouts.master')
@section('title', $type->name)
@section('content')
<div class="p-4 sm:p-6"><h1 class="text-2xl font-semibold">{{ $type->name }}</h1><p class="mt-1 text-sm text-gray-600">{{ __('Request::request.designer_readiness') }}</p><div class="mt-6">@livewire('request.admin.type-designer', ['typePublicId' => $type->public_id])</div></div>
@endsection
