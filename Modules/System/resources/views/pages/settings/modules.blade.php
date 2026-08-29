@extends('Admin::layouts.master')
@section('title', 'Quản lý Module')
@section('content')
    <div class="space-y-6">
        @include('System::partials.dashboard-return-link')
        @livewire('system.settings.modules-form')
    </div>
@endsection
