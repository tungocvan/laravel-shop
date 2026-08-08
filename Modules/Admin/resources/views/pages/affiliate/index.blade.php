@extends('Admin::layouts.master') {{-- Hoặc layout admin của bạn --}}

@section('title', 'Quản lý Affiliate')

@section('content')
    @livewire('admin.affiliate.commission-list')
@endsection