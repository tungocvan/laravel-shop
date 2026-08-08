@extends('Admin::layouts.master')

@section('title', 'Quản lý Header & Menu')

@section('content')
    <div class="max-w-7xl mx-auto py-6" x-data="{ activeTab: 'general' }">

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Chọn Themes</h1>
        </div>

        @livewire('admin.theme-switcher')

    </div>
@endsection

