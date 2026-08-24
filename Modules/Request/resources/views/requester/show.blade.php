@extends('Admin::layouts.master')
@section('title', __('Request::request.detail'))
@section('content')
<div class="p-4 sm:p-6">
    @include('Request::partials.offline-runtime')
    @livewire('request.requester.request-detail', ['requestPublicId' => $requestPublicId])
</div>
@endsection
