@php
    $adminShellPresentation = app(\Modules\Admin\Services\AdminShellPresentationService::class)->context();
    $headerBlur = $adminShellPresentation['header_backdrop_blur'] ?? true;
    $sidebarEnabled = (bool) data_get($adminSidebarConfig ?? [], 'enabled', true);
@endphp

<header
    class="{{ ($headerContext['sticky'] ?? true) ? 'sticky top-0' : 'relative' }} z-30 flex items-center transition-all duration-200 motion-reduce:transition-none {{ $headerBlur ? 'backdrop-blur-xl' : '' }}"
    data-admin-header-mode="{{ $adminShellPresentation['header_mode'] ?? 'balanced' }}"
    style="height: {{ $adminShellPresentation['header_height'] }}; {{ $adminShellPresentation['header_style'] }}; background-color: color-mix(in srgb, var(--admin-header-background) var(--admin-header-background-opacity), transparent); color: var(--admin-text-primary); box-shadow: var(--admin-header-shadow);"
>
    <div
        data-admin-header-grid
        class="grid min-w-0 flex-1 items-center"
        style="grid-template-columns: minmax(0, 1fr) auto; column-gap: {{ $adminShellPresentation['header_action_gap'] }}; padding-right: {{ $adminShellPresentation['header_padding_x'] }}; padding-left: {{ $sidebarEnabled ? $adminShellPresentation['header_padding_x'] : 'calc(' . $adminShellPresentation['header_padding_x'] . ' + 3rem)' }};"
        data-admin-header-reveal-aware
    >
        <div
            data-admin-header-left
            class="grid min-w-0 items-center"
            style="grid-template-columns: repeat(2, max-content) minmax(0, 1fr); column-gap: {{ $adminShellPresentation['header_action_gap'] }};"
        >
            @foreach (($headerContext['left'] ?? []) as $component)
                @include($component['view'], $component['data'] ?? [])
            @endforeach
        </div>

        <div data-admin-header-right class="flex min-w-0 shrink-0 items-center" style="gap: {{ $adminShellPresentation['header_action_gap'] }};">
            @foreach (($headerContext['right'] ?? []) as $component)
                @include($component['view'], $component['data'] ?? [])
            @endforeach
        </div>
    </div>
</header>
