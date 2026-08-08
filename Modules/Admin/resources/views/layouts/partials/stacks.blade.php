@if (data_get($adminHeaderConfig, 'search', true))
    <div
        x-cloak
        x-show="searchOpen"
        x-transition.opacity
        class="fixed inset-0 z-[70] flex items-start justify-center bg-slate-950/50 px-4 py-20 backdrop-blur-sm sm:hidden"
        role="dialog"
        aria-modal="true"
        aria-labelledby="admin-mobile-search-title"
        @click.self="closeSearch()"
        @keydown.tab="trapFocus($event, $refs.searchDialog)"
    >
        <section
            id="admin-mobile-search"
            x-ref="searchDialog"
            class="w-full max-w-lg rounded-lg border border-slate-200 bg-white p-4 shadow-2xl shadow-slate-950/20"
        >
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 id="admin-mobile-search-title" class="text-sm font-semibold text-slate-900">Tim kiem</h2>
                <button
                    type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    aria-label="Dong tim kiem"
                    @click="closeSearch()"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            @livewire('admin.partials.header-search')
        </section>
    </div>
@endif

<div id="admin-modal-root" class="relative z-[80]" aria-live="polite"></div>
<div id="admin-drawer-root" class="relative z-[75]"></div>

<x-toast />
