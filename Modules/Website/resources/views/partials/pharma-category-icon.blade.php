@php
    $icon = in_array($icon ?? '', ['medical','heart','shield','pill','cross','leaf'], true) ? $icon : 'medical';
@endphp
<svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($icon)
        @case('heart') <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/> @break
        @case('shield') <path d="M12 2 4 5v6c0 5.5 3.5 9 8 11 4.5-2 8-5.5 8-11V5l-8-3Z"/><path d="M9 12l2 2 4-4"/> @break
        @case('pill') <path d="M9 4a5 5 0 0 1 7 7l-5 5a5 5 0 0 1-7-7l5-5Z"/><path d="m7 7 7 7"/> @break
        @case('cross') <path d="M9 3h6v6h6v6h-6v6H9v-6H3V9h6V3Z"/> @break
        @case('leaf') <path d="M20 4C10 4 4 8 4 15a5 5 0 0 0 5 5c7 0 11-6 11-16Z"/><path d="M4 20c2-5 6-8 11-10"/> @break
        @default <rect x="4" y="4" width="16" height="16" rx="4"/><path d="M12 8v8M8 12h8"/>
    @endswitch
</svg>
