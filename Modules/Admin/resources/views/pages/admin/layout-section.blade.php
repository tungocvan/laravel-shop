@extends('Admin::layouts.master')

@section('title', $title.' - Giao diện Admin')

@section('content')
    <div class="mb-5">
        <a href="{{ route('admin.layout') }}" class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-indigo-600">
            <span aria-hidden="true">←</span>
            Tổng quan giao diện Admin
        </a>
    </div>

    @livewire('admin.settings.admin-layout-config', ['section' => $section])
@endsection
