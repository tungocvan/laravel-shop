@extends('Admin::layouts.master') {{-- Hoặc layout admin của bạn --}}

@section('title', 'Quản lý Affiliate')

@section('content')
    @livewire('website.admin.affiliate.commission-list')
@endsection
