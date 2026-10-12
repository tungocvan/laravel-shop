@extends('Website::layouts.frontend')
@section('title', $siteName)
@section('content')
    @if(app(\Modules\Website\Services\WebsiteTemplateService::class)->isPharma())
        @include('Website::pages.home.pharma')
    @else
        @livewire('website.home.home-list')
    @endif
@endsection
