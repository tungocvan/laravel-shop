<a
    href="{{ $item['href'] }}"
    class="group relative flex min-h-11 items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition duration-150 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1 motion-reduce:transition-none
        {{ $item['active'] ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}"
    @if ($item['active']) aria-current="page" @endif
    aria-label="{{ $item['name'] }}"
    title="{{ $item['name'] }}"
>
    @if ($item['active'])
        <span class="absolute inset-y-2 left-0 w-0.5 rounded-full bg-indigo-500" aria-hidden="true"></span>
    @endif

    @if (!empty($item['icon']))
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $item['active'] ? 'bg-indigo-100 text-indigo-600' : 'text-gray-400 group-hover:bg-white group-hover:text-gray-600' }}">
            <x-icon name="{{ $item['icon'] }}" class="h-5 w-5" />
        </span>
    @endif

    <span x-cloak x-show="sidebarOpen" class="min-w-0 flex-1 truncate whitespace-nowrap">{{ $item['name'] }}</span>
</a>
