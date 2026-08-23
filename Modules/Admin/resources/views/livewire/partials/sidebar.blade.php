<aside
    class="flex h-full w-full flex-col transition-all duration-300 ease-[cubic-bezier(0.4,0,0.2,1)] motion-reduce:transition-none
    {{ $theme['background'] }} {{ $theme['text'] }}"
>
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
            class="absolute -right-4 top-2 z-10 hidden h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-md transition hover:bg-slate-50 hover:text-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 lg:inline-flex"
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </button>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-2 py-4" aria-label="Admin navigation">
        @foreach ($menus as $item)
            @include('Admin::livewire.partials.sidebar.navigation.' . $item['kind'], ['item' => $item])
        @endforeach
    </nav>

    @if ($showFooterProfile)
        <div class="border-t border-gray-200 p-4">
            <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-500 text-xs font-bold text-white">
                    {{ $profileInitial }}
                </div>

                <div x-cloak x-show="sidebarOpen" class="overflow-hidden whitespace-nowrap">
                    <p class="truncate text-sm font-semibold">{{ $profileName }}</p>
                    <p class="text-xs text-gray-500">View Profile</p>
                </div>
            </div>
        </div>
    @endif
</aside>
