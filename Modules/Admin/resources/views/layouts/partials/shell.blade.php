@php
    $sidebarEnabled = (bool) data_get($adminSidebarConfig, 'enabled', true);
    $adminShellPresentation = app(\Modules\Admin\Services\AdminShellPresentationService::class)->context();
@endphp

<div
    class="flex h-dvh overflow-hidden antialiased"
    style="background-color: var(--admin-surface-base); color: var(--admin-text-primary); font-family: var(--admin-font-family); font-size: var(--admin-font-size-body);"
    data-admin-container="{{ $adminShellPresentation['container'] }}"
    data-admin-density="{{ $adminShellPresentation['density'] }}"
>
    @if ($sidebarEnabled)
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
            :role="isDesktop ? 'complementary' : 'dialog'"
            aria-label="Admin navigation"
            :aria-modal="(!isDesktop && sidebarOpen).toString()"
            @keydown.tab="trapFocus($event, $refs.sidebarPanel)"
            class="fixed inset-y-0 left-0 z-50 border-r shadow-xl shadow-slate-950/5 transition-[transform,width] duration-300 ease-out motion-reduce:transition-none lg:shadow-none"
            style="background-color: var(--admin-surface-raised); border-color: var(--admin-border-subtle);"
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
        :style="{{ $sidebarEnabled ? 'true' : 'false' }}
            ? (sidebarOpen
                ? 'margin-left: {{ $adminShellPresentation['sidebar_expanded_width'] }}'
                : 'margin-left: {{ $adminShellPresentation['sidebar_collapsed_width'] }}')
            : 'margin-left: 0'"
    >
        <livewire:admin.partials.header />

        @include('Admin::layouts.partials.content')
        @include('Admin::layouts.partials.footer')
    </div>
</div>
