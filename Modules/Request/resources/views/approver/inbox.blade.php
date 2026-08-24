@extends('Admin::layouts.master')

@section('title', __('Request::request.inbox.title'))

@section('content')
    <livewire:request.approver.inbox />
@endsection
