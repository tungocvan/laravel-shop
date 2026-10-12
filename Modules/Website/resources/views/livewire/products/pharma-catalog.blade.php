<div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <nav class="mb-4 text-sm text-slate-500"><a href="{{ route('home') }}" class="hover:text-teal-700">Trang chủ</a> / Sản phẩm dược phẩm</nav>
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Danh mục sản phẩm dược phẩm</h1>
        <p class="mt-2 text-slate-600">Tra cứu thông tin sản phẩm theo tên hoặc danh mục.</p>
        <div class="mt-8 grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 md:grid-cols-[2fr_1fr_auto]">
            <label class="block text-sm font-semibold text-slate-700">Tên sản phẩm
                <input type="search" wire:model.live.debounce.350ms="search" placeholder="Nhập tên sản phẩm..." class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-teal-600 focus:ring-teal-600">
            </label>
            <label class="block text-sm font-semibold text-slate-700">Danh mục
                <select wire:model.live="category" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-teal-600 focus:ring-teal-600">
                    <option value="">Tất cả danh mục</option>
                    @foreach($categories as $item)
                        <option value="{{ $item->slug }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </label>
            <button type="button" wire:click="clearFilters" class="self-end rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Xóa bộ lọc</button>
        </div>
        <p class="my-6 text-sm text-slate-500">{{ $products->total() }} sản phẩm</p>
        @if($products->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center text-slate-600">Không tìm thấy sản phẩm phù hợp.</div>
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($products as $product)
                    <article wire:key="pharma-product-{{ $product->id }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
                        <a href="{{ route('product.detail', $product->slug) }}" class="block">
                            <div class="flex aspect-[4/3] items-center justify-center bg-slate-100">
                                @if($product->image)
                                    <img src="{{ $product->image_url }}" alt="{{ $product->title }}" loading="lazy" class="h-full w-full object-contain">
                                @else
                                    <span class="text-sm text-slate-400">Chưa có hình ảnh</span>
                                @endif
                            </div>
                            <div class="p-5">
                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-teal-700">{{ $product->categories->firstWhere('type', 'medicine')?->name ?? 'Dược phẩm' }}</p>
                                <h2 class="line-clamp-2 text-base font-bold text-slate-900">{{ $product->title }}</h2>
                                <p class="mt-4 text-sm font-semibold text-teal-700">Xem thông tin sản phẩm →</p>
                            </div>
                        </a>
                    </article>
                @endforeach
            </div>
            <div class="mt-8">{{ $products->links() }}</div>
        @endif
    </div>
</div>
