<a
    href="{{ $item['href'] }}"
    class="group relative flex min-h-11 items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition duration-150 focus:outline-none focus:ring-2 focus:ring-current focus:ring-offset-1 motion-reduce:transition-none {{ $item['active'] ? $theme['active_bg'].' '.$theme['active_text'] : $theme['text'].' '.$theme['hover'] }}"
    :class="sidebarOpen ? '' : 'justify-center'"
    @if ($item['active']) aria-current="page" @endif
    aria-label="{{ $item['name'] }}"
    title="{{ $item['name'] }}"
>
    @if ($item['active'])
        <span class="absolute inset-y-2 left-0 w-0.5 rounded-full bg-current opacity-80" aria-hidden="true"></span>
    @endif

    @if (!empty($item['icon']))
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-black/5 {{ $item['active'] ? $theme['active_text'] : $theme['icon_inactive'] }}">
            <x-icon name="{{ $item['icon'] }}" class="h-5 w-5" />
        </span>
    @else
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-black/5 text-xs font-semibold opacity-70" aria-hidden="true">{{ mb_strtoupper(mb_substr($item['name'], 0, 1, 'UTF-8'), 'UTF-8') }}</span>
    @endif

    <span x-cloak x-show="sidebarOpen" class="min-w-0 flex-1 truncate whitespace-nowrap">{{ $item['name'] }}</span>
</a>
