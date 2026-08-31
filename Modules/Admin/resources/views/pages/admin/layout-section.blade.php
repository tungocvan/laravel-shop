@extends('Admin::layouts.master')

@section('title', $title.' - Giao diện Admin')

@section('content')
    <x-admin::page-header
        :title="$title"
        eyebrow="Thiết lập giao diện"
    >
        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-2">
                @if ($section === 'sidebar')
                    <a
                        href="{{ route('admin.menus.index') }}"
                        class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-3.5 text-sm font-semibold text-indigo-700 transition hover:border-indigo-300 hover:bg-indigo-100 focus:outline-none focus:ring-4 focus:ring-indigo-500/10"
                    >
                        Quản lý menu Sidebar
                        <span aria-hidden="true">→</span>
                    </a>
                @endif

                <a
                    href="{{ route('admin.layout') }}"
                    class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900 focus:outline-none focus:ring-4 focus:ring-indigo-500/10"
                >
                    <span aria-hidden="true">←</span>
                    Tổng quan
                </a>
            </div>
        </x-slot:actions>
    </x-admin::page-header>

    <x-admin::content-section>
        @if ($section === 'design')
            @livewire('admin.settings.admin-theme-editor')
        @else
            @livewire('admin.settings.admin-layout-config', ['section' => $section])
        @endif
    </x-admin::content-section>
@endsection
