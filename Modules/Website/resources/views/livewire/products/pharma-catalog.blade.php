<div class="min-h-screen bg-slate-50 text-slate-900">
    <section class="border-b border-emerald-100 bg-gradient-to-r from-emerald-50 via-cyan-50 to-white">
        <div class="mx-auto flex max-w-[1440px] flex-col gap-5 px-4 py-10 sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8">
            <div>
                <p class="mb-2 text-xs font-bold uppercase tracking-[0.2em] text-emerald-700">Danh mục dược phẩm</p>
                <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 md:text-4xl">Sản phẩm dược phẩm</h1>
                <p class="mt-3 max-w-xl text-sm leading-7 text-slate-600">Tra cứu danh mục và thông tin sản phẩm từ dữ liệu đã được cập nhật.</p>
            </div>
            <div class="grid grid-cols-3 gap-3 text-center text-xs font-semibold text-emerald-800">
                <div class="rounded-2xl border border-emerald-100 bg-white/80 px-4 py-4">Thông tin rõ ràng</div>
                <div class="rounded-2xl border border-emerald-100 bg-white/80 px-4 py-4">Danh mục khoa học</div>
                <div class="rounded-2xl border border-emerald-100 bg-white/80 px-4 py-4">Tra cứu thuận tiện</div>
            </div>
        </div>
    </section>
    <div class="mx-auto max-w-[1440px] px-4 py-8 sm:px-6 lg:px-8">
        <nav aria-label="Breadcrumb" class="mb-6 text-xs text-slate-500"><a href="{{ route('home') }}" class="hover:text-emerald-700">Trang chủ</a><span class="mx-2">›</span><span class="font-semibold text-slate-700">Sản phẩm</span></nav>
        <div class="grid gap-7 lg:grid-cols-[270px_minmax(0,1fr)]">
            <aside class="flex min-w-0 flex-col gap-5">
                <div class="order-2 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:order-1">
                    <h2 class="mb-3 text-base font-bold">Danh mục thuốc</h2>
                    <label for="pharma-category-search" class="sr-only">Tìm danh mục thuốc</label>
                    <input id="pharma-category-search" type="search" wire:model.live.debounce.250ms="categorySearch" placeholder="Tìm danh mục..." class="mb-3 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    <div class="max-h-72 space-y-1 overflow-y-auto overscroll-contain pr-1 lg:max-h-[480px]" role="group" aria-label="Chọn danh mục thuốc">
                        <button type="button" wire:click="$set('category', '')" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm {{ $category === '' ? 'bg-emerald-50 font-bold text-emerald-800' : 'text-slate-600 hover:bg-slate-50' }}"><span>Tất cả danh mục</span><span>{{ $products->total() }}</span></button>
                        @foreach($categories->filter(fn ($item) => str_contains(mb_strtolower($item->name), mb_strtolower(trim($categorySearch)))) as $item)
                            <button type="button" wire:key="category-{{ $item->id }}" wire:click="$set('category', @js($item->slug))" class="block w-full rounded-lg px-3 py-2 text-left text-sm {{ $category === $item->slug ? 'bg-emerald-50 font-bold text-emerald-800' : 'text-slate-600 hover:bg-slate-50' }}">{{ $item->name }}</button>
                        @endforeach
                        @if($categories->filter(fn ($item) => str_contains(mb_strtolower($item->name), mb_strtolower(trim($categorySearch))))->isEmpty())
                            <p class="px-3 py-4 text-xs text-slate-500">Không tìm thấy danh mục.</p>
                        @endif
                    </div>
                </div>
                <div class="order-1 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:order-2">
                    <h2 class="mb-4 text-base font-bold">Bộ lọc</h2>
                    <label for="pharma-catalog-search" class="block text-xs font-semibold text-slate-600">Tên sản phẩm</label>
                    <input id="pharma-catalog-search" type="search" wire:model.live.debounce.350ms="search" placeholder="Tìm tên sản phẩm..." class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-3 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    <button type="button" wire:click="clearFilters" class="mt-4 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Xóa bộ lọc</button>
                </div>
            </aside>
            <main class="min-w-0">
                <div class="mb-5 flex flex-wrap items-center justify-between gap-3"><p class="text-sm text-slate-600">Tìm thấy <strong class="text-slate-900">{{ $products->total() }}</strong> sản phẩm</p><span class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-500">Sắp xếp: Mới nhất</span></div>
                @if($products->isEmpty())
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-20 text-center text-slate-600">Không tìm thấy sản phẩm phù hợp.</div>
                @else
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3">
                        @foreach($products as $product)
                            <article wire:key="pharma-product-{{ $product->id }}" class="group flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-lg">
                                <a href="{{ route('product.detail', $product->slug) }}" class="flex h-full flex-col">
                                    <div class="flex aspect-[5/3] sm:aspect-[5/4] items-center justify-center border-b border-slate-100 bg-gradient-to-br from-slate-50 to-emerald-50/50 p-5">
                                        @if($product->image)
                                            <img src="{{ $product->image_url }}" alt="{{ $product->title }}" loading="lazy" class="h-full w-full object-contain transition group-hover:scale-[1.03]">
                                        @else
                                            <div class="flex flex-col items-center gap-2 text-slate-400"><svg aria-hidden="true" viewBox="0 0 80 80" fill="none" class="h-12 w-12 text-emerald-200"><rect x="17" y="9" width="46" height="62" rx="8" stroke="currentColor" stroke-width="3"/><path d="M40 24v25M27.5 36.5h25" stroke="currentColor" stroke-width="5" stroke-linecap="round"/></svg><span class="text-[11px]">Chưa có ảnh sản phẩm</span></div>
                                        @endif
                                    </div>
                                    <div class="flex flex-1 flex-col p-5">
                                        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-emerald-700">{{ $product->categories->firstWhere('type', 'medicine')?->name ?? 'Dược phẩm' }}</p>
                                        <h2 class="line-clamp-2 min-h-[3rem] text-base font-extrabold leading-6 text-slate-900">{{ $product->title }}</h2>
                                        @if(filled($product->short_description))
                                            <p class="mt-2 line-clamp-2 text-sm leading-6 text-slate-500">{{ strip_tags($product->short_description) }}</p>
                                        @endif
                                        <span class="mt-auto inline-flex pt-5 text-sm font-bold text-emerald-700">Xem chi tiết <span aria-hidden="true" class="ml-2">→</span></span>
                                    </div>
                                </a>
                            </article>
                        @endforeach
                    </div>
                    <div class="mt-8">{{ $products->links() }}</div>
                @endif
            </main>
        </div>
    </div>
</div>
