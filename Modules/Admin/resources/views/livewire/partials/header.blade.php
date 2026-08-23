@php
    $adminShellPresentation = app(\Modules\Admin\Services\AdminShellPresentationService::class)->context();
@endphp

<header
    class="{{ ($headerContext['sticky'] ?? true) ? 'sticky top-0' : 'relative' }} z-30 flex items-center justify-between border-b px-4 backdrop-blur-xl transition-all duration-300 sm:px-6 lg:px-8"
    style="height: {{ $adminShellPresentation['header_height'] }}; background-color: color-mix(in srgb, var(--admin-surface-raised) 90%, transparent); border-color: var(--admin-border-subtle); color: var(--admin-text-primary);"
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
