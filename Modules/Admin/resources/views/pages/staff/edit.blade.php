@extends('Admin::layouts.master')
@section('title', 'Cập nhật nhân viên')
@section('content')
    <livewire:admin.system.staff-form :id="$id" />
@endsection