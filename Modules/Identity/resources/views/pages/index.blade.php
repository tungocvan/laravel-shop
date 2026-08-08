@extends('Admin::layouts.master')

@section('title', 'Quản lý định danh')

@section('content')
    @livewire('identity.identities.index')
@endsection
