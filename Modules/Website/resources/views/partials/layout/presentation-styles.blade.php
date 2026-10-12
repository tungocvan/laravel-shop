@php
    $layoutPresentation = $websiteLayoutPresentation ?? [];
    $layoutService = app(\Modules\Website\Services\WebsiteLayoutPresentationService::class);
    $layoutVariables = $layoutService->cssVariables($layoutPresentation);
    $layoutMaxWidth = $layoutService->containerMaxWidth($layoutPresentation, $websiteDesign ?? []);
    $bodyBackground = data_get($layoutPresentation, 'body.background', 'background');
    $mainBackground = data_get($layoutPresentation, 'main.background', 'transparent');
    $mainAlignment = data_get($layoutPresentation, 'main.alignment', 'center');
    $smoothScroll = (bool) data_get($layoutPresentation, 'scroll.smooth', false);
@endphp

<style>
    html { scroll-behavior: {{ $smoothScroll ? 'smooth' : 'auto' }}; }
    body { background-color: var(--website-{{ $bodyBackground }}); }
    .website-main-shell {
        {{ $layoutVariables }};
        width: 100%;
        max-width: {{ $layoutMaxWidth }};
        margin-left: {{ $mainAlignment === 'center' ? 'auto' : '0' }};
        margin-right: {{ $mainAlignment === 'center' ? 'auto' : '0' }};
        padding-top: var(--website-main-padding-top);
        padding-bottom: var(--website-main-padding-bottom);
        padding-left: var(--website-main-padding-x);
        padding-right: var(--website-main-padding-x);
        background-color: {{ $mainBackground === 'transparent' ? 'transparent' : 'var(--website-'.$mainBackground.')' }};
    }

    @if(app(\Modules\Website\Services\WebsiteTemplateService::class)->isPharma())
    /* Pharma sections own their content width; the shared Website container must not constrain them. */
    .website-main-shell {
        max-width: none;
        padding-top: 0;
        padding-right: 0;
        padding-bottom: 0;
        padding-left: 0;
    }
    @endif

    @media (max-width: 767px) {
        .website-main-shell {
            padding-top: var(--website-main-mobile-padding-top);
            padding-bottom: var(--website-main-mobile-padding-bottom);
            padding-left: var(--website-main-mobile-padding-x);
            padding-right: var(--website-main-mobile-padding-x);
        }
        @if(app(\Modules\Website\Services\WebsiteTemplateService::class)->isPharma())
        .website-main-shell { padding: 0; }
        @endif
    }
</style>
