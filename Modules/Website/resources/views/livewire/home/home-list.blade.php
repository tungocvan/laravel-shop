<div class="container mx-auto px-4 py-8 space-y-12">
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
