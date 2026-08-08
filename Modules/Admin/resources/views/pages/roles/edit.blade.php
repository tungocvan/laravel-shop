@extends('Admin::layouts.master')

@section('title', 'Chỉnh sửa Vai trò')

@section('content')
    <livewire:admin.system.role-form :id="$id" />
@endsection