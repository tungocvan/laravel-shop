<a
    href="{{ $item['href'] }}"
    class="group relative flex items-center rounded-xl transition duration-150 focus:outline-none focus:ring-2 focus:ring-current focus:ring-offset-1 motion-reduce:transition-none {{ $sidebarMode === 'theme' ? ($item['active'] ? $theme['active_bg'] : $theme['hover']) : '' }}"
    style="min-height:var(--admin-sidebar-menu-item-height);padding:var(--admin-sidebar-menu-padding-y) var(--admin-sidebar-menu-padding-x);gap:var(--admin-sidebar-menu-content-gap);font-family:var(--admin-sidebar-menu-font-family);font-size:var(--admin-sidebar-menu-font-size);font-weight:{{ $item['active'] ? 'var(--admin-sidebar-active-font-weight)' : 'var(--admin-sidebar-menu-font-weight)' }};color:{{ $item['active'] ? 'var(--admin-sidebar-active-title-color)' : 'var(--admin-sidebar-menu-title-color)' }};{{ $sidebarMode !== 'theme' ? ($item['active'] ? 'background:var(--admin-sidebar-active-surface);' : '') : '' }}{{ $item['active'] ? 'border-width:var(--admin-sidebar-menu-active-border-width);border-style:var(--admin-sidebar-menu-active-border-style);border-color:var(--admin-sidebar-menu-active-border-color);' : '' }}"
    @if($sidebarMode !== 'theme' && !$item['active']) onmouseenter="this.style.background='var(--admin-sidebar-hover-surface)'" onmouseleave="this.style.background='transparent'" @endif
    :class="sidebarOpen ? '' : 'justify-center'"
    @if ($item['active']) aria-current="page" @endif
    aria-label="{{ $item['name'] }}"
    title="{{ $item['name'] }}"
>
    @if ($item['active'])<span class="absolute inset-y-2 left-0 w-0.5 rounded-full bg-current opacity-80" aria-hidden="true"></span>@endif
    @if (!empty($item['icon']))
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl transition" style="color:{{ $item['active'] ? 'var(--admin-sidebar-active-icon-color)' : 'var(--admin-sidebar-menu-icon-color)' }};background:{{ $item['active'] && $sidebarMode !== 'theme' ? 'color-mix(in srgb, var(--admin-sidebar-active-title-color) 12%, transparent)' : 'var(--admin-sidebar-control-surface,rgba(0,0,0,.04))' }}"><x-icon name="{{ $item['icon'] }}" style="width:var(--admin-sidebar-menu-icon-size);height:var(--admin-sidebar-menu-icon-size)" /></span>
    @else
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-xs font-semibold opacity-70" style="background:var(--admin-sidebar-control-surface,rgba(0,0,0,.04))" aria-hidden="true">{{ mb_strtoupper(mb_substr($item['name'], 0, 1, 'UTF-8'), 'UTF-8') }}</span>
    @endif
    <span x-cloak x-show="sidebarOpen" class="min-w-0 flex-1 truncate whitespace-nowrap">{{ $item['name'] }}</span>
</a>
