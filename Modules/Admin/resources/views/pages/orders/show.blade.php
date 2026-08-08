@extends('Admin::layouts.master')
@section('title', 'Chi tiết đơn hàng')

@section('content')
    @livewire('admin.orders.order-detail', ['id' => $id])
@endsection
