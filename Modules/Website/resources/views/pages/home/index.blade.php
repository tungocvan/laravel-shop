@extends('Website::layouts.frontend')
@section('title', $siteName)
@section('content')
    @livewire('website.home.home-list')
    {{-- <h2>Website trong thời gian bảo trì...</h2> --}}
@endsection
