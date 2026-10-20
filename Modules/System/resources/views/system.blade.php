@extends('Admin::layouts.master')
@section('title', 'System Operations · System Center')
@section('content')
<div class="container mx-auto px-4 py-6" x-data="{
    activeTab: @js($activeTab),
    tabs: @js($tabs),
    selectTab(id) {
        this.activeTab = id;
        window.history.replaceState({}, '', '{{ route('admin.system.index') }}?tab=' + encodeURIComponent(id));
    }
}">
    <nav class="mb-4 flex text-sm text-slate-500" aria-label="Breadcrumb">
        <span>Dashboard hệ thống</span>
        <span class="mx-2">/</span>
        <span class="font-semibold text-slate-800">System Center</span>
    </nav>

    <header class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:p-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-indigo-600">System Center</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">System Operations · Vận hành & Cấu hình hệ thống</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                    Một workspace cho các tác vụ runtime và cấu hình hạ tầng, với quyền truy cập độc lập theo chức năng.
                </p>
            </div>
            @include('System::partials.dashboard-return-link')
        </div>
        @if(session('success'))
            <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700" role="status">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">{{ session('error') }}</div>
        @endif
    </header>

    <div class="grid gap-5 lg:grid-cols-[220px_minmax(0,1fr)]">
        <nav class="min-w-0 rounded-2xl border border-slate-200 bg-slate-50 p-3 lg:self-start" aria-label="System workspaces">
            @if($canManage)
                <p class="px-3 pb-2 pt-2 text-xs font-bold uppercase tracking-wider text-slate-500">Runtime Operations</p>
                <div class="flex gap-1 overflow-x-auto lg:flex-col lg:overflow-visible">
                    @foreach($tabs->filter(fn ($tab) => ! str_starts_with($tab['id'], 'env-')) as $tab)
                        <button type="button" @click="selectTab(@js($tab['id']))"
                            :aria-current="activeTab === @js($tab['id']) ? 'page' : null"
                            :class="activeTab === @js($tab['id']) ? 'bg-white text-indigo-700 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:bg-white hover:text-slate-950'"
                            class="min-h-11 shrink-0 rounded-xl px-3 py-2 text-left text-sm font-semibold transition lg:w-full">{{ $tab['label'] }}</button>
                    @endforeach
                </div>
            @endif
            @if($canViewEnv)
                <p class="px-3 pb-2 pt-5 text-xs font-bold uppercase tracking-wider text-slate-500">Environment & Integrations</p>
                <div class="flex gap-1 overflow-x-auto lg:flex-col lg:overflow-visible">
                    @foreach($tabs->filter(fn ($tab) => str_starts_with($tab['id'], 'env-')) as $tab)
                        <button type="button" @click="selectTab(@js($tab['id']))"
                            :aria-current="activeTab === @js($tab['id']) ? 'page' : null"
                            :class="activeTab === @js($tab['id']) ? 'bg-white text-indigo-700 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:bg-white hover:text-slate-950'"
                            class="min-h-11 shrink-0 rounded-xl px-3 py-2 text-left text-sm font-semibold transition lg:w-full">{{ $tab['label'] }}</button>
                    @endforeach
                </div>
            @endif
        </nav>

        <main class="min-w-0 space-y-4">
            @if($canViewEnv)
                <section x-show="activeTab.startsWith('env-')" x-cloak class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
                    <h2 class="text-base font-bold text-slate-900">Quản lý môi trường</h2>
                    <p class="mb-4 mt-1 text-sm text-slate-500">Snapshot và đồng bộ mẫu cấu hình được bảo vệ bằng quyền cập nhật môi trường.</p>
                    @livewire('system.settings.env-manager')
                </section>
            @endif

            @forelse($tabs as $tab)
                <section x-show="activeTab === @js($tab['id'])" x-cloak
                    aria-label="{{ $tab['label'] }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    @if($tab['is_ready'])
                        {!! Livewire::mount($tab['component'], ['key' => 'system-center-'.$tab['id']]) !!}
                    @else
                        <div class="p-8 text-center">
                            <h2 class="font-bold text-slate-700">Workspace chưa sẵn sàng</h2>
                            <p class="mt-2 text-sm text-slate-500">Component {{ $tab['component'] }} chưa được khởi tạo.</p>
                        </div>
                    @endif
                </section>
            @empty
                <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500">
                    Không có workspace khả dụng với quyền hiện tại.
                </div>
            @endforelse
        </main>
    </div>
</div>
@endsection
