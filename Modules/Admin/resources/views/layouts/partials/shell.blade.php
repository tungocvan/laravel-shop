@php
    $sidebarEnabled = (bool) data_get($adminSidebarConfig, 'enabled', true);
@endphp

<div class="flex h-dvh overflow-hidden bg-slate-50 text-slate-900 antialiased">
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
            class="fixed inset-y-0 left-0 z-50 border-r border-slate-200 bg-white shadow-xl shadow-slate-950/5 transition-[transform,width] duration-300 ease-out motion-reduce:transition-none lg:shadow-none"
            :class="sidebarOpen ? 'w-64 translate-x-0' : '-translate-x-full lg:w-20 lg:translate-x-0'"
        >
            <livewire:admin.partials.sidebar />
        </div>
    @endif

    <div
        class="flex min-w-0 flex-1 flex-col transition-[margin] duration-300 ease-out motion-reduce:transition-none"
        :class="{
            'lg:ml-64': {{ $sidebarEnabled ? 'true' : 'false' }} && sidebarOpen,
            'lg:ml-20': {{ $sidebarEnabled ? 'true' : 'false' }} && !sidebarOpen
        }"
    >
        <livewire:admin.partials.header />

        @include('Admin::layouts.partials.content')

        @if (data_get($adminLayoutConfig, 'show_footer', false))
            @include('Admin::layouts.partials.footer')
        @endif
    </div>
</div>
