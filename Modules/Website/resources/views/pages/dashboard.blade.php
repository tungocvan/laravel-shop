@extends('Admin::layouts.master')
@section('title', 'Dashboard')

@section('content')
    <div class="mb-8">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Tổng quan hệ thống</h1>
        <p class="mt-1 text-sm text-gray-500">Chào mừng trở lại! Dưới đây là tình hình kinh doanh hôm nay.</p>
    </div>

    @livewire('website.dashboard.stats-overview')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2">
            @livewire('website.dashboard.revenue-chart')
        </div>

        <div class="lg:col-span-1">
            @livewire('website.dashboard.recent-orders')
        </div>
    </div>
@endsection
