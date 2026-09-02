@extends('Admin::layouts.master')

@section('title', 'Quản lý Phân quyền (Roles)')

@section('content')
    @livewire('role.role-table')
@endsection
