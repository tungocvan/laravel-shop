@extends('Admin::layouts.master')
@section('title', 'System Operations')
@section('content')
<div class="container mx-auto px-4 py-6" x-data="{ activeTab: @js($activeTab), tabs: @js($tabs) }">
    <nav class="mb-4 flex text-sm text-gray-500">
        <a href="{{ route('admin.system.dashboard') }}" class="transition hover:text-indigo-600">Dashboard hệ thống</a>
        <span class="mx-2">/</span>
        <span class="font-semibold text-gray-800">System Operations</span>
    </nav>

    <div class="mb-8 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-indigo-600">Runtime Operations</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">System Operations</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                Quản trị các dịch vụ runtime của hệ thống: Queue, Email và kết nối Database. Giao diện, branding và cấu hình đăng nhập được quản trị tại workspace Giao diện & Đăng nhập.
            </p>
        </div>
        @include('System::partials.dashboard-return-link')
    </div>

    <div class="mb-8 flex items-center gap-1 overflow-x-auto rounded-xl border border-slate-200 bg-slate-50 p-1.5 no-scrollbar">
        <template x-for="tab in tabs" :key="tab.id">
            <button @click="activeTab = tab.id; window.history.replaceState({}, '', '{{ route('admin.system.index') }}?tab=' + tab.id)"
                    :class="activeTab === tab.id ? 'bg-white text-indigo-700 shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:bg-white/70 hover:text-slate-900'"
                    class="flex min-h-11 items-center gap-2 whitespace-nowrap rounded-lg px-5 py-2.5 text-sm font-semibold transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon"></path>
                </svg>
                <span x-text="tab.label"></span>
            </button>
        </template>
    </div>

    <div class="relative min-h-[400px]">
        @forelse($tabs as $tab)
            <div x-show="activeTab === '{{ $tab['id'] }}'" x-transition x-cloak>
                @if($tab['is_ready'])
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        {!! Livewire::mount($tab['component'], ['key' => 'comp-'.$tab['id']]) !!}
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-white p-16 text-center">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"></path></svg>
                        </div>
                        <h3 class="mt-4 text-base font-bold text-slate-700">Workspace chưa sẵn sàng</h3>
                        <p class="mt-2 text-sm text-slate-500">Component <code class="font-mono text-pink-600">{{ $tab['component'] }}</code> chưa được khởi tạo.</p>
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-600">
                Không có runtime workspace khả dụng.
            </div>
        @endforelse
    </div>
</div>
@endsection
