@extends('Website::layouts.frontend')
@php
        use Modules\Website\Models\Setting;
@endphp
@section('title', Setting::getValue('site_name'))
@section('content')
    @livewire('website.home.home-list')
    {{-- <h2>Website trong thời gian bảo trì...</h2> --}}
@endsection

