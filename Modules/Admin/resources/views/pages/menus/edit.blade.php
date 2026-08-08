@extends('Admin::layouts.master')
@section('content')
    @livewire('admin.menus.menu-form', ['id' => $id])
@endsection
