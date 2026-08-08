@extends('Admin::layouts.master')
@section('title', 'Quản lý Đơn hàng')
@section('content')
    @livewire('order.orders.order-table')
@endsection
