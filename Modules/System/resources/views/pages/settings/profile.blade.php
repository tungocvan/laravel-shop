@extends('Admin::layouts.master')
@section('title', 'Hồ sơ cá nhân')
@section('content')
    @livewire('website.account.profile.user-profile')
    @livewire('website.account.profile.user-address')
@endsection
