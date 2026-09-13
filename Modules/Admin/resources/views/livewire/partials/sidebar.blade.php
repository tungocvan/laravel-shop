<aside x-data="{ navQuery: '', normalize(value) { return (value || '').toLowerCase(); }, matches(value) { const q = this.normalize(this.navQuery.trim()); return q === '' || this.normalize(value).includes(q); } }" class="flex h-full w-full flex-col overflow-hidden transition-all duration-300 motion-reduce:transition-none {{ $sidebarSurfaceClass }} {{ $sidebarTextClass }}" style="{{ $sidebarStyle }}">
    @if ($showSidebarHeader)
        <div class="relative flex min-h-16 shrink-0 items-center border-b px-3" style="border-color:var(--admin-border-subtle, currentColor); {{ $sidebarHeaderStyle }}">
            <div class="flex min-w-0 flex-1 items-center gap-3" :class="sidebarOpen ? '' : 'justify-center'">
                @if ($showHeaderMark)
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-xs font-bold shadow-sm {{ $sidebarMode === 'theme' ? $theme['active_bg'].' '.$theme['active_text'] : '' }}" @if($sidebarMode !== 'theme') style="background:var(--admin-sidebar-accent);color:var(--admin-sidebar-active-title-color)" @endif>{{ $schoolAcronym }}</div>
                @endif
                <div x-cloak x-show="sidebarOpen" class="min-w-0 pr-20">
                    @if($showHeaderTitle)
                        @if($headerTitle === '' && $schoolPrefix)<p class="truncate text-[10px] font-semibold uppercase tracking-[.14em] opacity-55">{{ $schoolPrefix }}</p>@endif
                        <p class="truncate text-sm font-semibold tracking-tight">{{ $headerTitle !== '' ? $headerTitle : $schoolDisplayName }}</p>
                    @endif
                    @if($showHeaderSubtitle)<p class="mt-0.5 truncate text-[11px]" style="color:var(--admin-text-muted, currentColor)">{{ $headerSubtitle }}</p>@endif
                </div>
            </div>

            @if ($showFullscreenControl || ($desktopCollapsible && $showCollapseControl))
                <div x-cloak x-show="isDesktop && sidebarOpen" class="absolute right-3 top-1/2 hidden -translate-y-1/2 items-center gap-1 lg:flex">
                    @if ($showFullscreenControl)
                        <button type="button" @click="toggleSidebarFullscreen($event.currentTarget)" class="inline-flex h-8 w-8 items-center justify-center rounded-xl border shadow-sm transition hover:scale-[1.03] focus:outline-none focus:ring-2 focus:ring-indigo-500" style="background:var(--admin-sidebar-control-surface,rgba(255,255,255,.85));border-color:var(--admin-sidebar-control-border,currentColor);color:var(--admin-text-muted,currentColor)" aria-label="Ẩn Sidebar toàn màn hình" aria-controls="admin-sidebar" title="Ẩn Sidebar toàn màn hình" data-admin-sidebar-fullscreen-enter><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 4h5M5 4v5M19 4h-5M19 4v5M5 20h5M5 20v-5M19 20h-5M19 20v-5" /></svg></button>
                    @endif
                    @if ($desktopCollapsible && $showCollapseControl)
                        <button type="button" @click="toggleSidebar($event.currentTarget)" class="inline-flex h-8 w-8 items-center justify-center rounded-xl border shadow-sm transition hover:scale-[1.03] focus:outline-none focus:ring-2 focus:ring-indigo-500" style="background:var(--admin-sidebar-control-surface,rgba(255,255,255,.85));border-color:var(--admin-sidebar-control-border,currentColor);color:var(--admin-text-muted,currentColor)" aria-label="Thu gọn Sidebar" aria-controls="admin-sidebar" :aria-expanded="sidebarOpen.toString()" title="Thu gọn Sidebar" data-admin-sidebar-collapse-toggle><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5l-7 7 7 7" /></svg></button>
                    @endif
                </div>
            @endif

            @if ($desktopCollapsible && $showCollapseControl)
                <button type="button" x-cloak x-show="isDesktop && !sidebarOpen" @click="toggleSidebar($event.currentTarget)" class="absolute -right-3 top-1/2 z-10 hidden h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full border bg-white text-slate-500 shadow-sm transition hover:text-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 lg:inline-flex" aria-label="Mở rộng Sidebar" aria-controls="admin-sidebar" :aria-expanded="sidebarOpen.toString()" title="Mở rộng Sidebar" data-admin-sidebar-collapse-toggle><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg></button>
            @endif
        </div>
    @endif

    @if ($showNavigationSearch)
        <div x-cloak x-show="sidebarOpen" class="shrink-0 px-3 pb-2 pt-3" style="{{ $sidebarNavigationStyle }}">
            <label for="admin-sidebar-search" class="sr-only">Tìm trong menu quản trị</label>
            <div class="relative"><svg class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2" style="color:var(--admin-text-muted,currentColor)" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="1.8" d="m21 21-4.35-4.35M19 11a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" /></svg><input id="admin-sidebar-search" x-model.debounce.120ms="navQuery" type="search" autocomplete="off" placeholder="Tìm chức năng..." class="h-9 w-full rounded-xl border bg-transparent pl-9 pr-3 text-xs outline-none transition placeholder:opacity-60 focus:ring-2 focus:ring-indigo-500/30" style="background:var(--admin-sidebar-control-surface,rgba(255,255,255,.72));border-color:var(--admin-sidebar-control-border,currentColor);color:var(--admin-text-primary,currentColor)"></div>
        </div>
    @endif

    <nav class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-2 py-3 [scrollbar-gutter:stable]" style="{{ $sidebarNavigationStyle }}" aria-label="Admin navigation">
        <div x-cloak x-show="sidebarOpen" class="mb-2 flex items-center justify-between px-2"><span class="text-[10px] font-semibold uppercase tracking-[.16em]" style="color:var(--admin-text-muted,currentColor)">Điều hướng</span>@if ($destinationCount >= 8)<span class="rounded-md px-1.5 py-0.5 text-[10px] font-medium" style="background:var(--admin-sidebar-control-surface,rgba(0,0,0,.05));color:var(--admin-text-muted,currentColor)">{{ $destinationCount }}</span>@endif</div>
        <div class="flex flex-col" style="gap:var(--admin-sidebar-menu-item-gap)">@foreach ($menus as $item)<div x-show="matches(@js($item['name'].' '.collect($item['children'] ?? [])->pluck('name')->implode(' '))) || navQuery.trim() === ''">@include('Admin::livewire.partials.sidebar.navigation.' . $item['kind'], ['item' => $item])</div>@endforeach</div>
        @if ($showNavigationSearch)<p x-cloak x-show="sidebarOpen && navQuery.trim() !== ''" class="mt-3 border-t px-2 pt-3 text-[11px]" style="border-color:var(--admin-border-subtle,currentColor);color:var(--admin-text-muted,currentColor)">Đang lọc menu theo “<span x-text="navQuery"></span>”</p>@endif
    </nav>

    @if ($showSidebarFooter)
        <div class="shrink-0 border-t p-3" style="border-color:var(--admin-border-subtle,currentColor); {{ $sidebarFooterStyle }}"><div class="flex min-w-0 items-center gap-3 rounded-xl px-2 py-2" style="background:var(--admin-sidebar-control-surface,transparent)" :class="sidebarOpen ? '' : 'justify-center'">@if ($showFooterAvatar)<div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-xs font-bold shadow-sm {{ $sidebarMode === 'theme' ? $theme['active_bg'].' '.$theme['active_text'] : '' }}" @if($sidebarMode !== 'theme') style="background:var(--admin-sidebar-accent);color:var(--admin-sidebar-active-title-color)" @endif>{{ $profileInitial }}</div>@endif<div x-cloak x-show="sidebarOpen" class="min-w-0 flex-1">@if($showFooterName)<p class="truncate text-sm font-semibold">{{ $profileName }}</p>@endif @if($showFooterSubtitle)<p class="truncate text-[11px]" style="color:var(--admin-text-muted,currentColor)">{{ $footerSubtitle }}</p>@endif</div></div></div>
    @endif
</aside>
