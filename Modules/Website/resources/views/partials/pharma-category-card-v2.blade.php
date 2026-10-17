@php
    $cardPreview = $cardPreview ?? false;
    $cardTag = $cardPreview ? 'div' : 'a';
@endphp
<{{ $cardTag }}
    @unless($cardPreview) href="{{ $cardUrl }}" @endunless
    class="pharma-card pharma-category-card-custom group"
    style="{{ $cardCss }}"
    data-preset="{{ $cardDesign['preset'] }}"
    data-layout="{{ $cardDesign['layout'] }}"
    data-icon-shadow="{{ $cardDesign['icon_shadow'] ? '1' : '0' }}"
>
    @if($cardDesign['show_icon'] && $cardDesign['icon'] !== 'none')
        <span class="pharma-category-card-icon" aria-hidden="true">
            @include('Website::partials.pharma-category-icon', ['icon' => $cardDesign['icon']])
        </span>
    @endif
    <span class="pharma-category-card-content">
        <span class="pharma-category-card-name">{{ $cardName }}</span>
        @if($cardDesign['show_count'])
            <span class="pharma-category-card-count" data-count-style="{{ $cardDesign['count_style'] }}">{{ $cardCount }} sản phẩm</span>
        @endif
    </span>
    @if($cardDesign['show_arrow'])
        <svg class="pharma-category-card-arrow transition-transform group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
    @endif
</{{ $cardTag }}>
