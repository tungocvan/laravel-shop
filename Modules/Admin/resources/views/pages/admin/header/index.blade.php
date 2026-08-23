@extends('Admin::layouts.master')

@section('title', 'Quản lý Header & Menu')

@section('content')
    <div x-data="{ activeTab: 'general' }">
        <x-admin::page-header
            title="Quản lý Header & Menu"
            description="Cấu hình thông tin Header, menu điều hướng và giao diện liên quan trong cùng một workspace."
            eyebrow="Website presentation"
        >
            <x-slot:toolbar>
                <div class="flex min-w-0 flex-1 overflow-x-auto" role="tablist" aria-label="Khu vực quản lý Header">
                    <button
                        type="button"
                        @click="activeTab = 'general'"
                        :aria-selected="(activeTab === 'general').toString()"
                        :class="activeTab === 'general' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900'"
                        class="min-h-10 shrink-0 rounded-lg px-3.5 text-sm font-semibold transition focus:outline-none focus:ring-4 focus:ring-indigo-500/10"
                        role="tab"
                    >
                        Cấu hình chung
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'menu'"
                        :aria-selected="(activeTab === 'menu').toString()"
                        :class="activeTab === 'menu' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900'"
                        class="min-h-10 shrink-0 rounded-lg px-3.5 text-sm font-semibold transition focus:outline-none focus:ring-4 focus:ring-indigo-500/10"
                        role="tab"
                    >
                        Quản lý Menu
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'themes'"
                        :aria-selected="(activeTab === 'themes').toString()"
                        :class="activeTab === 'themes' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900'"
                        class="min-h-10 shrink-0 rounded-lg px-3.5 text-sm font-semibold transition focus:outline-none focus:ring-4 focus:ring-indigo-500/10"
                        role="tab"
                    >
                        Quản lý Themes
                    </button>
                </div>
            </x-slot:toolbar>
        </x-admin::page-header>

        <x-admin::content-section>
            <div x-cloak x-show="activeTab === 'general'" role="tabpanel">
                @livewire('admin.header.general-settings')
            </div>

            <div x-cloak x-show="activeTab === 'menu'" role="tabpanel">
                @livewire('admin.header.menu-manager')
            </div>

            <div x-cloak x-show="activeTab === 'themes'" role="tabpanel">
                @livewire('admin.theme-switcher')
            </div>
        </x-admin::content-section>
    </div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('show-toast', (event) => {
            const data = event[0];

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: data.type || 'success',
                title: data.message,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        });
    });
</script>
@endsection
