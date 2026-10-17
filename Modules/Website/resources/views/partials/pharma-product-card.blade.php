@php
    $cardStyle = app(\Modules\Website\Services\PharmaProductCardStyle::class)->normalize($cardDesign ?? []);
    $cardPreview = $cardPreview ?? false;
    $cardTitle = $cardPreview ? ($cardName ?? 'Anvo-Telmisartan HCTZ 80/12,5mg') : $product->title;
    $cardCategory = $cardPreview ? 'Dược phẩm' : ($product->categories->first()?->name ?? 'Dược phẩm');
    $cardImage = $cardPreview ? null : ($product->image ? $product->image_url : null);
    $fallbackImage = $cardStyle['fallback_image_url'];
    $displayImage = $cardImage ?: $fallbackImage;
    $cardUrl = $cardPreview ? null : route('product.detail', $product->slug);
    $cardShowPrice = !$cardPreview && ($cardShowPrice ?? false);
    $ratio = ['1/1' => '1 / 1', '4/3' => '4 / 3', '3/4' => '3 / 4'][$cardStyle['image_ratio']];
@endphp
<a @if($cardUrl) href="{{ $cardUrl }}" @endif
   class="pharma-product-card group flex h-full flex-col overflow-hidden border transition duration-200 hover:-translate-y-0.5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
   style="background:{{ $cardStyle['background'] }};border-color:{{ $cardStyle['border_color'] }};border-radius:{{ $cardStyle['radius'] }}px;color:{{ $cardStyle['text_color'] }};{{ $cardStyle['shadow'] ? 'box-shadow:0 8px 24px rgba(15,23,42,.10)' : '' }}">
    <div class="flex w-full items-center justify-center overflow-hidden" style="aspect-ratio:{{ $ratio }};background:{{ $cardStyle['image_background'] }}">
        <img @if(!$displayImage) hidden @endif src="{{ $displayImage ?: '' }}" alt="{{ $cardTitle }}"
             class="h-full w-full" style="object-fit:{{ $cardStyle['image_fit'] }}"
             loading="lazy"
             data-fallback-image="{{ $fallbackImage }}"
             onerror="if(!this.dataset.fallbackAttempted && this.dataset.fallbackImage && this.src !== new URL(this.dataset.fallbackImage, document.baseURI).href){this.dataset.fallbackAttempted='1';this.src=this.dataset.fallbackImage;}else{this.hidden=true;this.nextElementSibling.hidden=false;}">
        <div @if($displayImage) hidden @endif class="flex flex-col items-center gap-3 text-center" style="color:{{ $cardStyle['muted_color'] }}">
            <span class="flex h-16 w-16 items-center justify-center rounded-2xl border border-teal-200 bg-white text-4xl font-light" aria-hidden="true">+</span>
            <span class="text-xs font-medium">Chưa có ảnh sản phẩm</span>
        </div>
    </div>
    <div class="flex flex-1 flex-col" style="padding:{{ $cardStyle['padding'] }}px">
        @if($cardStyle['show_category'])
            <p class="text-xs font-semibold uppercase" style="color:{{ $cardStyle['muted_color'] }}">{{ $cardCategory }}</p>
        @endif
        <h3 class="mt-2 font-bold leading-snug" style="font-size:{{ $cardStyle['title_size'] }}px;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:{{ $cardStyle['title_lines'] }};overflow:hidden">{{ $cardTitle }}</h3>
        @if($cardShowPrice)
            <p class="mt-3 text-lg font-bold" style="color:{{ $cardStyle['text_color'] }}">{{ number_format((float) ($product->sale_price > 0 ? $product->sale_price : $product->regular_price), 0, ',', '.') }}đ</p>
        @endif
        @if($cardStyle['show_action'])
            <span class="mt-auto inline-block pt-4 text-sm font-semibold" style="color:{{ $cardStyle['action_color'] }}">{{ $cardStyle['action_label'] }} →</span>
        @endif
    </div>
</a>
