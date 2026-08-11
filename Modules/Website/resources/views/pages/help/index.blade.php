@extends('Website::layouts.frontend')
@section('title', $siteName)
@section('content')
    @livewire('website.help.help-list')
@endsection
