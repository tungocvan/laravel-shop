<button
    type="button"
    class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 lg:hidden"
    aria-label="Mở tìm kiếm"
    aria-controls="admin-mobile-search"
    :aria-expanded="searchOpen.toString()"
    @click="openSearch($event.currentTarget)"
>
    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" />
    </svg>
</button>

<div class="hidden min-w-0 flex-1 lg:block">
    <div class="w-full max-w-lg">
        @livewire('admin.partials.header-search')
    </div>
</div>
