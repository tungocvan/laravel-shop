@php
    $adminShellPresentation = app(\Modules\Admin\Services\AdminShellPresentationService::class)->context();
    $headerBlur = $adminShellPresentation['header_backdrop_blur'] ?? true;
@endphp

<header
    class="{{ ($headerContext['sticky'] ?? true) ? 'sticky top-0' : 'relative' }} z-30 flex items-center transition-all duration-200 motion-reduce:transition-none {{ $headerBlur ? 'backdrop-blur-xl' : '' }}"
    data-admin-header-mode="{{ $adminShellPresentation['header_mode'] ?? 'balanced' }}"
    style="height: {{ $adminShellPresentation['header_height'] }}; {{ $adminShellPresentation['header_style'] }}; background-color: color-mix(in srgb, var(--admin-header-background) var(--admin-header-background-opacity), transparent); color: var(--admin-text-primary); box-shadow: var(--admin-header-shadow);"
>
    <div
        data-admin-header-grid
        class="grid min-w-0 flex-1 items-center"
        style="grid-template-columns: minmax(0, 1fr) auto; column-gap: {{ $adminShellPresentation['header_action_gap'] }}; padding-inline: {{ $adminShellPresentation['header_padding_x'] }};"
        data-admin-header-reveal-aware
    >
        <div
            data-admin-header-left
            class="grid min-w-0 items-center"
            style="grid-template-columns: {{ $sidebarEnabled ? 'repeat(2, max-content) minmax(0, 1fr)' : 'repeat(3, max-content) minmax(0, 1fr)' }}; column-gap: {{ $adminShellPresentation['header_action_gap'] }};"
        >
            @if (!$sidebarEnabled)
                <button
                    type="button"
                    x-cloak
                    x-show.important="isDesktop && !sidebarOpen"
                    @click="sidebarFullscreen = false; sidebarOpen = true"
                    class="hidden h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white/95 text-slate-600 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 lg:inline-flex"
                    data-admin-sidebar-disabled-reveal
                    aria-controls="admin-sidebar"
                    :aria-expanded="sidebarOpen.toString()"
                    aria-label="Mở Sidebar"
                    title="Mở Sidebar"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5h16v14H4zM9 5v14M11 9l3 3-3 3" />
                    </svg>
                </button>
            @endif
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
