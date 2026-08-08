@extends('Admin::layouts.master')
@section('title', 'Hồ sơ khách hàng')
@section('content')
    @livewire('website.admin.customers.customer-detail', ['id' => $id])
@endsection
