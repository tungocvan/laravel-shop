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
