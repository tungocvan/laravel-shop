@extends('Admin::layouts.master')
@section('title', __('Request::request.mine.title'))
@section('content')
<div class="p-4 sm:p-6">@livewire('request.requester.my-requests')</div>
@endsection
