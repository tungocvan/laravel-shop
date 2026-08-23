<div x-data="{ open: {{ $item['active'] ? 'true' : 'false' }}, filterEnabled: @js($showNavigationSearch) }">
    <button
        @click="sidebarOpen ? open = !open : sidebarOpen = true"
        class="group relative flex w-full items-center rounded-lg transition duration-150 focus:outline-none focus:ring-2 focus:ring-current focus:ring-offset-1 motion-reduce:transition-none {{ $item['active'] ? $theme['active_bg'] : $theme['hover'] }}"
        style="min-height:var(--admin-sidebar-menu-item-height);padding:var(--admin-sidebar-menu-padding-y) var(--admin-sidebar-menu-padding-x);gap:var(--admin-sidebar-menu-content-gap);font-family:var(--admin-sidebar-menu-font-family);font-size:var(--admin-sidebar-menu-font-size);font-weight:{{ $item['active'] ? 'var(--admin-sidebar-active-font-weight)' : 'var(--admin-sidebar-menu-font-weight)' }};color:{{ $item['active'] ? 'var(--admin-sidebar-active-title-color)' : 'var(--admin-sidebar-menu-title-color)' }}"
        :class="sidebarOpen ? '' : 'justify-center'"
        aria-controls="{{ $item['group_id'] }}"
        :aria-expanded="((open || (filterEnabled && navQuery.trim() !== '')) && sidebarOpen).toString()"
        aria-label="{{ $item['name'] }}"
        title="{{ $item['name'] }}"
    >
        @if ($item['active'])<span class="absolute inset-y-2 left-0 w-0.5 rounded-full bg-current opacity-80" aria-hidden="true"></span>@endif
        @if (!empty($item['icon']))
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-black/5" style="color:{{ $item['active'] ? 'var(--admin-sidebar-active-icon-color)' : 'var(--admin-sidebar-menu-icon-color)' }}"><x-icon name="{{ $item['icon'] }}" style="width:var(--admin-sidebar-menu-icon-size);height:var(--admin-sidebar-menu-icon-size)" /></span>
        @else
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-black/5 text-xs font-semibold opacity-70" aria-hidden="true">{{ mb_strtoupper(mb_substr($item['name'], 0, 1, 'UTF-8'), 'UTF-8') }}</span>
        @endif
        <span x-cloak x-show="sidebarOpen" class="min-w-0 flex-1 truncate whitespace-nowrap text-left">{{ $item['name'] }}</span>
        <svg x-cloak x-show="sidebarOpen" :class="(open || (filterEnabled && navQuery.trim() !== '')) ? 'rotate-90' : ''" class="h-3.5 w-3.5 shrink-0 opacity-50 transition-transform duration-150 motion-reduce:transition-none" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M6 6L14 10L6 14V6Z" /></svg>
    </button>

    <div id="{{ $item['group_id'] }}" x-cloak x-show="(open || (filterEnabled && navQuery.trim() !== '')) && sidebarOpen" x-collapse class="flex flex-col border-l {{ $theme['border'] }}" style="margin-left:var(--admin-sidebar-submenu-indent);margin-top:var(--admin-sidebar-menu-group-gap);padding-left:var(--admin-sidebar-submenu-offset);gap:var(--admin-sidebar-submenu-item-gap)">
        @foreach ($item['children'] as $child)
            <a
                href="{{ $child['href'] }}"
                @if($showNavigationSearch) x-show="navQuery.trim() === '' || matches(@js($child['name'])) || matches(@js($item['name']))" @endif
                class="relative flex items-center rounded-md transition duration-150 focus:outline-none focus:ring-2 focus:ring-current focus:ring-offset-1 motion-reduce:transition-none {{ $child['active'] ? $theme['child_active_bg'] : $theme['child_hover'] }}"
                style="min-height:var(--admin-sidebar-submenu-item-height);padding:var(--admin-sidebar-submenu-padding-y) var(--admin-sidebar-submenu-padding-x);font-family:var(--admin-sidebar-submenu-font-family);font-size:var(--admin-sidebar-submenu-font-size);font-weight:{{ $child['active'] ? 'var(--admin-sidebar-active-font-weight)' : 'var(--admin-sidebar-submenu-font-weight)' }};color:{{ $child['active'] ? 'var(--admin-sidebar-active-title-color)' : 'var(--admin-sidebar-submenu-title-color)' }}"
                @if ($child['active']) aria-current="page" @endif
            >
                @if ($child['active'])<span class="absolute -left-[13px] h-5 w-0.5 rounded-full bg-current" aria-hidden="true"></span>@endif
                <span class="truncate">{{ $child['name'] }}</span>
            </a>
        @endforeach
    </div>
</div>
