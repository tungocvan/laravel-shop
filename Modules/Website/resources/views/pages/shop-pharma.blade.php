@extends('Website::layouts.frontend')
@section('title', 'Danh mục dược phẩm')
@section('content')
<div class="min-h-screen bg-slate-50 text-slate-900">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-9 sm:px-6">
            <nav class="text-sm text-slate-500"><a href="{{ route('home') }}" class="hover:text-teal-700">Trang chủ</a> / Danh mục dược phẩm</nav>
            <h1 class="mt-3 text-3xl font-extrabold tracking-tight">Tra cứu danh mục dược phẩm</h1>
            <p class="mt-2 text-sm text-slate-600">Tìm sản phẩm theo tên, nhóm dược phẩm và nhà cung cấp.</p>
        </div>
    </header>
    <div class="mx-auto grid max-w-7xl gap-7 px-4 py-8 sm:px-6 lg:grid-cols-[260px_minmax(0,1fr)]">
        <aside>
            <form id="pharma-filters" action="{{ route('product.list') }}" method="GET" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-2"><h2 class="text-lg font-bold">Bộ lọc tra cứu</h2><a href="{{ route('product.list') }}" class="text-xs font-semibold text-teal-700 hover:underline">Xóa bộ lọc</a></div>
                @if($search !== '')
                    <input type="hidden" name="search" value="{{ $search }}">
                    <p class="mt-4 rounded-lg bg-teal-50 px-3 py-2 text-xs text-teal-800">Từ khóa từ thanh tìm kiếm: <strong>{{ $search }}</strong></p>
                @endif
                <label for="pharma-category" class="mt-5 block text-sm font-semibold">Nhóm dược phẩm</label>
                <select id="pharma-category" name="category" onchange="this.form.requestSubmit()" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    <option value="">Tất cả danh mục</option>
                    @foreach($categories as $item)
                        <option value="{{ $item->slug }}" @selected($category === $item->slug)>{{ $item->name }}</option>
                    @endforeach
                </select>
                <label for="pharma-supplier" class="mt-5 block text-sm font-semibold">Nhà cung cấp</label>
                <select id="pharma-supplier" name="supplier" onchange="this.form.requestSubmit()" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    <option value="">Tất cả nhà cung cấp</option>
                    @foreach($suppliers as $name)
                        <option value="{{ $name }}" @selected($supplier === $name)>{{ $name }}</option>
                    @endforeach
                </select>
                @if($authenticated)
                    <label for="pharma-price" class="mt-5 block text-sm font-semibold">Khoảng giá</label>
                    <select id="pharma-price" name="price_range" onchange="this.form.requestSubmit()" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                        <option value="">Tất cả mức giá</option>
                        @foreach(['0-100000' => 'Dưới 100.000đ', '100000-300000' => '100.000 – 300.000đ', '300000-500000' => '300.000 – 500.000đ', '500000-99999999' => 'Trên 500.000đ'] as $value => $label)
                            <option value="{{ $value }}" @selected($range === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                @endif

            </form>
        </aside>
        <main class="min-w-0">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-slate-600">Tìm thấy <strong class="text-slate-900">{{ $products->total() }}</strong> sản phẩm</p>
                <form method="GET" action="{{ route('product.list') }}" class="flex items-center gap-2">
                    <input type="hidden" name="search" value="{{ $search }}"><input type="hidden" name="category" value="{{ $category }}"><input type="hidden" name="supplier" value="{{ $supplier }}">
                    @if($authenticated)<input type="hidden" name="price_range" value="{{ $range }}">@endif
                    <label for="pharma-sort" class="sr-only">Sắp xếp</label>
                    <select id="pharma-sort" name="sort" onchange="this.form.submit()" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
                        <option value="latest" @selected($sort === 'latest')>Mới cập nhật</option>
                        <option value="name_asc" @selected($sort === 'name_asc')>Tên A–Z</option>
                        @if($authenticated)
                            <option value="price_asc" @selected($sort === 'price_asc')>Giá tăng dần</option>
                            <option value="price_desc" @selected($sort === 'price_desc')>Giá giảm dần</option>
                        @endif
                    </select>
                </form>
            </div>
            @if($products->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                    <h2 class="font-bold">Không tìm thấy dược phẩm phù hợp</h2>
                    <p class="mt-2 text-sm text-slate-500">Hãy thử từ khóa hoặc bộ lọc khác.</p>
                    <a href="{{ route('product.list') }}" class="mt-4 inline-block text-sm font-semibold text-teal-700">Xem tất cả sản phẩm →</a>
                </div>
            @else
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach($products as $product)
                        <article class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                            <a href="{{ route('product.detail', $product->slug) }}" class="flex aspect-[4/3] items-center justify-center overflow-hidden bg-teal-50/60">
                                @if($product->image)
                                    <img src="{{ $product->image_url }}" alt="{{ $product->title }}" loading="lazy" class="h-full w-full object-contain p-4" onerror="this.hidden=true;this.nextElementSibling.hidden=false">
                                    <span hidden class="text-sm text-slate-500">Chưa có ảnh sản phẩm</span>
                                @else
                                    <div class="flex flex-col items-center gap-3 text-teal-700">
                                        <svg class="h-12 w-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><path d="M12 7v10M7 12h10"/></svg>
                                        <span class="text-xs text-slate-500">Chưa có ảnh sản phẩm</span>
                                    </div>
                                @endif
                            </a>
                            <div class="flex flex-1 flex-col p-5">
                                <p class="text-xs font-bold uppercase tracking-wide text-teal-700">{{ $product->categories->first()?->name ?? 'Dược phẩm' }}</p>
                                <h2 class="mt-2 line-clamp-2 text-base font-bold leading-6"><a href="{{ route('product.detail', $product->slug) }}" class="hover:text-teal-700">{{ $product->title }}</a></h2>
                                @if($authenticated)
                                    <p class="mt-3 text-lg font-bold text-slate-900">{{ number_format((float) ($product->sale_price > 0 ? $product->sale_price : $product->regular_price), 0, ',', '.') }}đ</p>
                                @endif
                                <a href="{{ route('product.detail', $product->slug) }}" class="mt-auto pt-5 text-sm font-bold text-teal-700">Xem thông tin sản phẩm →</a>
                            </div>
                        </article>
                    @endforeach
                </div>
                <div class="mt-8">{{ $products->links() }}</div>
            @endif
        </main>
    </div>
</div>
@endsection
