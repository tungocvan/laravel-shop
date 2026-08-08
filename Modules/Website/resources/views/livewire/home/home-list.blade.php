<div>
    {{-- 1. Hero Banner --}}
    @php $heroClass = $this->getVisibilityClass('show_hero'); @endphp
    @if($heroClass !== 'hidden')
        <div class="container mx-auto px-4 mt-4 {{ $heroClass }}">
            {{-- Hero tự query Banner theo position='hero' --}}
            @livewire('website.home.hero-banner')
        </div>
    @endif

    {{-- Container chính --}}
    <div class="container mx-auto px-4 py-8 space-y-12">

        {{-- 2. Danh mục nổi bật --}}
        @php $catClass = $this->getVisibilityClass('show_categories'); @endphp
        @if($catClass !== 'hidden')
            <div class="{{ $catClass }}">
                {{-- Truyền mảng ID danh mục đã chọn xuống con --}}
                @livewire('website.home.category-highlight', [
                    'categoryIds' => $settings['category_ids'] ?? []
                ])
            </div>
        @endif

        {{-- 3. Flash Sale --}}
        @php $flashClass = $this->getVisibilityClass('show_flash_sale'); @endphp
        @if($flashClass !== 'hidden')
            <div class="{{ $flashClass }}">
                @livewire('website.home.flash-sale', ['lazy' => true])
            </div>
        @endif

        {{-- 4. Promo Banner --}}
        @php $promoClass = $this->getVisibilityClass('show_promo_banner'); @endphp
        @if($promoClass !== 'hidden')
            <div class="{{ $promoClass }}">
                @livewire('website.home.promo-banner', ['lazy' => true])
            </div>
        @endif

        {{-- 5. Sản phẩm nổi bật --}}
        @php $featuredClass = $this->getVisibilityClass('show_featured'); @endphp
        @if($featuredClass !== 'hidden')
            <div class="{{ $featuredClass }}">
                {{-- Truyền mảng ID sản phẩm đã ghim xuống con --}}
                @livewire('website.home.featured-products', [
                    'lazy' => true,
                    'productIds' => $settings['featured_ids'] ?? []
                ])
            </div>
        @endif

        {{-- 6. Hàng mới về --}}
        @php $newClass = $this->getVisibilityClass('show_new_arrivals'); @endphp
        @if($newClass !== 'hidden')
            <div class="{{ $newClass }}">
                @livewire('website.home.new-arrivals', ['lazy' => true])
            </div>
        @endif

        {{-- 7. Top bán chạy --}}
        @php $showClass = $this->getVisibilityClass('show_best_sellers'); @endphp
        @if($showClass !== 'hidden')
            <div class="{{ $showClass }}">
                @livewire('website.home.best-sellers', ['lazy' => true])
            </div>
        @endif

        {{-- 8. Trust Badges --}}
        @php $trustClass = $this->getVisibilityClass('show_trust_badges'); @endphp
        @if($trustClass !== 'hidden')
            <div class="hidden md:block {{ $trustClass }}">
                {{-- Truyền mảng cấu hình Trust Badges xuống con --}}
                @livewire('website.home.trust-badges', [
                    'lazy' => true,
                    'badges' => $settings['trust_badges'] ?? []
                ])
            </div>
        @endif

        {{-- 9. Blog --}}
        @php $blogClass = $this->getVisibilityClass('show_blog_highlight'); @endphp
        @if($blogClass !== 'hidden')
            <div class="{{ $blogClass }}">
                @livewire('website.home.blog-highlight', ['lazy' => true])
            </div>
        @endif

        {{-- 10. Newsletter --}}
        {{-- Luôn hiển thị hoặc kiểm tra config show_newsletter --}}

        @php $newsletterClass = $this->getVisibilityClass('show_newsletter'); @endphp
        @if($newsletterClass !== 'hidden')
            <div class="{{ $blogClass }}">
                @livewire('website.home.newsletter-signup', ['lazy' => true])
            </div>
        @endif

    </div>
</div>
