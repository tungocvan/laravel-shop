<div class="container mx-auto px-4 py-8 space-y-12">
    @foreach ($sectionOrder as $sectionKey)
        @php
            $visibilityKey = 'show_' . $sectionKey;
            $visibilityClass = $this->getVisibilityClass($visibilityKey);
        @endphp

        @if ($visibilityClass !== 'hidden')
            <section class="{{ $visibilityClass }}" wire:key="homepage-section-{{ $sectionKey }}">
                @php $sectionType = $sectionTypes[$sectionKey] ?? $sectionKey; @endphp
                @switch($sectionType)
                    @case('hero')
                        @livewire('website.home.hero-banner', key('home-'.$sectionKey))
                    @break

                    @case('categories')
                    @case('category_grid')
                        @livewire('website.home.category-highlight', ['categoryIds' => $categoryIds], key('home-'.$sectionKey))
                    @break

                    @case('flash_sale')
                        @livewire('website.home.flash-sale', ['lazy' => true], key('home-'.$sectionKey))
                    @break

                    @case('product_grid')
                        @if (str_starts_with($sectionKey, 'new_arrivals'))
                            @livewire('website.home.new-arrivals', ['lazy' => true], key('home-'.$sectionKey))
                        @elseif (str_starts_with($sectionKey, 'best_sellers'))
                            @livewire('website.home.best-sellers', ['lazy' => true], key('home-'.$sectionKey))
                        @else
                            @livewire('website.home.featured-products', ['lazy' => true], key('home-'.$sectionKey))
                        @endif
                    @break

                    @case('featured')
                        @livewire('website.home.featured-products', ['lazy' => true], key('home-'.$sectionKey))
                    @break

                    @case('new_arrivals')
                        @livewire('website.home.new-arrivals', ['lazy' => true], key('home-'.$sectionKey))
                    @break

                    @case('best_sellers')
                        @livewire('website.home.best-sellers', ['lazy' => true], key('home-'.$sectionKey))
                    @break

                    @case('blog_highlight')
                    @case('post_grid')
                        @livewire('website.home.blog-highlight', ['lazy' => true], key('home-'.$sectionKey))
                    @break

                    @case('promo_banner')
                        @livewire('website.home.promo-banner', ['lazy' => true], key('home-'.$sectionKey))
                    @break

                    @case('trust_badges')
                        <div class="hidden md:block">
                            @livewire('website.home.trust-badges', ['lazy' => true], key('home-'.$sectionKey))
                        </div>
                    @break

                    @case('newsletter')
                        @livewire('website.home.newsletter-signup', ['lazy' => true], key('home-'.$sectionKey))
                    @break
                @endswitch
            </section>
        @endif
    @endforeach
</div>
