@extends('Admin::layouts.master')
@section('title', 'Hồ sơ cá nhân')
@section('content')
    @livewire('admin.profile.user-profile')
    @livewire('admin.profile.user-address')
@endsection
