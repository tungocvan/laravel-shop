@extends('Admin::layouts.master')
@section('title', 'Chi tiết đơn hàng')

@section('content')
    @livewire('order.orders.order-detail', ['id' => $id])
@endsection
