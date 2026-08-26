@extends('ClientPortal::layouts.application')

@section('title', $applicationPresentation['name'] ?? $application['name'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-dashboard-route', route('client.request.dashboard'))
@section('mobile-nav')@include('ClientPortal::applications.request.partials.mobile-nav')@endsection

@section('content')
    @livewire('request.requester.catalog')
@endsection
