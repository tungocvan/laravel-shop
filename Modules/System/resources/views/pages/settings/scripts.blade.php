@extends('Admin::layouts.master')
@section('title', 'Thao tác Script')
@section('content')
    <div class="space-y-6">
        @include('System::partials.dashboard-return-link')
        <livewire:system.settings.sh-script />
    </div>
@endsection
