@php
    $sidebarEnabled = (bool) data_get($adminSidebarConfig, 'enabled', true);
    $adminShellPresentation = app(\Modules\Admin\Services\AdminShellPresentationService::class)->context();
@endphp

<div
    class="flex h-dvh overflow-hidden antialiased"
    style="{{ $adminShellPresentation['shell_style'] }}; background-color: var(--admin-page-background); color: var(--admin-text-primary); font-family: var(--admin-font-family); font-size: var(--admin-font-size-body);"
    data-admin-container="{{ $adminShellPresentation['container'] }}"
    data-admin-density="{{ $adminShellPresentation['density'] }}"
    data-admin-reduced-motion="{{ $adminShellPresentation['reduced_motion'] ? 'true' : 'false' }}"
>
    @if ($sidebarEnabled)
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

        <div
            x-cloak
            x-show="sidebarOpen && !isDesktop"
            x-transition.opacity
            class="fixed inset-0 z-40 bg-slate-950/45 backdrop-blur-sm lg:hidden"
            aria-hidden="true"
            @click="closeSidebar()"
        ></div>

        <div
            id="admin-sidebar"
            x-ref="sidebarPanel"
            x-show="!isDesktop || !sidebarFullscreen"
            x-transition.opacity.duration.150ms
            :role="isDesktop ? 'complementary' : 'dialog'"
            aria-label="Admin navigation"
            :aria-modal="(!isDesktop && sidebarOpen).toString()"
            @keydown.tab="trapFocus($event, $refs.sidebarPanel)"
            class="fixed inset-y-0 left-0 z-50 shadow-xl shadow-slate-950/5 transition-[transform,width,opacity] duration-300 ease-out motion-reduce:transition-none lg:shadow-none"
            style="background-color: var(--admin-surface-raised);"
            :style="sidebarOpen
                ? 'width: {{ $adminShellPresentation['sidebar_expanded_width'] }}'
                : 'width: {{ $adminShellPresentation['sidebar_collapsed_width'] }}'"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        >
            <livewire:admin.partials.sidebar />
        </div>
    @endif

    <div
        class="flex min-w-0 flex-1 flex-col transition-[margin] duration-300 ease-out motion-reduce:transition-none"
        :style="({{ $sidebarEnabled ? 'true' : 'false' }} && isDesktop && !sidebarFullscreen)
            ? (sidebarOpen
                ? 'margin-left: {{ $adminShellPresentation['sidebar_expanded_width'] }}'
                : 'margin-left: {{ $adminShellPresentation['sidebar_collapsed_width'] }}')
            : 'margin-left: 0'"
        :data-admin-sidebar-fullscreen="(isDesktop && sidebarFullscreen) ? 'true' : 'false'"
    >
        <livewire:admin.partials.header />

        @include('Admin::layouts.partials.content')
        @include('Admin::layouts.partials.footer')
    </div>
</div>
