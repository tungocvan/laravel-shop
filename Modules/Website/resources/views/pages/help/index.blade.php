@extends('Website::layouts.frontend')
@php
        use Modules\Website\Models\Setting;
@endphp
@section('title', Setting::getValue('site_name'))
@section('content')
    @livewire('website.help.help-list')
@endsection

