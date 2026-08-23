<header
    class="{{ ($headerContext['sticky'] ?? true) ? 'sticky top-0' : 'relative' }} z-30 flex h-16 items-center justify-between border-b border-slate-200 bg-white/90 px-4 backdrop-blur-xl transition-all duration-300 sm:px-6 lg:px-8"
>
    <div class="flex min-w-0 flex-1 items-center gap-3">
        @foreach (($headerContext['left'] ?? []) as $component)
            @include($component['view'])
        @endforeach
    </div>

    <div class="flex shrink-0 items-center gap-3 sm:gap-4">
        @foreach (($headerContext['right'] ?? []) as $component)
            @include($component['view'])
        @endforeach
    </div>
</header>
