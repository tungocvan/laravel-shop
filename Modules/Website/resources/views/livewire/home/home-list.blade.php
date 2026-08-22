<div class="{{ $homepageContainerClass }}" style="{{ $homepageStyle }}; padding-left: var(--homepage-page-padding); padding-right: var(--homepage-page-padding); padding-top: 2rem; padding-bottom: 2rem;">
    <div class="homepage-section-stack" style="display: flex; flex-direction: column; gap: var(--homepage-section-gap);">
        @foreach ($sectionOrder as $sectionKey)
            @php
                $visibilityKey = 'show_' . $sectionKey;
                $visibilityClass = $this->getVisibilityClass($visibilityKey);
                $render = $sectionRenderers[$sectionKey] ?? null;
            @endphp

            @if ($visibilityClass !== 'hidden' && is_array($render) && filled($render['renderer'] ?? null))
                <section class="{{ $visibilityClass }}" wire:key="homepage-section-{{ $sectionKey }}">
                    @livewire($render['renderer'], $render['params'] ?? [], key('home-'.$sectionKey))
                </section>
            @endif
        @endforeach
    </div>

    <style>
        @media (max-width: 767px) {
            .homepage-section-stack { gap: var(--homepage-mobile-section-gap) !important; }
        }
    </style>
</div>
