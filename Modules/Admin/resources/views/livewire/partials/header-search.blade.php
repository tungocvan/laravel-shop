<div
    x-data="{ focus: false }"
    class="relative w-full max-w-lg"
>
    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
        <svg class="h-4.5 w-4.5 text-slate-400 transition-colors duration-200" :class="focus ? 'text-indigo-500' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
        </svg>
    </div>

    <input
        type="text"
        wire:model.debounce.400ms="query"
        wire:keydown.enter="submit"
        @focus="focus = true"
        @blur="focus = false"
        class="block h-10 w-full rounded-xl border border-slate-200 bg-slate-50/80 py-2 pl-10 pr-20 text-sm text-slate-900 outline-none transition duration-200 placeholder:text-slate-400 hover:border-slate-300 hover:bg-white focus:border-indigo-300 focus:bg-white focus:ring-4 focus:ring-indigo-500/10"
        placeholder="Tìm kiếm nhanh..."
        aria-label="Tìm kiếm nhanh"
    >

    <div
        class="pointer-events-none absolute inset-y-0 right-2 hidden items-center sm:flex"
        x-show="!focus"
        x-transition.opacity.duration.150ms
        aria-hidden="true"
    >
        <kbd class="inline-flex h-6 items-center gap-1 rounded-md border border-slate-200 bg-white px-2 text-[11px] font-medium text-slate-400 shadow-sm">
            <span>Ctrl</span><span>K</span>
        </kbd>
    </div>
</div>
