<div x-data="{ open: {{ $item['active'] ? 'true' : 'false' }} }">
    <button
        @click="sidebarOpen ? open = !open : sidebarOpen = true"
        class="group relative flex min-h-11 w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition duration-150 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1 motion-reduce:transition-none {{ $item['active'] ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}"
        aria-controls="{{ $item['group_id'] }}"
        :aria-expanded="((open || navQuery.trim() !== '') && sidebarOpen).toString()"
        aria-label="{{ $item['name'] }}"
        title="{{ $item['name'] }}"
    >
        @if ($item['active'])<span class="absolute inset-y-2 left-0 w-0.5 rounded-full bg-indigo-500" aria-hidden="true"></span>@endif
        @if (!empty($item['icon']))
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $item['active'] ? 'bg-indigo-100 text-indigo-600' : 'text-gray-400 group-hover:bg-white group-hover:text-gray-600' }}"><x-icon name="{{ $item['icon'] }}" class="h-5 w-5" /></span>
        @endif
        <span x-cloak x-show="sidebarOpen" class="min-w-0 flex-1 truncate whitespace-nowrap text-left">{{ $item['name'] }}</span>
        <svg x-cloak x-show="sidebarOpen" :class="(open || navQuery.trim() !== '') ? 'rotate-90' : ''" class="h-3.5 w-3.5 shrink-0 opacity-50 transition-transform duration-150 motion-reduce:transition-none" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M6 6L14 10L6 14V6Z" /></svg>
    </button>

    <div id="{{ $item['group_id'] }}" x-cloak x-show="(open || navQuery.trim() !== '') && sidebarOpen" x-collapse class="ml-7 mt-1 space-y-0.5 border-l border-slate-200 pl-3">
        @foreach ($item['children'] as $child)
            <a href="{{ $child['href'] }}" x-show="navQuery.trim() === '' || matches(@js($child['name'])) || matches(@js($item['name']))" class="relative flex min-h-9 items-center rounded-md px-3 py-1.5 text-[13px] transition duration-150 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1 motion-reduce:transition-none {{ $child['active'] ? 'bg-indigo-50 font-medium text-indigo-700' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800' }}" @if ($child['active']) aria-current="page" @endif>
                @if ($child['active'])<span class="absolute -left-[13px] h-5 w-0.5 rounded-full bg-indigo-500" aria-hidden="true"></span>@endif
                <span class="truncate">{{ $child['name'] }}</span>
            </a>
        @endforeach
    </div>
</div>
