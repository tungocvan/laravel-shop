@extends('Admin::layouts.master')
@section('title', 'Quản lý Đơn hàng')
@section('content')
    @livewire('admin.orders.order-table')
@endsection
