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
    $allowed = array_merge(['hero', 'categories', 'new_arrivals'], array_keys($productSections));
    $savedOrder = $settings->get('website.pharma_homepage.order', $allowed);
    $order = is_array($savedOrder) ? array_values(array_unique(array_intersect($savedOrder, $allowed))) : $allowed;
    $order = array_merge($order, array_diff($allowed, $order));
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
    $selectedCategoryIds = array_values(array_unique(array_filter(array_map('intval', (array) ($categoryDisplay['selected_ids'] ?? [])))));
    $categoryQuery = \Modules\Category\Models\Category::query()->ofType('medicine')->active();
    if ($selectedCategoryIds) {
        $categories = $categoryQuery->whereIn('id', $selectedCategoryIds)->get()
            ->sortBy(fn ($category) => array_search((int) $category->id, $selectedCategoryIds, true))
            ->take($categoryLimit)->values();
    } else {
        $categories = $categoryQuery->sorted()->limit($categoryLimit)->get();
    }

    $hero = $settings->get('website.pharma_hero', []);
    $hero = is_array($hero) ? $hero : [];
    $heroBadge = trim((string) ($hero['badge'] ?? 'Thông tin dược phẩm')) ?: 'Thông tin dược phẩm';
    $heroTitle = trim((string) ($hero['title'] ?? 'Khám phá danh mục sản phẩm dược phẩm')) ?: 'Khám phá danh mục sản phẩm dược phẩm';
    $heroDescription = trim((string) ($hero['description'] ?? 'Tra cứu sản phẩm và nhóm dược phẩm từ danh mục được công bố, cập nhật theo hệ thống.'));
    $heroButton = trim((string) ($hero['button_text'] ?? 'Tra cứu sản phẩm')) ?: 'Tra cứu sản phẩm';
@endphp
<style>
    .pharma-public { --pharma-primary: {{ $primary }}; --pharma-accent: {{ $accent }}; background: #f7fafc; color: #142238; }
    .pharma-public .pharma-button { background: var(--pharma-primary); color: white; }
    .pharma-public .pharma-card { transition: transform .2s ease, box-shadow .2s ease; }
    .pharma-public .pharma-card:hover { transform: translateY(-3px); box-shadow: 0 16px 32px rgba(20,34,56,.09); }
    @media (max-width: 767px) { .pharma-desktop-only { display: none !important; } }
    @media (min-width: 768px) { .pharma-mobile-only { display: none !important; } }
</style>
<div class="pharma-public">
    @foreach($order as $section)
        @php
            $visibility = $layout[$section] ?? 'all';
            $visibility = in_array($visibility, ['all', 'desktop', 'mobile', 'none'], true) ? $visibility : 'all';
            $responsiveClass = $visibility === 'desktop' ? 'pharma-desktop-only' : ($visibility === 'mobile' ? 'pharma-mobile-only' : '');
        @endphp
        @if($visibility !== 'none')
            @if($section === 'hero')
                <section class="{{ $responsiveClass }} relative overflow-hidden px-5 py-16 text-white sm:px-8 md:py-24" style="background: linear-gradient(115deg, #073b48 0%, {{ $primary }} 62%, #0c6870 100%)">
                    <div class="pointer-events-none absolute -right-24 -top-40 h-96 w-96 rounded-full border border-white/10"></div>
                    <div class="pointer-events-none absolute -bottom-52 right-1/4 h-96 w-96 rounded-full border border-white/10"></div>
                    <div class="relative mx-auto grid max-w-7xl items-center gap-12 lg:grid-cols-12">
                        <div class="lg:col-span-7">
                            <p class="inline-flex rounded-full border border-white/30 bg-white/10 px-4 py-2 text-xs font-bold uppercase tracking-widest">{{ $heroBadge }}</p>
                            <h1 class="mt-7 max-w-3xl text-4xl font-extrabold leading-tight tracking-tight md:text-5xl lg:text-6xl">{{ $heroTitle }}</h1>
                            <p class="mt-6 max-w-2xl text-base leading-8 text-white/90">{{ $heroDescription }}</p>
                            <div class="mt-9 flex flex-wrap items-center gap-4">
                                <a href="{{ route('product.list') }}" class="inline-flex items-center justify-center rounded-xl bg-white px-7 py-3.5 text-sm font-bold shadow-lg transition hover:-translate-y-0.5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white" style="color: {{ $primary }}">{{ $heroButton }} →</a>
                                <span class="text-sm font-medium text-white/80">Danh mục · Hoạt chất · Thông tin sản phẩm</span>
                            </div>
                        </div>
                        <div class="hidden lg:col-span-5 lg:flex lg:justify-center" aria-hidden="true">
                            <div class="relative flex h-80 w-80 items-center justify-center rounded-[3rem] border border-white/20 bg-white/10 shadow-2xl backdrop-blur-sm">
                                <div class="absolute -left-7 top-12 h-20 w-20 rounded-2xl border border-white/20 bg-white/10"></div>
                                <div class="absolute -bottom-6 right-3 h-24 w-24 rounded-2xl border border-white/20 bg-white/10"></div>
                                <svg viewBox="0 0 240 240" fill="none" class="h-56 w-56 text-white" xmlns="http://www.w3.org/2000/svg">
                                    <rect x="51" y="31" width="138" height="178" rx="35" stroke="currentColor" stroke-width="5" opacity=".8"/>
                                    <rect x="75" y="56" width="90" height="128" rx="24" stroke="currentColor" stroke-width="2" opacity=".35"/>
                                    <path d="M120 83v74M83 120h74" stroke="currentColor" stroke-width="14" stroke-linecap="round"/>
                                    <circle cx="166" cy="72" r="7" fill="currentColor" opacity=".5"/>
                                </svg>
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
                    <div class="mt-8 grid gap-4 sm:grid-cols-2 {{ $categoryColumns === 2 ? 'lg:grid-cols-2' : ($categoryColumns === 3 ? 'lg:grid-cols-3' : 'lg:grid-cols-4') }}">
                        @forelse($categories as $category)
                            <a href="{{ route('product.list', ['category' => $category->slug]) }}" class="pharma-card group flex items-center gap-4 rounded-2xl border border-slate-200 bg-white {{ $categoryCardStyle === 'compact' ? 'min-h-24 p-5' : 'min-h-32 p-6' }} shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2" style="outline-color: {{ $primary }}">
                                @if($categoryCardStyle === 'clinical')
                                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-700" aria-hidden="true">
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="4" y="4" width="16" height="16" rx="4"/><path d="M12 8v8M8 12h8"/>
                                        </svg>
                                    </span>
                                @endif
                                <span class="min-w-0 flex-1 text-sm font-semibold leading-6 text-slate-900">{{ $category->name }}</span>
                                <svg class="h-5 w-5 shrink-0 text-emerald-700 transition-transform group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                            </a>
                        @empty
                            <p class="text-slate-600">Chưa có danh mục được công bố.</p>
                        @endforelse
                    </div>
                </section>
            @elseif(isset($productSections[$section]))
                <section class="{{ $responsiveClass }} mx-auto max-w-7xl px-5 pb-20 pt-6 sm:px-8">
                    @livewire(\\Modules\\Website\\Livewire\\Home\\PharmaProductSection::class, ['sectionKey' => $section], key('pharma-home-products-'.$section))
                </section>
            @endif
        @endif
    @endforeach
</div>
@endsection