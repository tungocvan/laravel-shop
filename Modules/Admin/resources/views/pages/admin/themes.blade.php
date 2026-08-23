@extends('Admin::layouts.master')

@section('title', 'Chọn Themes')

@section('content')
    <x-admin::page-header
        title="Chọn Themes"
        description="Chọn giao diện hiển thị phù hợp cho không gian quản trị."
        eyebrow="Giao diện Admin"
    />

    <x-admin::content-section>
        @livewire('admin.theme-switcher')
    </x-admin::content-section>
@endsection
