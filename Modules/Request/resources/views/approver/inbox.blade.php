@extends('Admin::layouts.master')

@section('title', __('Request::request.inbox.title'))

@section('content')
    <div class="p-4 sm:p-6">
        @include('Request::partials.offline-runtime')
        @include('Request::partials.dashboard-back')
        <livewire:request.approver.inbox />
    </div>
@endsection
