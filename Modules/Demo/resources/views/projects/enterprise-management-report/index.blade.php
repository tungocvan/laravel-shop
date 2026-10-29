@extends('demo::projects.enterprise-management-report.layouts.app')

@section('content')
    @include('demo::projects.enterprise-management-report.pages.dashboard')
    @include('demo::projects.enterprise-management-report.partials.modal')
    @include('demo::projects.enterprise-management-report.partials.data')
@endsection
