<div class="flex flex-wrap" style="gap: 1rem;">
    @foreach($socialLinks as $social)
        <a href="{{ $social->url }}" target="_blank"
           class="rounded-full flex items-center justify-center transition-all transform hover:-translate-y-1"
           style="width: var(--footer-social-size); height: var(--footer-social-size); background: color-mix(in srgb, var(--footer-background) 80%, white 20%); color: var(--footer-foreground);"
           title="{{ $social->name }}" aria-label="{{ $social->name }}">
            @if($social->icon_class)
                <i class="{{ $social->icon_class }} text-lg"></i>
            @else
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
            @endif
        </a>
    @endforeach
</div>
