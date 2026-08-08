@extends('Admin::layouts.master')
@section('title', 'Cập nhật nhân viên')
@section('content')
    @livewire('user.user-form',["id" => $id])
@endsection
