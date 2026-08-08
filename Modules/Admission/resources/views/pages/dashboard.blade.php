@extends('Admin::layouts.master')

@section('title', 'Dashboard')

@section('content')
    <div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6">

        {{-- HEADER --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">
                    Tổng quan hệ thống
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    Theo dõi nhanh tình hình hồ sơ tuyển sinh
                </p>
            </div>
        </div>

        {{-- STATS OVERVIEW --}}
        @livewire('admission.admin.dashboard.stats-overview')

    </div>
@endsection