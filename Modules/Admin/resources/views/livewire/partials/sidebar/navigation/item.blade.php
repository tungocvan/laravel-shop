<a
    href="{{ $item['href'] }}"
    class="group relative flex min-h-11 items-center gap-3 rounded-lg px-3 py-2 text-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 motion-reduce:transition-none
        {{ $item['active'] ? 'bg-indigo-50 text-indigo-600 shadow-sm' : 'text-gray-600 hover:bg-gray-100 active:scale-[0.98]' }}"
    @if ($item['active']) aria-current="page" @endif
    aria-label="{{ $item['name'] }}"
    title="{{ $item['name'] }}"
>
    @if (!empty($item['icon']))
        <x-icon
            name="{{ $item['icon'] }}"
            class="h-5 w-5 flex-shrink-0 {{ $item['active'] ? 'text-indigo-600' : 'text-gray-400' }}"
        />
    @endif

    <span x-cloak x-show="sidebarOpen" class="truncate whitespace-nowrap">
        {{ $item['name'] }}
    </span>
</a>
