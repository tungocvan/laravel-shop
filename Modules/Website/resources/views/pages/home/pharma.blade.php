@php
    $pharmaHome = app(\Modules\Website\Services\PharmaHomepageService::class);
    $sections = $pharmaHome->sections();
    $heroContent = array_merge([
        'eyebrow' => 'Thông tin dược phẩm',
        'title' => 'Khám phá danh mục sản phẩm dược phẩm',
        'description' => 'Tìm kiếm và tham khảo thông tin sản phẩm theo danh mục chuyên môn, với dữ liệu được cập nhật từ hệ thống.',
        'button' => 'Tra cứu sản phẩm',
        'image' => '',
    ], (array) app(\Modules\System\Services\SettingsService::class)->get('website.pharma.hero', []));
@endphp
<div class="w-full min-w-0 bg-slate-50 text-slate-900">
    @foreach($sections as $section)
        <section class="{{ $section['visibility'] }}" aria-label="{{ match($section['key']) { 'hero' => 'Giới thiệu', 'categories' => 'Danh mục dược phẩm', 'featured' => 'Sản phẩm nổi bật', default => 'Sản phẩm mới' } }}">
            @if($section['key'] === 'hero')
                <div class="relative isolate overflow-hidden bg-gradient-to-br from-emerald-950 via-emerald-900 to-teal-800">
                    <div aria-hidden="true" class="pointer-events-none absolute -right-20 -top-40 h-96 w-96 rounded-full border border-white/10 bg-white/5 blur-3xl"></div>
                    <div aria-hidden="true" class="pointer-events-none absolute -bottom-40 left-1/3 h-96 w-96 rounded-full bg-teal-400/10 blur-3xl"></div>
                    <div class="relative mx-auto grid w-full max-w-[1440px] items-center gap-10 px-5 py-16 sm:px-8 md:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)] md:py-24 lg:gap-16 lg:px-12 lg:py-28">
                        <div class="min-w-0">
                            <p class="inline-flex items-center gap-2 rounded-full border border-emerald-300/30 bg-white/10 px-4 py-2 text-xs font-bold uppercase tracking-[0.15em] text-emerald-100">
                                <span aria-hidden="true" class="h-2 w-2 rounded-full bg-emerald-300"></span> {{ $heroContent['eyebrow'] }}
                            </p>
                            <h1 class="mt-7 max-w-3xl text-4xl font-extrabold leading-[1.15] tracking-tight text-white sm:text-5xl lg:text-6xl">{{ $heroContent['title'] }}</h1>
                            <p class="mt-6 max-w-xl text-base leading-8 text-emerald-50/90 sm:text-lg">{{ $heroContent['description'] }}</p>
                            <div class="mt-9 flex flex-wrap items-center gap-4">
                                <a href="{{ route('product.list') }}" class="inline-flex min-h-12 items-center justify-center gap-3 rounded-xl bg-white px-6 py-3 text-sm font-bold text-emerald-950 shadow-lg transition hover:bg-emerald-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white">{{ $heroContent['button'] }} <span aria-hidden="true">→</span></a>
                                <span class="text-sm font-medium text-emerald-100">Danh mục · Thông tin · Tra cứu</span>
                            </div>
                        </div>
                        <div aria-hidden="true" class="relative mx-auto hidden aspect-square w-full max-w-[440px] items-center justify-center md:flex">
                            @if(!empty($heroContent['image']))
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($heroContent['image']) }}" alt="" class="relative z-10 h-full w-full rounded-[3rem] object-contain">
                            @else
                            <div class="absolute inset-[7%] rounded-[3rem] border border-white/15 bg-white/10 shadow-2xl backdrop-blur-sm"></div>
                            <div class="absolute inset-[20%] rotate-12 rounded-[2.5rem] border border-emerald-200/20"></div>
                            <svg class="relative h-48 w-48 text-emerald-100 lg:h-56 lg:w-56" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="38" y="18" width="124" height="164" rx="28" stroke="currentColor" stroke-width="5" opacity=".8"/>
                                <path d="M100 57V143M57 100H143" stroke="currentColor" stroke-width="15" stroke-linecap="round"/>
                                <circle cx="151" cy="43" r="7" fill="currentColor" opacity=".5"/>
                                <circle cx="48" cy="157" r="5" fill="currentColor" opacity=".5"/>
                            </svg>
                            @endif
                        </div>
                    </div>
                </div>
            @elseif($section['key'] === 'categories')
                @php($categories = $pharmaHome->categories())
                @if($categories->isNotEmpty())
                    <div class="mx-auto w-full max-w-[1440px] px-5 py-16 sm:px-8 lg:px-12 lg:py-20">
                        <div class="mb-7 flex flex-wrap items-end justify-between gap-3">
                            <div><p class="text-xs font-bold uppercase tracking-widest text-emerald-700">Danh mục</p><h2 class="mt-3 text-3xl font-extrabold tracking-tight md:text-4xl">Khám phá theo nhóm dược phẩm</h2></div>
                            <a href="{{ route('product.list') }}" class="text-sm font-bold text-emerald-700 hover:underline">Tất cả sản phẩm →</a>
                        </div>
                        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach($categories as $category)
                                <a href="{{ route('product.list', ['category' => $category->slug]) }}" class="group flex min-h-32 items-center gap-4 rounded-2xl border border-slate-200 bg-white px-5 py-5 shadow-sm transition duration-200 hover:-translate-y-1 hover:border-emerald-300 hover:shadow-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">
                                    <span aria-hidden="true" class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100 transition group-hover:bg-emerald-100"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="5"/><path d="M12 7v10M7 12h10"/></svg></span><span class="min-w-0 flex-1 text-base font-bold leading-6 text-slate-800">{{ $category->name }}</span><span class="text-emerald-700 transition group-hover:translate-x-1" aria-hidden="true">↗</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @elseif(in_array($section['key'], ['featured', 'new_arrivals'], true))
                @php($products = $pharmaHome->products($section['key']))
                @if($products->isNotEmpty())
                    <div class="mx-auto w-full max-w-[1440px] px-5 py-16 sm:px-8 lg:px-12 lg:py-20">
                        <div class="mb-7 flex flex-wrap items-end justify-between gap-3">
                            <div><p class="text-xs font-bold uppercase tracking-widest text-emerald-700">Danh mục sản phẩm</p><h2 class="mt-3 text-3xl font-extrabold tracking-tight md:text-4xl">{{ $section['key'] === 'featured' ? 'Sản phẩm nổi bật' : 'Sản phẩm mới cập nhật' }}</h2></div>
                            <a href="{{ route('product.list') }}" class="text-sm font-bold text-emerald-700 hover:underline">Xem tất cả →</a>
                        </div>
                        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach($products as $product)
                                <article class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:border-emerald-200 hover:shadow-lg">
                                    <a href="{{ route('product.detail', $product->slug) }}" class="flex h-full flex-col focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-emerald-700">
                                        <div class="flex aspect-[5/3] items-center justify-center border-b border-emerald-50 bg-gradient-to-br from-slate-50 to-emerald-50 p-4">
                                            @if($product->image)
                                                <img src="{{ $product->image_url }}" alt="{{ $product->title }}" loading="lazy" class="h-full w-full object-contain">
                                            @else
                                                <div class="flex flex-col items-center text-center"><span aria-hidden="true" class="flex h-16 w-16 items-center justify-center rounded-2xl border border-emerald-100 bg-white/70 text-emerald-400 shadow-sm"><svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="5"/><path d="M12 7v10M7 12h10"/></svg></span><p class="mt-3 text-xs font-medium text-slate-500">Chưa có ảnh sản phẩm</p></div>
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
