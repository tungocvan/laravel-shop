@extends('Admin::layouts.master')
@section('title', __('Request::request.create_draft'))
@section('content')
<div class="p-4 sm:p-6">@livewire('request.requester.create-draft', ['typePublicId' => $typePublicId])</div>
@endsection
