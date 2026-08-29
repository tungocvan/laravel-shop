@extends('Admin::layouts.master')
@section('title', 'Cấu hình hệ thống')
@section('content')
    <div class="space-y-6">
        @include('System::partials.dashboard-return-link')
        @livewire('system.settings.setting-form')
    </div>
@endsection
