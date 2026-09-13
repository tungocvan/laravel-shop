@extends('Admin::layouts.master')
@section('title', 'Môi trường & Tích hợp')
@section('content')
@php
    $requestedTab = request()->query('tab', 'database');
    $activeTab = $tabs->contains(fn ($tab) => $tab['id'] === $requestedTab) ? $requestedTab : 'database';
@endphp
<div class="container mx-auto px-4 py-6" x-data="{ activeTab: @js($activeTab), tabs: @js($tabs) }">
    <nav class="mb-4 flex text-sm text-slate-500">
        <a href="{{ route('admin.system.dashboard') }}" class="transition hover:text-indigo-600">Dashboard hệ thống</a>
        <span class="mx-2">/</span>
        <span class="font-semibold text-slate-800">Môi trường & Tích hợp</span>
    </nav>

    <div class="mb-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-indigo-600">Infrastructure Configuration</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Môi trường & Tích hợp</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                    Quản lý cấu hình hạ tầng và tích hợp: Database, Email, Runtime & Bridge, Storage / Cloud, Web & Analytics và thanh toán.
                </p>
            </div>
            @include('System::partials.dashboard-return-link')
        </div>

        @if(session('success'))
            <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                {{ session('error') }}
            </div>
        @endif

        <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
            @livewire('system.settings.env-manager')
        </div>
    </div>

    <div class="mb-8 flex items-center gap-1 overflow-x-auto rounded-xl border border-slate-200 bg-slate-50 p-1.5 no-scrollbar">
        <template x-for="tab in tabs" :key="tab.id">
            <button @click="activeTab = tab.id; history.replaceState(null, '', '?tab=' + tab.id)"
                    :class="activeTab === tab.id ? 'bg-white text-indigo-700 shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:bg-white/70 hover:text-slate-900'"
                    class="flex min-h-11 items-center gap-2 whitespace-nowrap rounded-lg px-5 py-2.5 text-sm font-semibold transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon"></path></svg>
                <span x-text="tab.label"></span>
            </button>
        </template>
    </div>

    <div class="relative min-h-[400px]">
        @foreach($tabs as $tab)
            <div x-show="activeTab === '{{ $tab['id'] }}'" x-transition x-cloak>
                @if($tab['is_ready'])
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        {!! Livewire::mount($tab['component'], ['key' => 'comp-'.$tab['id']]) !!}
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-white p-16 text-center">
                        <h3 class="text-base font-bold text-slate-600">Workspace chưa sẵn sàng</h3>
                        <p class="mt-2 text-sm text-slate-500">Component <code class="font-mono text-pink-600">{{ $tab['component'] }}</code> chưa được khởi tạo.</p>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection
