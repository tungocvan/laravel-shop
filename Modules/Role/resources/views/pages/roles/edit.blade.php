@extends('Admin::layouts.master')

@section('title', 'Chỉnh sửa Vai trò')

@section('content')    
     @livewire('role.role-form',["id" => $id])
@endsection