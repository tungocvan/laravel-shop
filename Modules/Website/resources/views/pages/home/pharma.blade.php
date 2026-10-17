@extends('Website::layouts.frontend')
@section('title', 'Danh mục dược phẩm')
@section('content')
@php
    $settings = app(\Modules\System\Services\SettingsService::class);
    $theme = $settings->get('website.pharma_homepage.theme', []);
    $theme = is_array($theme) ? $theme : [];
    $primary = $theme['primary'] ?? '#047857';
    $accent = $theme['accent'] ?? '#10b981';
    $primary = preg_match('/^#[0-9a-fA-F]{6}$/', $primary) ? $primary : '#047857';
    $accent = preg_match('/^#[0-9a-fA-F]{6}$/', $accent) ? $accent : '#10b981';
    $productSections = $settings->get('website.pharma_homepage.product_sections', []);
    $productSections = is_array($productSections) ? $productSections : [];
    $productSections = array_replace(['new_arrivals' => ['eyebrow' => 'Danh mục sản phẩm', 'title' => 'Sản phẩm mới cập nhật', 'category_ids' => [], 'sort' => 'newest', 'period' => 'all', 'limit' => 8]], $productSections);
    $blogSections = $settings->get('website.pharma_homepage.blog_sections', []);
    $blogSections = is_array($blogSections) ? $blogSections : [];
    $blogSections = array_replace(['blog' => ['title' => 'Tin tức & Kiến thức dược']], $blogSections);
    $allowed = array_merge(['hero', 'categories', 'new_arrivals'], array_keys($productSections), array_keys($blogSections));
    $savedOrder = $settings->get('website.pharma_homepage.order', $allowed);
    $order = is_array($savedOrder) ? array_values(array_unique(array_intersect($savedOrder, $allowed))) : $allowed;
    $order = array_merge($order, array_diff($allowed, $order));
    if (! in_array('blog', is_array($savedOrder) ? $savedOrder : [], true) && in_array('new_arrivals', $order, true)) {
        $order = array_values(array_diff($order, ['blog']));
        $position = array_search('new_arrivals', $order, true);
        array_splice($order, $position + 1, 0, ['blog']);
    }
    $layout = $settings->get('website.pharma_homepage.layout', []);
    $layout = is_array($layout) ? $layout : [];
    $categoryDisplay = $settings->get('website.pharma_homepage.categories', []);
    $categoryDisplay = is_array($categoryDisplay) ? $categoryDisplay : [];
    $categoryEyebrow = trim((string) ($categoryDisplay['eyebrow'] ?? 'Danh mục')) ?: 'Danh mục';
    $categoryTitle = trim((string) ($categoryDisplay['title'] ?? 'Khám phá theo nhóm dược phẩm')) ?: 'Khám phá theo nhóm dược phẩm';
    $categoryDescription = trim((string) ($categoryDisplay['description'] ?? ''));
    $categoryLimit = in_array((int) ($categoryDisplay['limit'] ?? 8), [4, 8, 12, 16, 20, 24], true) ? (int) ($categoryDisplay['limit'] ?? 8) : 8;
    $categoryColumns = in_array((int) ($categoryDisplay['columns'] ?? 4), [2, 3, 4], true) ? (int) ($categoryDisplay['columns'] ?? 4) : 4;
    $categoryCardStyle = in_array($categoryDisplay['card_style'] ?? '', ['clinical', 'compact'], true) ? $categoryDisplay['card_style'] : 'clinical';
    $categoryDesignService = app(\Modules\Website\Services\PharmaCategoryCardStyle::class);
    $categoryDesignEnabled = isset($categoryDisplay['card_design']) && is_array($categoryDisplay['card_design']);
    $categoryDesign = $categoryDesignService->normalize($categoryDisplay['card_design'] ?? []);
    $selectedCategoryIds = array_values(array_unique(array_filter(array_map('intval', (array) ($categoryDisplay['selected_ids'] ?? [])))));
    $categoryQuery = \Modules\Category\Models\Category::query()->ofType('medicine')->active()
        ->withCount(['products as storefront_products_count' => fn ($query) => $query->active()->whereHas('pharmaLink')]);
    if ($selectedCategoryIds) {
        $categories = $categoryQuery->whereIn('id', $selectedCategoryIds)->get()
            ->sortBy(fn ($category) => array_search((int) $category->id, $selectedCategoryIds, true))
            ->take($categoryLimit)->values();
    } else {
        $categories = $categoryQuery->sorted()->limit($categoryLimit)->get();
    }

    $hero = app(\Modules\Website\Services\PharmaHeroService::class)->normalize($settings->get('website.pharma_hero'));

