@extends('Admin::layouts.master')
@section('title', 'Quản lý Nhân sự')

@section('content')
    <div class="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        @livewire('user.user-table')
    </div>
@endsection
