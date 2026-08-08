<aside
    class="flex h-full flex-col transition-all duration-300 ease-[cubic-bezier(0.4,0,0.2,1)] motion-reduce:transition-none
    {{ $theme['background'] }} {{ $theme['text'] }}"
    :class="sidebarOpen ? 'w-64' : 'w-20'"
>
    @php
        $adminSidebarConfig = app(\Modules\Admin\Support\AdminLayoutManager::class)->config()['sidebar'] ?? [];
    @endphp

    <div class="relative flex h-16 items-center justify-center border-b px-4 {{ $theme['border'] }}">
        <div class="flex min-w-0 items-center gap-3">
            <div x-show="!sidebarOpen" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-500 text-xs font-bold text-white">
                {{ $schoolAcronym }}
            </div>

            <span x-cloak x-show="sidebarOpen" class="text-center text-sm font-bold uppercase leading-tight">
                @if($schoolPrefix)
                    <span class="block tracking-wide">{{ $schoolPrefix }}</span>
                @endif
                <span class="block tracking-widest text-indigo-500">{{ $schoolDisplayName }}</span>
            </span>
        </div>

        <button
            type="button"
            @click="toggleSidebar($event.currentTarget)"
            class="absolute -right-4 top-2 z-10 inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-md transition hover:bg-slate-50 hover:text-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            aria-label="Thu gọn hoặc mở rộng menu"
            aria-controls="admin-sidebar"
            :aria-expanded="sidebarOpen.toString()"
            title="Thu gọn hoặc mở rộng menu"
        >
            <svg
                :class="sidebarOpen ? 'rotate-180' : ''"
                class="h-4 w-4 transition-transform duration-300 motion-reduce:transition-none"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                aria-hidden="true"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 5l7 7-7 7" />
            </svg>
        </button>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-2 py-4" aria-label="Admin navigation">
        @foreach ($menus as $menu)
            @php
                $children = collect($menu['children'] ?? []);
                $hasChildren = !empty($menu['has_children']) && $children->isNotEmpty();
                $isActive = (bool) ($menu['active'] ?? false);
                $groupId = 'admin-nav-group-' . $menu['id'];
            @endphp

            @if (!$hasChildren)
                <a href="{{ !empty($menu['url']) ? url($menu['url']) : '#' }}"
                   class="group relative flex min-h-11 items-center gap-3 rounded-lg px-3 py-2 text-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 motion-reduce:transition-none
                   {{ $isActive 
                        ? 'bg-indigo-50 text-indigo-600 shadow-sm' 
                        : 'text-gray-600 hover:bg-gray-100 active:scale-[0.98]' }}"
                   @if ($isActive) aria-current="page" @endif
                   aria-label="{{ $menu['name'] }}"
                   title="{{ $menu['name'] }}">

                    @if (!empty($menu['icon']))
                        <x-icon
                            name="{{ $menu['icon'] }}"
                            class="w-5 h-5 flex-shrink-0
                            {{ $isActive ? 'text-indigo-600' : 'text-gray-400' }}"
                        />
                    @endif

                    <span x-cloak x-show="sidebarOpen" class="truncate whitespace-nowrap">
                        {{ $menu['name'] }}
                    </span>
                </a>

            @elseif ($hasChildren)
                <div x-data="{ open: {{ $isActive ? 'true' : 'false' }} }">
                    <button
                        @click="sidebarOpen ? open = !open : sidebarOpen = true"
                        class="group relative flex min-h-11 w-full items-center justify-between rounded-lg px-3 py-2 text-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 motion-reduce:transition-none
                        {{ $isActive 
                            ? 'bg-indigo-50 text-indigo-600 shadow-sm' 
                            : 'text-gray-600 hover:bg-gray-100' }}"
                        aria-controls="{{ $groupId }}"
                        :aria-expanded="(open && sidebarOpen).toString()"
                        aria-label="{{ $menu['name'] }}"
                        title="{{ $menu['name'] }}"
                    >

                        <span class="flex min-w-0 items-center gap-3">
                            @if (!empty($menu['icon']))
                                <x-icon
                                    name="{{ $menu['icon'] }}"
                                    class="w-5 h-5 flex-shrink-0
                                    {{ $isActive ? 'text-indigo-600' : 'text-gray-400' }}"
                                />
                            @endif

                            <span x-cloak x-show="sidebarOpen" class="truncate whitespace-nowrap">
                                {{ $menu['name'] }}
                            </span>
                        </span>

                        <svg x-cloak x-show="sidebarOpen"
                            :class="open ? 'rotate-90' : ''"
                            class="h-4 w-4 shrink-0 transition-transform duration-200 motion-reduce:transition-none"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                            aria-hidden="true">
                            <path d="M6 6L14 10L6 14V6Z" />
                        </svg>

                    </button>

                    <div id="{{ $groupId }}" x-cloak x-show="open && sidebarOpen" x-collapse class="ml-8 mt-1 space-y-1">
                        @foreach ($children as $child)
                            @php
                                $childActive = (bool) ($child['active'] ?? false);
                            @endphp

                            <a href="{{ url($child['url']) }}"
                               class="flex min-h-10 items-center gap-2 rounded-lg px-3 py-2 text-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 motion-reduce:transition-none
                               {{ $childActive
                                    ? 'bg-indigo-100 text-indigo-600'
                                    : 'text-gray-500 hover:bg-gray-100' }}"
                               @if ($childActive) aria-current="page" @endif>

                                <svg class="h-3.5 w-3.5 shrink-0 opacity-70" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                    <path d="M6 6L14 10L6 14V6Z" />
                                </svg>

                                <span class="truncate">{{ $child['name'] }}</span>
                            </a>

                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </nav>

    @if (data_get($adminSidebarConfig, 'show_footer_profile', true))
        <div class="border-t border-gray-200 p-4">
            <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-500 text-xs font-bold text-white">
                    {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
                </div>

                <div x-cloak x-show="sidebarOpen" class="overflow-hidden whitespace-nowrap">
                    <p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-500">View Profile</p>
                </div>
            </div>
        </div>
    @endif
</aside>
