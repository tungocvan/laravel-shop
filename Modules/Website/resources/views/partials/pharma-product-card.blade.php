@php
    $cardStyle = app(\Modules\Website\Services\PharmaProductCardStyle::class)->normalize($cardDesign ?? []);
    $cardTitle = $cardPreview ? ($cardName ?? 'Anvo-Telmisartan HCTZ 80/12,5mg') : $product->title;
    $cardCategory = $cardPreview ? 'Dược phẩm' : ($product->categories->first()?->name ?? 'Dược phẩm');
    $cardImage = $cardPreview ? null : ($product->image ? $product->image_url : null);
    $cardUrl = $cardPreview ? null : route('product.detail', $product->slug);
    $ratio = ['1/1' => '1 / 1', '4/3' => '4 / 3', '3/4' => '3 / 4'][$cardStyle['image_ratio']];
@endphp
<a @if($cardUrl) href="{{ $cardUrl }}" @endif
   class="pharma-product-card group flex h-full flex-col overflow-hidden border transition duration-200 hover:-translate-y-0.5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
   style="background:{{ $cardStyle['background'] }};border-color:{{ $cardStyle['border_color'] }};border-radius:{{ $cardStyle['radius'] }}px;color:{{ $cardStyle['text_color'] }};{{ $cardStyle['shadow'] ? 'box-shadow:0 8px 24px rgba(15,23,42,.10)' : '' }}">
    <div class="flex w-full items-center justify-center overflow-hidden" style="aspect-ratio:{{ $ratio }};background:{{ $cardStyle['image_background'] }}">
        @if($cardImage)
            <img src="{{ $cardImage }}" alt="{{ $cardTitle }}" class="h-full w-full" style="object-fit:{{ $cardStyle['image_fit'] }}" loading="lazy">
        @else
            <div class="flex flex-col items-center gap-3 text-center" style="color:{{ $cardStyle['muted_color'] }}">
                <span class="flex h-16 w-16 items-center justify-center rounded-2xl border border-teal-200 bg-white text-4xl font-light" aria-hidden="true">+</span>
                <span class="text-xs font-medium">Chưa có ảnh sản phẩm</span>
            </div>
        @endif
    </div>
    <div class="flex flex-1 flex-col" style="padding:{{ $cardStyle['padding'] }}px">
        @if($cardStyle['show_category'])
            <p class="text-xs font-semibold uppercase" style="color:{{ $cardStyle['muted_color'] }}">{{ $cardCategory }}</p>
        @endif
        <h3 class="mt-2 font-bold leading-snug" style="font-size:{{ $cardStyle['title_size'] }}px;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:{{ $cardStyle['title_lines'] }};overflow:hidden">{{ $cardTitle }}</h3>
        @if($cardStyle['show_action'])
            <span class="mt-auto inline-block pt-4 text-sm font-semibold" style="color:{{ $cardStyle['action_color'] }}">{{ $cardStyle['action_label'] }} →</span>
        @endif
    </div>
</a>
