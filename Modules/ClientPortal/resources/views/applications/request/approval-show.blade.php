@extends('ClientPortal::layouts.application')

@section('title', $applicationPresentation['name'] ?? $application['name'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-dashboard-route', route('client.request.dashboard'))
@section('mobile-nav')@include('ClientPortal::applications.request.partials.mobile-nav')@endsection

@section('content')
    @livewireStyles
    @livewire('request.approver.request-detail', ['requestPublicId' => $requestPublicId])
    @livewireScripts
@endsection
