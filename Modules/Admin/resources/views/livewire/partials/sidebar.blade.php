<aside x-data="{ navQuery: '', normalize(value) { return (value || '').toLowerCase(); }, matches(value) { const q = this.normalize(this.navQuery.trim()); return q === '' || this.normalize(value).includes(q); } }" class="flex h-full w-full flex-col overflow-hidden transition-all duration-300 motion-reduce:transition-none {{ $theme['background'] }} {{ $theme['text'] }}">
    <div class="relative flex min-h-16 shrink-0 items-center border-b px-3 {{ $theme['border'] }}">
        <div class="flex min-w-0 flex-1 items-center gap-3" :class="sidebarOpen ? '' : 'justify-center'">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-xs font-bold shadow-sm {{ $theme['active_bg'] }} {{ $theme['active_text'] }}">{{ $schoolAcronym }}</div>
            <div x-cloak x-show="sidebarOpen" class="min-w-0">
                @if($schoolPrefix)<p class="truncate text-[10px] font-semibold uppercase tracking-wider opacity-60">{{ $schoolPrefix }}</p>@endif
                <p class="truncate text-sm font-semibold">{{ $schoolDisplayName }}</p>
                <p class="mt-0.5 text-[11px] opacity-50">Không gian quản trị</p>
            </div>
        </div>
        <button type="button" @click="toggleSidebar($event.currentTarget)" class="absolute -right-3 top-1/2 z-10 hidden h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:text-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 lg:inline-flex" aria-label="Thu gọn hoặc mở rộng menu" aria-controls="admin-sidebar" :aria-expanded="sidebarOpen.toString()" title="Thu gọn hoặc mở rộng menu">
            <svg :class="sidebarOpen ? 'rotate-180' : ''" class="h-3.5 w-3.5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
        </button>
    </div>

    @if ($showNavigationSearch)
        <div x-cloak x-show="sidebarOpen" class="shrink-0 px-3 pb-2 pt-3">
            <label for="admin-sidebar-search" class="sr-only">Tìm trong menu quản trị</label>
            <input id="admin-sidebar-search" x-model.debounce.120ms="navQuery" type="search" autocomplete="off" placeholder="Tìm chức năng..." class="h-9 w-full rounded-lg border border-current/15 bg-white/80 px-3 text-xs text-slate-700 outline-none transition placeholder:text-slate-400 focus:ring-2 focus:ring-current/20">
        </div>
    @endif

    <nav class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-2 py-3 [scrollbar-gutter:stable]" aria-label="Admin navigation">
        <div x-cloak x-show="sidebarOpen" class="mb-2 flex items-center justify-between px-2"><span class="text-[10px] font-semibold uppercase tracking-wider opacity-45">Điều hướng</span>@if ($destinationCount >= 8)<span class="rounded-md bg-black/5 px-1.5 py-0.5 text-[10px] font-medium opacity-55">{{ $destinationCount }}</span>@endif</div>
        <div class="space-y-1">
            @foreach ($menus as $item)
                <div x-show="matches(@js($item['name'].' '.collect($item['children'] ?? [])->pluck('name')->implode(' '))) || navQuery.trim() === ''">@include('Admin::livewire.partials.sidebar.navigation.' . $item['kind'], ['item' => $item])</div>
            @endforeach
        </div>
        @if ($showNavigationSearch)<p x-cloak x-show="sidebarOpen && navQuery.trim() !== ''" class="mt-3 border-t border-current/10 px-2 pt-3 text-[11px] opacity-50">Đang lọc menu theo “<span x-text="navQuery"></span>”</p>@endif
    </nav>

    @if ($showFooterProfile)
        <div class="shrink-0 border-t border-current/10 p-3">
            <div class="flex min-w-0 items-center gap-3 rounded-lg px-1 py-1" :class="sidebarOpen ? '' : 'justify-center'">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-xs font-bold shadow-sm {{ $theme['active_bg'] }} {{ $theme['active_text'] }}">{{ $profileInitial }}</div>
                <div x-cloak x-show="sidebarOpen" class="min-w-0 flex-1"><p class="truncate text-sm font-semibold">{{ $profileName }}</p><p class="truncate text-[11px] opacity-50">Tài khoản quản trị</p></div>
            </div>
        </div>
    @endif
</aside>