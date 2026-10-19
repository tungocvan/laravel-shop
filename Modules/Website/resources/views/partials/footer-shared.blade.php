{{--
    FOOTER COMPONENT
    Data Injected by: Modules/Website/Providers/WebsiteServiceProvider.php (View::composer)
    Variables: $footerSettings, $footerColumns, $socialLinks, $footerPresentation, $footerLayout, $websiteFeatures
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
        <div class="absolute top-0 left-0 right-0 h-[2px]" style="background: var(--footer-accent);"></div>
    @endif

    @include('Website::components.footer.slot', ['slot' => 'desktop.top'])

    <div class="mx-auto px-4"
        style="max-width: {{ $footerContainer ? (int) $footerContainer.'px' : '100%' }}; padding-top: var(--footer-padding-top); padding-bottom: var(--footer-padding-bottom);">
        @if($footerProfileColumnsActive ?? false)
            {{-- Published profile columns own the primary layout. Legacy slots must not render in parallel. --}}
            @php
                $desktopProfileColumns = $footerColumns->filter(fn ($column) => $column->desktop ?? true)->values();
                $desktopWidths = $desktopProfileColumns->map(fn ($column) => ($column->module ?? '') === 'app-install' ? max(4, (int) ($column->width ?? 3)) : max(2, (int) ($column->width ?? 3)));
                $desktopTemplate = $desktopWidths->map(fn ($width) => 'minmax(0, '.$width.'fr)')->implode(' ');
            @endphp
            <div class="hidden md:grid" style="grid-template-columns: {{ $desktopTemplate ?: 'minmax(0, 1fr)' }}; gap: clamp(20px, 2.4vw, var(--footer-column-gap)); margin-bottom: clamp(24px, 3vw, 44px);">
                @foreach($footerColumns as $column)
                    @if($column->desktop ?? true)
                        <div class="min-w-0 space-y-4" style="min-width: 0;">
                            @include('Website::components.footer.profile-column', ['column' => $column])
                        </div>
                    @endif
                @endforeach
            </div>
            <div class="md:hidden space-y-7" style="margin-bottom: 28px;">
                @foreach($footerColumns as $column)
                    @if($column->mobile ?? true)
                        <div class="space-y-4">@include('Website::components.footer.profile-column', ['column' => $column])</div>
                    @endif
                @endforeach
            </div>
        @else
        <div class="hidden md:grid md:grid-cols-2 lg:grid-cols-12" style="gap: var(--footer-column-gap); margin-bottom: clamp(24px, 3vw, 44px);">
            <div class="lg:col-span-4 space-y-6">@include('Website::components.footer.slot', ['slot' => 'desktop.main.brand'])</div>
            @include('Website::components.footer.slot', ['slot' => 'desktop.main.columns'])
            <div class="lg:col-span-3 space-y-6">@include('Website::components.footer.slot', ['slot' => 'desktop.main.extra'])</div>
        </div>
        <div class="md:hidden space-y-7" style="margin-bottom: 28px;">@include('Website::components.footer.slot', ['slot' => 'mobile.main'])</div>
        @endif
        @if($footerBottomBarEnabled ?? true)
        <div class="hidden md:flex items-start justify-between gap-x-8 gap-y-4 pt-5" style="border-top: 1px solid rgba(255, 255, 255, 0.18);">
            <div class="min-w-0 flex-1 space-y-2 text-xs leading-relaxed text-left" style="color: var(--footer-muted);">@include('Website::components.footer.slot', ['slot' => 'desktop.bottom.left'])</div>
            <div class="flex shrink-0 items-center justify-end">@include('Website::components.footer.slot', ['slot' => 'desktop.bottom.right'])</div>
        </div>
        <div class="md:hidden flex flex-col items-center gap-4 pt-5 text-xs leading-5 text-center" style="border-top: 1px solid rgba(255, 255, 255, 0.18); color: var(--footer-muted);">@include('Website::components.footer.slot', ['slot' => 'mobile.bottom'])</div>
        @endif
    </div>
</footer>

@include('Website::components.footer.slot', ['slot' => 'overlay'])

@if(data_get($websiteFeatures ?? [], 'chat_widget', true))
    @livewire('website.chat.chat-widget', ['position' => data_get($websiteFeatures ?? [], 'chat_position', 'bottom-right')])
@endif
