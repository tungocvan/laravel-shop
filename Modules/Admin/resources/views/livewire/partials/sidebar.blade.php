<aside x-data="{ navQuery: '', normalize(value) { return (value || '').toLowerCase(); }, matches(value) { const q = this.normalize(this.navQuery.trim()); return q === '' || this.normalize(value).includes(q); } }" class="flex h-full w-full flex-col overflow-hidden transition-all duration-300 motion-reduce:transition-none {{ $sidebarSurfaceClass }} {{ $sidebarTextClass }}">
    @if ($showSidebarHeader)
        <div class="relative flex min-h-16 shrink-0 items-center border-b px-3 {{ $theme['border'] }}">
            <div class="flex min-w-0 flex-1 items-center gap-3" :class="sidebarOpen ? '' : 'justify-center'">
                @if ($showHeaderMark)
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-xs font-bold shadow-sm {{ $theme['active_bg'] }} {{ $theme['active_text'] }}">{{ $schoolAcronym }}</div>
                @endif
                <div x-cloak x-show="sidebarOpen" class="min-w-0 pr-20">
                    @if($showHeaderTitle)
                        @if($headerTitle === '' && $schoolPrefix)<p class="truncate text-[10px] font-semibold uppercase tracking-wider opacity-60">{{ $schoolPrefix }}</p>@endif
                        <p class="truncate text-sm font-semibold">{{ $headerTitle !== '' ? $headerTitle : $schoolDisplayName }}</p>
                    @endif
                    @if($showHeaderSubtitle)<p class="mt-0.5 truncate text-[11px] opacity-50">{{ $headerSubtitle }}</p>@endif
                </div>
            </div>

            @if ($showFullscreenControl || ($desktopCollapsible && $showCollapseControl))
                <div x-cloak x-show="isDesktop && sidebarOpen" class="absolute right-3 top-1/2 hidden -translate-y-1/2 items-center gap-1 lg:flex">
                    @if ($showFullscreenControl)
                        <button type="button" @click="toggleSidebarFullscreen($event.currentTarget)" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-current/10 bg-white/85 text-slate-500 shadow-sm transition hover:bg-indigo-50 hover:text-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500" aria-label="Ẩn Sidebar toàn màn hình" aria-controls="admin-sidebar" title="Ẩn Sidebar toàn màn hình" data-admin-sidebar-fullscreen-enter>
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 4h5M5 4v5M19 4h-5M19 4v5M5 20h5M5 20v-5M19 20h-5M19 20v-5" /></svg>
                        </button>
                    @endif
                    @if ($desktopCollapsible && $showCollapseControl)
                        <button type="button" @click="toggleSidebar($event.currentTarget)" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-current/10 bg-white/85 text-slate-500 shadow-sm transition hover:bg-indigo-50 hover:text-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500" aria-label="Thu gọn Sidebar" aria-controls="admin-sidebar" :aria-expanded="sidebarOpen.toString()" title="Thu gọn Sidebar" data-admin-sidebar-collapse-toggle>
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5l-7 7 7 7" /></svg>
                        </button>
                    @endif
                </div>
            @endif

            @if ($desktopCollapsible && $showCollapseControl)
                <button type="button" x-cloak x-show="isDesktop && !sidebarOpen" @click="toggleSidebar($event.currentTarget)" class="absolute -right-3 top-1/2 z-10 hidden h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:text-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 lg:inline-flex" aria-label="Mở rộng Sidebar" aria-controls="admin-sidebar" :aria-expanded="sidebarOpen.toString()" title="Mở rộng Sidebar" data-admin-sidebar-collapse-toggle>
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </button>
            @endif
        </div>
    @endif

    @if ($showNavigationSearch)
        <div x-cloak x-show="sidebarOpen" class="shrink-0 px-3 pb-2 pt-3">
            <label for="admin-sidebar-search" class="sr-only">Tìm trong menu quản trị</label>
            <input id="admin-sidebar-search" x-model.debounce.120ms="navQuery" type="search" autocomplete="off" placeholder="Tìm chức năng..." class="h-9 w-full rounded-lg border border-current/15 bg-white/90 px-3 text-xs text-slate-700 outline-none shadow-sm transition placeholder:text-slate-400 focus:ring-2 focus:ring-indigo-500/30">
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

    @if ($showSidebarFooter)
        <div class="shrink-0 border-t border-current/10 p-3">
            <div class="flex min-w-0 items-center gap-3 rounded-lg px-1 py-1" :class="sidebarOpen ? '' : 'justify-center'">
                @if ($showFooterAvatar)
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-xs font-bold shadow-sm {{ $theme['active_bg'] }} {{ $theme['active_text'] }}">{{ $profileInitial }}</div>
                @endif
                <div x-cloak x-show="sidebarOpen" class="min-w-0 flex-1">
                    @if($showFooterName)<p class="truncate text-sm font-semibold">{{ $profileName }}</p>@endif
                    @if($showFooterSubtitle)<p class="truncate text-[11px] opacity-50">{{ $footerSubtitle }}</p>@endif
                </div>
            </div>
        </div>
    @endif
</aside>
