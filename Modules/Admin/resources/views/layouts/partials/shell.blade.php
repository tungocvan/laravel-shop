@php
    $sidebarEnabled = (bool) data_get($adminSidebarConfig, 'enabled', true);
    $collapseToggleEnabled = (bool) data_get($adminSidebarConfig, 'desktop_collapsible', true)
        && (bool) data_get($adminSidebarConfig, 'controls.collapse_enabled', true);
    $fullscreenToggleEnabled = (bool) data_get($adminSidebarConfig, 'controls.fullscreen_enabled', true);
    $adminShellPresentation = app(\Modules\Admin\Services\AdminShellPresentationService::class)->context();
@endphp

<style>
    [data-admin-shell] {
        --admin-sidebar-width: 0px;
    }

    [data-admin-shell] [data-admin-shell-workspace] {
        width: 100%;
        min-width: 0;
    }

    @media (min-width: 1024px) {
        [data-admin-shell] [data-admin-shell-sidebar] {
            position: fixed;
            inset-block: 0;
            left: 0;
            height: 100dvh;
            min-height: 0;
        }

        [data-admin-shell] [data-admin-shell-workspace] {
            margin-left: var(--admin-sidebar-width);
            width: calc(100% - var(--admin-sidebar-width));
        }
    }
</style>

<div
    data-admin-shell
    class="relative min-h-0 h-full overflow-hidden antialiased"
    style="height: 100dvh; {{ $adminShellPresentation['shell_style'] }}; background-color: var(--admin-page-background); color: var(--admin-text-primary); font-family: var(--admin-font-family); font-size: var(--admin-font-size-body);"
    data-admin-container="{{ $adminShellPresentation['container'] }}"
    data-admin-density="{{ $adminShellPresentation['density'] }}"
    data-admin-reduced-motion="{{ $adminShellPresentation['reduced_motion'] ? 'true' : 'false' }}"
    x-init="if (!{{ $sidebarEnabled ? 'true' : 'false' }}) { sidebarOpen = false; sidebarFullscreen = false; }"
    x-effect="
        if ({{ $sidebarEnabled ? 'true' : 'false' }} && isDesktop && !{{ $collapseToggleEnabled ? 'true' : 'false' }} && !sidebarOpen) {
            sidebarOpen = true;
            persistSidebarPreference();
        }

        if (!{{ $fullscreenToggleEnabled ? 'true' : 'false' }} && sidebarFullscreen) {
            sidebarFullscreen = false;
            persistSidebarFullscreenPreference();
        }

        const sidebarWidth = isDesktop
            && {{ $sidebarEnabled ? 'true' : 'false' }}
            && (!sidebarFullscreen || !{{ $fullscreenToggleEnabled ? 'true' : 'false' }})
                ? (sidebarOpen ? '{{ $adminShellPresentation['sidebar_expanded_width'] }}' : '{{ $adminShellPresentation['sidebar_collapsed_width'] }}')
                : '0px';

        $el.style.setProperty('--admin-sidebar-width', sidebarWidth);
    "
>
    @if (!$sidebarEnabled)
        <button
            type="button"
            x-cloak
            x-show="isDesktop && (!sidebarOpen || sidebarFullscreen)"
            @click="sidebarFullscreen = false; sidebarOpen = true"
            class="fixed left-3 top-3 z-[70] hidden h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white/95 text-slate-600 shadow-md shadow-slate-950/10 backdrop-blur transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 lg:inline-flex"
            aria-controls="admin-sidebar"
            :aria-expanded="(sidebarOpen && !sidebarFullscreen).toString()"
            aria-label="Mở Sidebar"
            title="Mở Sidebar"
            data-admin-sidebar-disabled-reveal
        >
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5h16v14H4zM9 5v14M11 9l3 3-3 3" />
            </svg>
        </button>
    @endif

    @if ($sidebarEnabled && $fullscreenToggleEnabled)
        <button
            type="button"
            x-cloak
            x-show="isDesktop && sidebarFullscreen"
            @click="toggleSidebarFullscreen($event.currentTarget)"
            class="fixed left-3 top-3 z-[70] hidden h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white/95 text-slate-600 shadow-md shadow-slate-950/10 backdrop-blur transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 lg:inline-flex"
            aria-controls="admin-sidebar"
            aria-expanded="false"
            aria-label="Mở lại Sidebar"
            title="Mở lại Sidebar"
            data-admin-sidebar-fullscreen-toggle
        >
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5h16v14H4zM9 5v14M11 9l3 3-3 3" />
            </svg>
        </button>
    @endif

    @if ($sidebarEnabled)
        <div
            x-cloak
            x-show="sidebarOpen && !isDesktop"
            x-transition.opacity
            class="fixed inset-0 z-40 bg-slate-950/45 backdrop-blur-sm lg:hidden"
            aria-hidden="true"
            @click="closeSidebar()"
        ></div>
    @endif

        <div
            id="admin-sidebar"
            data-admin-shell-sidebar
            x-ref="sidebarPanel"
            x-show="{{ $sidebarEnabled ? 'true' : 'false' }} ? (!isDesktop || !sidebarFullscreen || !{{ $fullscreenToggleEnabled ? 'true' : 'false' }}) : (sidebarOpen && !sidebarFullscreen)"
            x-transition.opacity.duration.150ms
            :role="isDesktop ? 'complementary' : 'dialog'"
            aria-label="Admin navigation"
            :aria-modal="(!isDesktop && sidebarOpen).toString()"
            @keydown.tab="trapFocus($event, $refs.sidebarPanel)"
            class="fixed inset-y-0 left-0 z-50 shadow-xl shadow-slate-950/5 transition-[transform,width,opacity] duration-300 ease-out motion-reduce:transition-none lg:h-full lg:min-h-0 lg:shadow-none"
            style="background-color: var(--admin-surface-raised);"
            :style="sidebarOpen
                ? 'width: {{ $adminShellPresentation['sidebar_expanded_width'] }}'
                : 'width: {{ $adminShellPresentation['sidebar_collapsed_width'] }}'"
            :class="isDesktop
                ? 'translate-x-0'
                : (sidebarOpen ? 'translate-x-0' : '-translate-x-full')"
        >
            <livewire:admin.partials.sidebar />
        </div>

    <div
        data-admin-shell-workspace
        class="grid min-h-0 min-w-0 overflow-hidden transition-[margin-left,width] duration-300 ease-out motion-reduce:transition-none"
        style="grid-template-rows: auto minmax(0, 1fr) auto;"
        :data-admin-sidebar-fullscreen="(isDesktop && sidebarFullscreen && {{ $fullscreenToggleEnabled ? 'true' : 'false' }}) ? 'true' : 'false'"
    >
        <livewire:admin.partials.header />

        @include('Admin::layouts.partials.content')
        @include('Admin::layouts.partials.footer')
    </div>
</div>
