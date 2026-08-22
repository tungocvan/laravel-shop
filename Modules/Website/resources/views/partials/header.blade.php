@php
    $headerPresentation ??= app(\Modules\Website\Services\HeaderPresentationService::class)->resolve();
    $inheritColors = $headerPresentation['inherit_colors'] ?? true;
    $headerColors = $headerPresentation['colors'] ?? [];
    $headerBg = $inheritColors ? 'var(--website-color-surface)' : ($headerColors['background'] ?? '#ffffff');
    $headerText = $inheritColors ? 'var(--website-color-text)' : ($headerColors['foreground'] ?? '#111827');
    $headerBorder = $inheritColors ? 'var(--website-color-border)' : ($headerColors['border'] ?? '#e5e7eb');
    $headerAccent = $inheritColors ? 'var(--website-color-primary)' : ($headerColors['accent'] ?? '#2563eb');
    $containerWidth = $headerPresentation['container_width'] ?? null;
    $heights = $headerPresentation['heights'] ?? ['desktop' => 80, 'tablet' => 72, 'mobile' => 64];
    $custom = $headerPresentation['custom'] ?? [];
    $shadow = match($headerPresentation['shadow'] ?? 'soft') {
        'none' => 'none',
        'medium' => 'var(--website-shadow-medium)',
        default => 'var(--website-shadow-soft)',
    };
@endphp

<div style="--header-bg: {{ $headerBg }}; --header-text: {{ $headerText }}; --header-border: {{ $headerBorder }}; --header-accent: {{ $headerAccent }}; --header-height-desktop: {{ (int)($heights['desktop'] ?? 80) }}px; --header-height-tablet: {{ (int)($heights['tablet'] ?? 72) }}px; --header-height-mobile: {{ (int)($heights['mobile'] ?? 64) }}px; --header-logo-max: {{ (int)($custom['logo_max_height'] ?? 48) }}px; --header-search-max: {{ (int)($custom['search_max_width'] ?? 560) }}px; --header-topbar-height: {{ (int)($custom['topbar_height'] ?? 32) }}px;">
    @include('Website::components.header.slot', ['slot' => 'desktop.topbar'])

    <header class="{{ ($headerPresentation['sticky'] ?? true) ? 'sticky top-0' : 'relative' }} z-50 backdrop-blur-md border-b"
        style="background-color: color-mix(in srgb, var(--header-bg) 95%, transparent); color: var(--header-text); border-color: var(--header-border); box-shadow: {{ $shadow }};"
        x-data="{ userDropdownOpen:false, mobileMenuOpen:false, mobileSearchOpen:false }">
        <div class="mx-auto px-4 sm:px-6 lg:px-8" @if($containerWidth) style="max-width: {{ (int)$containerWidth }}px" @endif>
            <div class="header-main-row flex justify-between items-center gap-4">
                @include('Website::components.header.slot', ['slot' => 'desktop.main.left'])
                @include('Website::components.header.slot', ['slot' => 'desktop.main.center'])
                @include('Website::components.header.slot', ['slot' => 'desktop.main.right'])
            </div>

            @include('Website::components.header.slot', ['slot' => 'mobile.search'])
        </div>

        @include('Website::components.header.slot', ['slot' => 'mobile.drawer'])
    </header>
</div>

<style>
    .header-main-row { min-height: var(--header-height-mobile); }
    @media (min-width: 768px) { .header-main-row { min-height: var(--header-height-tablet); } }
    @media (min-width: 1024px) { .header-main-row { min-height: var(--header-height-desktop); } }
</style>
