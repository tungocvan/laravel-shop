@php
    $adminShellPresentation = app(\Modules\Admin\Services\AdminShellPresentationService::class)->context();
    $headerBlur = $adminShellPresentation['header_backdrop_blur'] ?? true;
@endphp

<header
    class="{{ ($headerContext['sticky'] ?? true) ? 'sticky top-0' : 'relative' }} z-30 flex items-center transition-all duration-200 motion-reduce:transition-none {{ $headerBlur ? 'backdrop-blur-xl' : '' }}"
    data-admin-header-mode="{{ $adminShellPresentation['header_mode'] ?? 'balanced' }}"
    style="height: {{ $adminShellPresentation['header_height'] }}; {{ $adminShellPresentation['header_style'] }}; background-color: color-mix(in srgb, var(--admin-header-background) var(--admin-header-background-opacity), transparent); color: var(--admin-text-primary); box-shadow: var(--admin-header-shadow);"
>
    <div class="flex min-w-0 flex-1 items-center justify-between gap-3" style="padding-inline: {{ $adminShellPresentation['header_padding_x'] }};">
        <div class="flex min-w-0 flex-1 items-center" style="gap: {{ $adminShellPresentation['header_action_gap'] }};">
            @foreach (($headerContext['left'] ?? []) as $component)
                @include($component['view'], $component['data'] ?? [])
            @endforeach
        </div>

        <div class="flex shrink-0 items-center" style="gap: {{ $adminShellPresentation['header_action_gap'] }};">
            @foreach (($headerContext['right'] ?? []) as $component)
                @include($component['view'], $component['data'] ?? [])
            @endforeach
        </div>
    </div>
</header>
