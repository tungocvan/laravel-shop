@extends('Admin::layouts.master')
@section('title', __('Request::request.catalog.title'))
@section('content')
<div class="p-4 sm:p-6">
    @include('Request::partials.offline-runtime')
    @livewire('request.requester.catalog')
</div>
@endsection
