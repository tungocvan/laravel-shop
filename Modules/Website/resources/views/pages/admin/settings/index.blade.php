@extends('Admin::layouts.master')
@section('title', 'SEO, Giao diện & Cài đặt Website')
@section('content')
    <div class="mx-auto max-w-6xl px-4 pt-5">
        @livewire('website.admin.settings.website-template-selector')
    </div>
    @livewire('website.admin.settings.website-settings')
@endsection
