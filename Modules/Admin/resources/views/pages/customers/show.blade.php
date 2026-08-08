@extends('Admin::layouts.master')
@section('title', 'Hồ sơ khách hàng')
@section('content')
    @livewire('admin.customers.customer-detail', ['id' => $id])
@endsection