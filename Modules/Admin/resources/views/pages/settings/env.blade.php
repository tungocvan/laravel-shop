@extends('Admin::layouts.master')
@section('title', 'Cấu hình Hệ thống')
@section('content')
<div class="container mx-auto px-4 py-6" x-data="{ activeTab: 'database', tabs: @js($tabs) }">
    {{-- Breadcrumb --}}
    <nav class="flex mb-4 text-sm text-gray-500">
        <a href="#" class="hover:text-primary transition uppercase tracking-tighter">Dashboard</a>
        <span class="mx-2">/</span>
        <span class="text-gray-800 font-bold uppercase tracking-tighter">Cấu hình hệ thống</span>
    </nav>

    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
        <div>
            <h1 class="text-2xl font-black text-gray-900 uppercase tracking-tight flex items-center gap-2">
                <span class="w-2 h-8 bg-primary rounded-full"></span>
                Quản trị Môi trường (.env)
            </h1>
            <p class="text-gray-500 text-sm mt-1">
                Hệ thống quản lý tham số vận hành chuẩn 
                <span class="px-2 py-0.5 bg-red-50 text-red-600 rounded font-bold border border-red-100">Production</span> 
                & <span class="px-2 py-0.5 bg-blue-50 text-blue-600 rounded font-bold border border-blue-100">Local</span>
            </p>
        </div>
    
        @livewire('admin.settings.env-manager')
       
    </div>

    {{-- Tabs Navigation --}}
    <div class="flex items-center gap-1 border-b border-gray-200 mb-8 bg-gray-50 p-1.5 rounded-t-xl overflow-x-auto no-scrollbar">
        <template x-for="tab in tabs" :key="tab.id">
            <button @click="activeTab = tab.id"
                    :class="activeTab === tab.id ? 'bg-white text-blue-600 shadow-sm border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-800'"
                    class="px-6 py-3 text-[11px] font-black rounded-t-lg transition-all flex items-center gap-2 whitespace-nowrap uppercase tracking-widest">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon"></path></svg>
                <span x-text="tab.label"></span>
            </button>
        </template>
    </div>

    {{-- Content Area --}}
    <div class="relative min-h-[400px]">
        @foreach($tabs as $tab)
            <div x-show="activeTab === '{{ $tab['id'] }}'" x-transition:enter="transition ease-out duration-200" x-cloak>
                @if($tab['is_ready'])
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        {{-- THAY ĐỔI QUAN TRỌNG TẠI ĐÂY: Dùng Livewire::mount thay vì Dynamic Component Tag --}}
                        {!!  Livewire::mount($tab['component'], ['key' => 'comp-'.$tab['id']]) !!}
                    </div>
                @else
                    <div class="p-20 bg-white rounded-xl border-2 border-dashed border-gray-100 flex flex-col items-center justify-center text-center">
                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-6 text-gray-300">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"></path></svg>
                        </div>
                        <h3 class="text-lg font-black text-gray-400 uppercase tracking-widest italic">Tính năng đang xây dựng</h3>
                        <p class="text-gray-400 text-xs mt-2">Class <code class="text-pink-500 font-mono">{{ $tab['component'] }}</code> chưa được khởi tạo.</p>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection
