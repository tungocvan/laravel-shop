@php
    $cardPreview = $cardPreview ?? false;
    $cardTag = $cardPreview ? 'div' : 'a';
    $cardIcon = $cardIcon ?? $cardDesign['icon'];
    $cardLayout = $cardDesign['preset'] === 'premium' ? 'stacked' : $cardDesign['layout'];
@endphp
<{{ $cardTag }}
    @unless($cardPreview) href="{{ $cardUrl }}" @endunless
    class="pharma-category-card-custom group"
    style="{{ $cardCss }}"
    data-preset="{{ $cardDesign['preset'] }}"
    data-layout="{{ $cardLayout }}"
    data-icon-shadow="{{ $cardDesign['icon_shadow'] ? '1' : '0' }}"
>
    <span class="pharma-category-card-top">
        @if($cardDesign['show_icon'] && $cardIcon !== 'none')
            <span class="pharma-category-card-icon" aria-hidden="true">
                @include('Website::partials.pharma-category-icon', ['icon' => $cardIcon])
            </span>
        @endif
        @if($cardDesign['show_count'] && $cardDesign['count_style'] === 'badge')
            <span class="pharma-category-card-count" data-count-style="badge">{{ $cardCount }} SP</span>
        @endif
    </span>
    <span class="pharma-category-card-bottom">
        <span class="pharma-category-card-content">
            <span class="pharma-category-card-name">{{ $cardName }}</span>
            @if($cardDesign['show_count'] && $cardDesign['count_style'] === 'subtitle')
                <span class="pharma-category-card-count" data-count-style="subtitle">{{ $cardCount }} sản phẩm</span>
            @endif
        </span>
        @if($cardDesign['show_arrow'])
            <svg class="pharma-category-card-arrow transition-transform group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
        @endif
    </span>
</{{ $cardTag }}>
