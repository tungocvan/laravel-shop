@php
    $adminShellPresentation = app(\Modules\Admin\Services\AdminShellPresentationService::class)->context();
@endphp

<header
    class="{{ ($headerContext['sticky'] ?? true) ? 'sticky top-0' : 'relative' }} z-30 flex items-center backdrop-blur-xl transition-all duration-200 motion-reduce:transition-none"
    style="height: {{ $adminShellPresentation['header_height'] }}; background-color: color-mix(in srgb, var(--admin-surface-raised) 94%, transparent); color: var(--admin-text-primary); box-shadow: 0 1px 0 color-mix(in srgb, var(--admin-border-subtle) 72%, transparent);"
>
    <div class="flex min-w-0 flex-1 items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
        <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3">
            @foreach (($headerContext['left'] ?? []) as $component)
                @include($component['view'], $component['data'] ?? [])
            @endforeach
        </div>

        <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
            @foreach (($headerContext['right'] ?? []) as $component)
                @include($component['view'], $component['data'] ?? [])
            @endforeach
        </div>
    </div>
</header>
