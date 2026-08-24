@extends('Admin::layouts.master')
@section('title', __('Request::request.types.title'))
@section('content')
<div class="p-4 sm:p-6"><h1 class="text-2xl font-semibold">{{ __('Request::request.types.title') }}</h1><p class="mt-1 text-sm text-gray-600">{{ __('Request::request.types.description') }}</p><div class="mt-6">@livewire('request.admin.definition-index')</div></div>
@endsection
