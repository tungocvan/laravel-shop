@extends('Admin::layouts.master')
@section('title', 'Thao tác Artisan')
@section('content')
    <div class="space-y-6">
        @include('System::partials.dashboard-return-link')
        @livewire('system.settings.artisan-list')
    </div>
@endsection
