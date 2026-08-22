<div class="flex flex-wrap" style="gap: 1rem;">
    @foreach($socialLinks as $social)
        @php
            $platform = strtolower((string) ($social->platform ?: $social->name));
        @endphp
        <a href="{{ $social->url }}" target="_blank" rel="noopener noreferrer"
           class="rounded-full flex items-center justify-center transition-all transform hover:-translate-y-1 hover:brightness-125"
           style="width: var(--footer-social-size); height: var(--footer-social-size); background: color-mix(in srgb, var(--footer-background) 80%, white 20%); color: var(--footer-heading);"
           title="{{ $social->name }}" aria-label="{{ $social->name }}">
            @if(str_contains($platform, 'facebook'))
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13.5 8H16l.4-3h-2.9C10.7 5 9 6.7 9 9.5V11H6.5v3H9v7h3v-7h3l.5-3H12V9.7c0-1.1.3-1.7 1.5-1.7Z"/></svg>
            @elseif(str_contains($platform, 'instagram'))
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
            @elseif(str_contains($platform, 'youtube'))
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21.6 7.2a2.8 2.8 0 0 0-2-2C17.8 4.7 12 4.7 12 4.7s-5.8 0-7.6.5a2.8 2.8 0 0 0-2 2A29 29 0 0 0 2 12a29 29 0 0 0 .4 4.8 2.8 2.8 0 0 0 2 2c1.8.5 7.6.5 7.6.5s5.8 0 7.6-.5a2.8 2.8 0 0 0 2-2A29 29 0 0 0 22 12a29 29 0 0 0-.4-4.8ZM10 15.5v-7l6 3.5-6 3.5Z"/></svg>
            @elseif(str_contains($platform, 'linkedin'))
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.5 8.5H3.4V21h3.1V8.5ZM5 3A1.8 1.8 0 1 0 5 6.6 1.8 1.8 0 0 0 5 3ZM21 13.8c0-3.8-2-5.6-4.7-5.6-2.2 0-3.1 1.2-3.7 2v-1.7H9.5V21h3.1v-6.2c0-1.6.3-3.2 2.3-3.2 2 0 2 1.8 2 3.3V21H20v-7.2Z"/></svg>
            @elseif(str_contains($platform, 'twitter') || $platform === 'x')
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 4h4.7l4.1 5.6L17.7 4H20l-6.1 7 6.5 9H15.7l-4.5-6.2L5.8 20H3.5l6.6-7.6L4 4Zm3.5 2L16.7 18h1.9L9.4 6H7.5Z"/></svg>
            @else
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
            @endif
        </a>
    @endforeach
</div>
