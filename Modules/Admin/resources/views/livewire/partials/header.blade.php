@php
    $adminHeaderConfig = app(\Modules\Admin\Support\AdminLayoutManager::class)->config()['header'] ?? [];
    $stickyHeader = (bool) data_get(app(\Modules\Admin\Support\AdminLayoutManager::class)->config(), 'layout.sticky_header', true);
@endphp

<header
    class="{{ $stickyHeader ? 'sticky top-0' : 'relative' }} z-30 flex h-16 items-center justify-between border-b border-slate-200 bg-white/90 px-4 backdrop-blur-xl transition-all duration-300 sm:px-6 lg:px-8"
>
    <div class="flex min-w-0 flex-1 items-center gap-3">
        <button
            type="button"
            class="inline-flex h-11 w-11 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 lg:hidden"
            aria-label="Mo menu quan tri"
            aria-controls="admin-sidebar"
            :aria-expanded="(!isDesktop && sidebarOpen).toString()"
            @click="openSidebar($event.currentTarget)"
        >
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        @if (data_get($adminHeaderConfig, 'search', true))
            <button
                type="button"
                class="inline-flex h-11 w-11 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:hidden"
                aria-label="Mo tim kiem"
                aria-controls="admin-mobile-search"
                :aria-expanded="searchOpen.toString()"
                @click="openSearch($event.currentTarget)"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" />
                </svg>
            </button>

            <div class="hidden min-w-0 flex-1 sm:block">
                <div class="w-full max-w-md">
                    @livewire('admin.partials.header-search')
                </div>
            </div>
        @endif
    </div>

    <div class="flex shrink-0 items-center gap-3 sm:gap-4">
        @if (data_get($adminHeaderConfig, 'notifications', true))
            @livewire('admin.partials.header-notifications')
        @endif

        @if (data_get($adminHeaderConfig, 'notifications', true) && data_get($adminHeaderConfig, 'user_menu', true))
            <div class="hidden h-6 w-px bg-slate-200 lg:block" aria-hidden="true"></div>
        @endif

        @if (data_get($adminHeaderConfig, 'user_menu', true))
            @livewire('admin.partials.header-user')
        @endif
    </div>
</header>