@endphp
<style>
    .pharma-public { --pharma-primary: {{ $primary }}; --pharma-accent: {{ $accent }}; background: #f7fafc; color: #142238; }
    .pharma-hero { padding-block: {{ $hero['mobile_padding'] }}px; min-height: {{ $hero['mobile_min_height'] }}px; }
    .pharma-hero-inner { width: {{ $hero['mobile_width'] }}%; max-width: 100%; }
    .pharma-hero-illustration { width: {{ $hero['mobile_illustration_size'] }}px; height: {{ $hero['mobile_illustration_size'] }}px; }
    .pharma-hero-title { font-family: {{ $hero['title_font'] }}; font-size: {{ $hero['mobile_title_size'] }}px; }
    @media (min-width: 768px) {
        .pharma-hero { padding-block: {{ $hero['desktop_padding'] }}px; min-height: {{ $hero['desktop_min_height'] }}px; }
        .pharma-hero-inner { width: 100%; max-width: {{ $hero['desktop_width'] }}px; }
        .pharma-hero-illustration { width: {{ $hero['desktop_illustration_size'] }}px; height: {{ $hero['desktop_illustration_size'] }}px; }
        .pharma-hero-title { font-size: {{ $hero['desktop_title_size'] }}px; }
    }
    .pharma-public .pharma-button { background: var(--pharma-primary); color: white; }
    .pharma-public .pharma-card { transition: transform .2s ease, box-shadow .2s ease; }
    .pharma-public .pharma-card:hover { transform: translateY(-3px); box-shadow: 0 16px 32px rgba(20,34,56,.09); }
    @media (max-width: 767px) { .pharma-desktop-only { display: none !important; } }
    @media (min-width: 768px) { .pharma-mobile-only { display: none !important; } }

    @media(max-width:639px){.pharma-category-card-custom{gap:12px;padding:min(var(--category-card-padding),16px)}.pharma-category-grid-custom[data-mobile-columns="2"] .pharma-category-card-custom{flex-wrap:wrap;align-content:start}.pharma-category-grid-custom[data-mobile-columns="2"] .pharma-category-card-content{flex-basis:100%;order:3}.pharma-category-grid-custom[data-mobile-columns="2"] .pharma-category-card-arrow{margin-left:auto}}
    @media(max-width:639px){.pharma-category-grid-custom[data-mobile-columns="2"]{grid-template-columns:repeat(2,minmax(0,1fr));}}
    @media(min-width:640px) and (max-width:1023px){.pharma-category-grid-custom[data-tablet-columns="1"]{grid-template-columns:minmax(0,1fr)}.pharma-category-grid-custom[data-tablet-columns="3"]{grid-template-columns:repeat(3,minmax(0,1fr))}}
</style>
@include('Website::partials.pharma-category-card-style')
<div class="pharma-public">
    @foreach($order as $section)
        @php
            $visibility = $layout[$section] ?? 'all';
            $visibility = in_array($visibility, ['all', 'desktop', 'mobile', 'none'], true) ? $visibility : 'all';
            $responsiveClass = $visibility === 'desktop' ? 'pharma-desktop-only' : ($visibility === 'mobile' ? 'pharma-mobile-only' : '');
        @endphp
        @if($visibility !== 'none')
            @if($section === 'hero')
                <section class="{{ $responsiveClass }} pharma-hero relative overflow-hidden px-5 sm:px-8" style="color:{{ $hero['text_color'] }};background:linear-gradient(115deg, {{ $hero['background_start'] }} 0%, {{ $primary }} 62%, {{ $hero['background_end'] }} 100%)">
                    <div class="pointer-events-none absolute -right-24 -top-40 h-96 w-96 rounded-full border border-white/10"></div>
                    <div class="pointer-events-none absolute -bottom-52 right-1/4 h-96 w-96 rounded-full border border-white/10"></div>
                    <div class="pharma-hero-inner relative mx-auto grid items-center gap-12 lg:grid-cols-12">
                        <div class="lg:col-span-7">
                            @if($hero['show_badge'] && $hero['badge'] !== '')
                            <p class="inline-flex rounded-full border border-white/30 bg-white/10 px-4 py-2 text-xs font-bold uppercase tracking-widest">{{ $hero['badge'] }}</p>
                            @endif
                            <h1 class="pharma-hero-title mt-7 max-w-3xl font-extrabold leading-tight tracking-tight">{{ $hero['title'] }}</h1>
                            @if($hero['description'] !== '')
                            <p class="mt-6 max-w-2xl text-base leading-8 opacity-90">{{ $hero['description'] }}</p>
                            @endif
                            @if($hero['show_button'] && $hero['button_text'] !== '')
                            <div class="mt-9">
                                <a href="{{ $hero['button_url'] }}" class="inline-flex items-center justify-center rounded-xl px-7 py-3.5 text-sm font-bold shadow-lg transition hover:-translate-y-0.5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white" style="background:{{ $hero['button_background'] }};color:{{ $hero['button_color'] }}">{{ $hero['button_text'] }} →</a>
                            </div>
                            @endif
                            @if($hero['show_caption'] && $hero['caption'] !== '')
                            <p class="mt-4 max-w-2xl text-sm font-medium leading-6 opacity-80">{{ $hero['caption'] }}</p>
                            @endif
                        </div>
                        <div class="{{ $hero['show_illustration'] && $hero['illustration_type'] !== 'none' ? 'hidden lg:flex' : 'hidden' }} lg:col-span-5 lg:justify-center" aria-hidden="true">
                            <div class="pharma-hero-illustration relative flex items-center justify-center rounded-[3rem] border border-white/20 bg-white/10 shadow-2xl backdrop-blur-sm">
                                <div class="absolute -left-7 top-12 h-20 w-20 rounded-2xl border border-white/20 bg-white/10"></div>
                                <div class="absolute -bottom-6 right-3 h-24 w-24 rounded-2xl border border-white/20 bg-white/10"></div>
                                @if($hero['illustration_type'] === 'image' && $hero['illustration_image'] !== '')
                                    <img src="{{ asset('storage/'.$hero['illustration_image']) }}" alt="" class="h-full w-full rounded-[3rem] object-contain p-3">
                                @else
                                <svg viewBox="0 0 240 240" fill="none" class="h-56 w-56 text-white" xmlns="http://www.w3.org/2000/svg">
                                    <rect x="51" y="31" width="138" height="178" rx="35" stroke="currentColor" stroke-width="5" opacity=".8"/>
                                    <rect x="75" y="56" width="90" height="128" rx="24" stroke="currentColor" stroke-width="2" opacity=".35"/>
                                    <path d="M120 83v74M83 120h74" stroke="currentColor" stroke-width="14" stroke-linecap="round"/>
                                    <circle cx="166" cy="72" r="7" fill="currentColor" opacity=".5"/>
                                </svg>
                                @endif
                            </div>
                        </div>
                    </div>
                </section>
            @elseif($section === 'categories')
                <section class="{{ $responsiveClass }} mx-auto max-w-7xl px-5 py-16 sm:px-8" aria-labelledby="pharma-categories-title">
                    <div class="max-w-3xl">
                        <p class="text-sm font-bold uppercase tracking-widest" style="color: {{ $primary }}">{{ $categoryEyebrow }}</p>
                        <h2 id="pharma-categories-title" class="mt-2 text-3xl font-bold tracking-tight">{{ $categoryTitle }}</h2>
                        @if($categoryDescription !== '')
                            <p class="mt-3 text-base leading-7 text-slate-600">{{ $categoryDescription }}</p>
                        @endif
                    </div>
                    <div class="mt-8 grid gap-4 sm:grid-cols-2 {{ $categoryDesignEnabled ? 'pharma-category-grid-custom' : '' }} {{ $categoryColumns === 2 ? 'lg:grid-cols-2' : ($categoryColumns === 3 ? 'lg:grid-cols-3' : 'lg:grid-cols-4') }}" @if($categoryDesignEnabled) data-mobile-columns="{{ $categoryDesign['mobile_columns'] }}" data-tablet-columns="{{ $categoryDesign['tablet_columns'] }}" @endif>
                        @forelse($categories as $category)
                            @if($categoryDesignEnabled)
                                @include('Website::partials.pharma-category-card-v2', [
                                    'cardName' => $category->name,
                                    'cardCount' => $category->storefront_products_count,
                                    'cardUrl' => route('product.list', ['category' => $category->slug]),
                                    'cardDesign' => $categoryDesign,
                                    'cardIcon' => $categoryDesign['icons_by_category'][$category->id] ?? $categoryDesign['icon'],
                                    'cardCss' => $categoryDesignService->css($categoryDesign),
                                    'cardPreview' => false,
                                ])
                            @else
                            <a href="{{ route('product.list', ['category' => $category->slug]) }}" class="pharma-card group flex items-center gap-4 rounded-2xl border border-slate-200 bg-white {{ $categoryDesignEnabled ? 'pharma-category-card-custom' : '' }} {{ $categoryCardStyle === 'compact' ? 'min-h-24 p-5' : 'min-h-32 p-6' }} shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2" style="outline-color: {{ $primary }};{{ $categoryDesignEnabled ? $categoryDesignService->css($categoryDesign) : '' }}" @if($categoryDesignEnabled) data-preset="{{ $categoryDesign['preset'] }}" @endif>
                                @if($categoryDesignEnabled ? ($categoryDesign['show_icon'] && $categoryDesign['icon'] !== 'none') : $categoryCardStyle === 'clinical')
                                    <span class="pharma-category-card-icon flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-700" aria-hidden="true">
                                        @include('Website::partials.pharma-category-icon', ['icon' => $categoryDesignEnabled ? $categoryDesign['icon'] : 'medical'])
                                    </span>
                                @endif
                                <span class="pharma-category-card-name min-w-0 flex-1 text-sm font-semibold leading-6 text-slate-900">{{ $category->name }}</span>
                                @if(!$categoryDesignEnabled || $categoryDesign['show_count'])
                                <span class="pharma-category-card-count shrink-0 rounded-full border border-emerald-100 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800" aria-label="{{ $category->storefront_products_count }} sản phẩm">{{ $category->storefront_products_count }} SP</span>
                                @endif
                                @if(!$categoryDesignEnabled || $categoryDesign['show_arrow'])
                                <svg class="pharma-category-card-arrow h-5 w-5 shrink-0 text-emerald-700 transition-transform group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                                @endif
                            </a>
                            @endif
                        @empty
                            <p class="text-slate-600">Chưa có danh mục được công bố.</p>
                        @endforelse
                    </div>
                </section>
            @elseif(isset($blogSections[$section]))
                <section class="{{ $responsiveClass }} mx-auto max-w-7xl px-5 pb-20 pt-6 sm:px-8">
                    @livewire(\Modules\Website\Livewire\Home\PharmaBlogSection::class, ['sectionKey' => $section], key('pharma-home-blog-'.$section))
                </section>
            @elseif(isset($productSections[$section]))
                <section class="{{ $responsiveClass }} mx-auto max-w-7xl px-5 pb-20 pt-6 sm:px-8">
                    @livewire(\Modules\Website\Livewire\Home\PharmaProductSection::class, ['sectionKey' => $section], key('pharma-home-products-'.$section))
                </section>
            @endif
        @endif
    @endforeach
</div>
@endsection