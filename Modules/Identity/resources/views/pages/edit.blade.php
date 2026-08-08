@extends('Admin::layouts.master')

@section('title', 'Cập nhật định danh')

@section('content')
    @livewire('identity.identities.form', ['identity' => $identity])
@endsection
