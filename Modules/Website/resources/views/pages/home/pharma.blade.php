@php
    $pharmaHome = app(\Modules\Website\Services\PharmaHomepageService::class);
    $sections = $pharmaHome->sections();
@endphp
<div class="bg-slate-50 text-slate-900">
    @foreach($sections as $section)
        <section class="{{ $section['visibility'] }}" aria-label="{{ match($section['key']) { 'hero' => 'Giới thiệu', 'categories' => 'Danh mục dược phẩm', 'featured' => 'Sản phẩm nổi bật', default => 'Sản phẩm mới' } }}">
            @if($section['key'] === 'hero')
                <div class="bg-gradient-to-r from-emerald-950 via-emerald-900 to-teal-800">
                    <div class="mx-auto grid max-w-[1440px] items-center gap-8 px-4 py-16 sm:px-6 md:grid-cols-[1.4fr_1fr] md:py-24 lg:px-8">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-200">Thông tin dược phẩm</p>
                            <h1 class="mt-4 max-w-3xl text-4xl font-extrabold leading-tight tracking-tight text-white md:text-5xl">Khám phá danh mục sản phẩm dược phẩm</h1>
                            <p class="mt-5 max-w-xl text-base leading-8 text-emerald-50">Tìm kiếm và tham khảo thông tin sản phẩm theo danh mục chuyên môn.</p>
                            <a href="{{ route('product.list') }}" class="mt-8 inline-flex min-h-12 items-center justify-center rounded-xl bg-white px-6 py-3 text-sm font-bold text-emerald-900 transition hover:bg-emerald-50">Tra cứu sản phẩm <span class="ml-2" aria-hidden="true">→</span></a>
                        </div>
                        <div aria-hidden="true" class="hidden justify-center md:flex">
                            <div class="flex h-64 w-64 items-center justify-center rounded-[3rem] border border-white/20 bg-white/10 text-[9rem] font-light text-emerald-200 shadow-2xl">+</div>
                        </div>
                    </div>
                </div>
            @elseif($section['key'] === 'categories')
                @php($categories = $pharmaHome->categories())
                @if($categories->isNotEmpty())
                    <div class="mx-auto max-w-[1440px] px-4 py-14 sm:px-6 lg:px-8">
                        <div class="mb-7 flex flex-wrap items-end justify-between gap-3">
                            <div><p class="text-xs font-bold uppercase tracking-widest text-emerald-700">Danh mục</p><h2 class="mt-2 text-2xl font-extrabold md:text-3xl">Khám phá theo nhóm dược phẩm</h2></div>
                            <a href="{{ route('product.list') }}" class="text-sm font-bold text-emerald-700 hover:underline">Tất cả sản phẩm →</a>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach($categories as $category)
                                <a href="{{ route('product.list', ['category' => $category->slug]) }}" class="flex min-h-24 items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm transition hover:border-emerald-300 hover:shadow-md">
                                    <span class="text-base font-bold text-slate-800">{{ $category->name }}</span><span class="text-emerald-700" aria-hidden="true">↗</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @elseif(in_array($section['key'], ['featured', 'new_arrivals'], true))
                @php($products = $pharmaHome->products($section['key']))
                @if($products->isNotEmpty())
                    <div class="mx-auto max-w-[1440px] px-4 py-14 sm:px-6 lg:px-8">
                        <div class="mb-7 flex flex-wrap items-end justify-between gap-3">
                            <div><p class="text-xs font-bold uppercase tracking-widest text-emerald-700">Danh mục sản phẩm</p><h2 class="mt-2 text-2xl font-extrabold md:text-3xl">{{ $section['key'] === 'featured' ? 'Sản phẩm nổi bật' : 'Sản phẩm mới cập nhật' }}</h2></div>
                            <a href="{{ route('product.list') }}" class="text-sm font-bold text-emerald-700 hover:underline">Xem tất cả →</a>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach($products as $product)
                                <article class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:border-emerald-200 hover:shadow-lg">
                                    <a href="{{ route('product.detail', $product->slug) }}" class="flex h-full flex-col">
                                        <div class="flex aspect-[5/3] items-center justify-center bg-gradient-to-br from-slate-50 to-emerald-50 p-4">
                                            @if($product->image)
                                                <img src="{{ $product->image_url }}" alt="{{ $product->title }}" loading="lazy" class="h-full w-full object-contain">
                                            @else
                                                <div class="text-center text-emerald-300"><span aria-hidden="true" class="text-5xl font-light">+</span><p class="mt-2 text-xs text-slate-400">Chưa có ảnh sản phẩm</p></div>
                                            @endif
                                        </div>
                                        <div class="flex flex-1 flex-col p-5">
                                            <p class="text-xs font-bold uppercase tracking-wide text-emerald-700">{{ $product->categories->firstWhere('type', 'medicine')?->name ?? 'Dược phẩm' }}</p>
                                            <h3 class="mt-2 line-clamp-2 text-base font-extrabold leading-6">{{ $product->title }}</h3>
                                            <span class="mt-auto pt-5 text-sm font-bold text-emerald-700">Xem thông tin →</span>
                                        </div>
                                    </a>
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif
        </section>
    @endforeach
</div>
