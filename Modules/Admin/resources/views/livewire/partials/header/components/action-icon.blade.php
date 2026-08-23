@php
    $rawIcon = strtolower((string) ($icon ?? 'bell'));
    $name = match (true) {
        str_contains($rawIcon, 'globe') => 'globe',
        str_contains($rawIcon, 'book') => 'book',
        str_contains($rawIcon, 'circle-question'), str_contains($rawIcon, 'help') => 'help',
        str_contains($rawIcon, 'arrow-up-right'), str_contains($rawIcon, 'link') => 'link',
        str_contains($rawIcon, 'message') => 'message',
        str_contains($rawIcon, 'calendar') => 'calendar',
        str_contains($rawIcon, 'star') => 'star',
        str_contains($rawIcon, 'ellipsis') => 'ellipsis',
        default => 'bell',
    };
@endphp

<svg class="{{ $class ?? 'h-5 w-5' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('globe')
            <circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/>
            @break
        @case('book')
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V4H6.5A2.5 2.5 0 0 0 4 6.5v13Z"/><path d="M8 7h8M8 10h8"/>
            @break
        @case('help')
            <circle cx="12" cy="12" r="9"/><path d="M9.7 9a2.5 2.5 0 1 1 4.5 1.5c-.8 1-2.2 1.2-2.2 2.5M12 17h.01"/>
            @break
        @case('link')
            <path d="M14 5h5v5M19 5l-8 8"/><path d="M18 13v5a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>
            @break
        @case('message')
            <path d="M21 15a3 3 0 0 1-3 3H9l-5 3v-3a3 3 0 0 1-1-2.2V8a3 3 0 0 1 3-3h12a3 3 0 0 1 3 3v7Z"/><path d="M8 10h8M8 13h5"/>
            @break
        @case('calendar')
            <rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>
            @break
        @case('star')
            <path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.3 6.4 20.2 7.5 14 3 9.6l6.2-.9L12 3Z"/>
            @break
        @case('ellipsis')
            <circle cx="12" cy="5" r="1" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1" fill="currentColor" stroke="none"/><circle cx="12" cy="19" r="1" fill="currentColor" stroke="none"/>
            @break
        @default
            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/>
    @endswitch
</svg>
