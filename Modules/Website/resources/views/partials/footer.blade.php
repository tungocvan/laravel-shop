{{--
    FOOTER COMPONENT
    Data Injected by: Modules/Website/Providers/WebsiteServiceProvider.php (View::composer)
    Variables: $footerSettings, $footerColumns, $socialLinks, $footerPresentation, $footerLayout
--}}

@php
    $footerColors = $footerPresentation['colors'] ?? [];
    $footerContainer = $footerPresentation['container_width'] ?? null;
@endphp

<footer class="relative font-website-body"
    style="
        --footer-background: {{ $footerColors['background'] ?? '#111827' }};
        --footer-foreground: {{ $footerColors['foreground'] ?? '#9ca3af' }};
        --footer-heading: {{ $footerColors['heading'] ?? '#ffffff' }};
        --footer-muted: {{ $footerColors['muted'] ?? '#6b7280' }};
        --footer-accent: {{ $footerColors['accent'] ?? '#2563eb' }};
        --footer-border: {{ $footerColors['border'] ?? '#1f2937' }};
        --footer-padding-top: {{ (int) ($footerPresentation['padding_top'] ?? 64) }}px;
        --footer-padding-bottom: {{ (int) ($footerPresentation['padding_bottom'] ?? 32) }}px;
        --footer-column-gap: {{ (int) ($footerPresentation['column_gap'] ?? 48) }}px;
        --footer-section-gap: {{ (int) ($footerPresentation['section_gap'] ?? 64) }}px;
        --footer-logo-max: {{ (int) data_get($footerPresentation, 'custom.logo_max_height', 40) }}px;
        --footer-social-size: {{ (int) data_get($footerPresentation, 'custom.social_icon_size', 40) }}px;
        background: var(--footer-background);
        color: var(--footer-foreground);
        border-top: {{ ($footerPresentation['border'] ?? true) ? '1px solid var(--footer-border)' : '0' }};
    ">
    @if($footerPresentation['accent'] ?? true)
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500"></div>
    @endif

    @include('Website::components.footer.slot', ['slot' => 'desktop.top'])

    <div class="mx-auto px-4"
        style="max-width: {{ $footerContainer ? (int) $footerContainer.'px' : '100%' }}; padding-top: var(--footer-padding-top); padding-bottom: var(--footer-padding-bottom);">
        <div class="hidden md:grid md:grid-cols-2 lg:grid-cols-12"
            style="gap: var(--footer-column-gap); margin-bottom: var(--footer-section-gap);">
            <div class="lg:col-span-4 space-y-6">
                @include('Website::components.footer.slot', ['slot' => 'desktop.main.brand'])
            </div>

            @include('Website::components.footer.slot', ['slot' => 'desktop.main.columns'])

            <div class="lg:col-span-3 space-y-6">
                @include('Website::components.footer.slot', ['slot' => 'desktop.main.extra'])
            </div>
        </div>

        <div class="md:hidden space-y-8" style="margin-bottom: var(--footer-section-gap);">
            @include('Website::components.footer.slot', ['slot' => 'mobile.main'])
        </div>

        <div class="hidden md:flex items-center justify-between gap-6 pt-8" style="border-top: 1px solid var(--footer-border);">
            <div class="text-xs text-center md:text-left" style="color: var(--footer-muted);">
                @include('Website::components.footer.slot', ['slot' => 'desktop.bottom.left'])
            </div>
            @include('Website::components.footer.slot', ['slot' => 'desktop.bottom.right'])
        </div>

        <div class="md:hidden flex flex-col items-center gap-6 pt-8 text-xs text-center" style="border-top: 1px solid var(--footer-border); color: var(--footer-muted);">
            @include('Website::components.footer.slot', ['slot' => 'mobile.bottom'])
        </div>
    </div>
</footer>

@include('Website::components.footer.slot', ['slot' => 'overlay'])

@livewire('website.chat.chat-widget')
