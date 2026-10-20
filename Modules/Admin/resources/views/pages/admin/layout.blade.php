@extends('Admin::layouts.master')

@section('title', 'Tổng quan giao diện Admin')

@section('content')
    <div class="mb-4">
        @include('System::partials.dashboard-return-link')
    </div>
    <x-admin::page-header
        title="Tổng quan giao diện Admin"
        description="Theo dõi trạng thái và truy cập nhanh từng khu vực cấu hình UI Admin."
        eyebrow="Không gian giao diện"
    />

    <x-admin::content-section>
        @livewire('admin.settings.admin-layout-dashboard')
    </x-admin::content-section>
@endsection
