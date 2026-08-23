<div x-data="{ open: {{ $item['active'] ? 'true' : 'false' }} }">
    <button
        @click="sidebarOpen ? open = !open : sidebarOpen = true"
        class="group relative flex min-h-11 w-full items-center justify-between rounded-lg px-3 py-2 text-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 motion-reduce:transition-none
            {{ $item['active'] ? 'bg-indigo-50 text-indigo-600 shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}"
        aria-controls="{{ $item['group_id'] }}"
        :aria-expanded="(open && sidebarOpen).toString()"
        aria-label="{{ $item['name'] }}"
        title="{{ $item['name'] }}"
    >
        <span class="flex min-w-0 items-center gap-3">
            @if (!empty($item['icon']))
                <x-icon
                    name="{{ $item['icon'] }}"
                    class="h-5 w-5 flex-shrink-0 {{ $item['active'] ? 'text-indigo-600' : 'text-gray-400' }}"
                />
            @endif

            <span x-cloak x-show="sidebarOpen" class="truncate whitespace-nowrap">
                {{ $item['name'] }}
            </span>
        </span>

        <svg
            x-cloak
            x-show="sidebarOpen"
            :class="open ? 'rotate-90' : ''"
            class="h-4 w-4 shrink-0 transition-transform duration-200 motion-reduce:transition-none"
            fill="currentColor"
            viewBox="0 0 20 20"
            aria-hidden="true"
        >
            <path d="M6 6L14 10L6 14V6Z" />
        </svg>
    </button>

    <div
        id="{{ $item['group_id'] }}"
        x-cloak
        x-show="open && sidebarOpen"
        x-collapse
        class="ml-8 mt-1 space-y-1"
    >
        @foreach ($item['children'] as $child)
            <a
                href="{{ $child['href'] }}"
                class="flex min-h-10 items-center gap-2 rounded-lg px-3 py-2 text-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 motion-reduce:transition-none
                    {{ $child['active'] ? 'bg-indigo-100 text-indigo-600' : 'text-gray-500 hover:bg-gray-100' }}"
                @if ($child['active']) aria-current="page" @endif
            >
                <svg class="h-3.5 w-3.5 shrink-0 opacity-70" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path d="M6 6L14 10L6 14V6Z" />
                </svg>

                <span class="truncate">{{ $child['name'] }}</span>
            </a>
        @endforeach
    </div>
</div>
