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
    $allowed = ['hero', 'categories', 'new_arrivals'];
    $savedOrder = $settings->get('website.pharma_homepage.order', $allowed);
    $order = is_array($savedOrder) ? array_values(array_unique(array_intersect($savedOrder, $allowed))) : $allowed;
    $order = array_merge($order, array_diff($allowed, $order));
    $layout = $settings->get('website.pharma_homepage.layout', []);
    $layout = is_array($layout) ? $layout : [];
    $categories = \Modules\Category\Models\Category::query()->ofType('medicine')->active()->sorted()->limit(8)->get();
    $products = \Modules\Product\Models\Product::query()->active()->whereHas('pharmaLink')->with('categories')->latest('id')->limit(8)->get();
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
                <section class="{{ $responsiveClass }} px-5 py-16 text-white md:py-24" style="background: linear-gradient(115deg, {{ $primary }}, #083344)">
                    <div class="mx-auto max-w-6xl">
                        <p class="mb-4 text-sm font-bold uppercase tracking-widest">Thông tin dược phẩm</p>
                        <h1 class="max-w-3xl text-4xl font-extrabold leading-tight md:text-6xl">Khám phá danh mục sản phẩm dược phẩm</h1>
                        <p class="mt-6 max-w-2xl text-base leading-8 text-white/90">Tra cứu sản phẩm và nhóm dược phẩm từ danh mục được công bố, cập nhật theo hệ thống.</p>
                        <a href="{{ route('product.list') }}" class="mt-8 inline-flex rounded-xl bg-white px-6 py-3 font-bold" style="color: {{ $primary }}">Tra cứu sản phẩm →</a>
                    </div>
                </section>
            @elseif($section === 'categories')
                <section class="{{ $responsiveClass }} mx-auto max-w-6xl px-5 py-16">
                    <p class="text-sm font-bold uppercase tracking-widest" style="color: {{ $primary }}">Danh mục</p>
                    <h2 class="mt-2 text-3xl font-bold">Khám phá theo nhóm dược phẩm</h2>
                    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @forelse($categories as $category)
                            <a href="{{ route('product.list', ['category' => $category->slug]) }}" class="pharma-card flex min-h-28 items-center justify-between rounded-2xl border border-slate-200 bg-white p-6 font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2">
                                <span>{{ $category->name }}</span><span aria-hidden="true" style="color: {{ $primary }}">↗</span>
                            </a>
                        @empty
                            <p class="text-slate-600">Chưa có danh mục được công bố.</p>
                        @endforelse
                    </div>
                </section>
            @elseif($section === 'new_arrivals')
                <section class="{{ $responsiveClass }} mx-auto max-w-6xl px-5 pb-20 pt-6">
                    <p class="text-sm font-bold uppercase tracking-widest" style="color: {{ $primary }}">Danh mục sản phẩm</p>
                    <h2 class="mt-2 text-3xl font-bold">Sản phẩm mới cập nhật</h2>
                    <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        @forelse($products as $product)
                            <a href="{{ route('product.detail', $product->slug) }}" class="pharma-card overflow-hidden rounded-2xl border border-slate-200 bg-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2">
                                <div class="flex aspect-[4/3] items-center justify-center bg-emerald-50">
                                    @if($product->image)
                                        <img src="{{ $product->image_url }}" alt="{{ $product->title }}" class="h-full w-full object-contain" loading="lazy">
                                    @else
                                        <span class="text-sm text-slate-500">Chưa có ảnh sản phẩm</span>
                                    @endif
                                </div>
                                <div class="p-5">
                                    <p class="text-xs font-semibold uppercase" style="color: {{ $primary }}">{{ $product->categories->first()?->name ?? 'Dược phẩm' }}</p>
                                    <h3 class="mt-2 min-h-14 font-bold leading-6">{{ $product->title }}</h3>
                                    <span class="mt-3 inline-block text-sm font-semibold" style="color: {{ $primary }}">Xem thông tin →</span>
                                </div>
                            </a>
                        @empty
                            <p class="text-slate-600">Chưa có sản phẩm được công bố.</p>
                        @endforelse
                    </div>
                </section>
            @endif
        @endif
    @endforeach
</div>
@endsection