@extends('Website::layouts.frontend')
@section('title', 'Danh mục dược phẩm')
@section('content')
<div class="min-h-screen bg-slate-50 text-slate-900">
    <header class="border-b border-slate-100 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:py-7">
            <nav class="text-xs font-medium text-slate-500"><a href="{{ route('home') }}" class="hover:text-teal-700">Trang chủ</a> / Danh mục dược phẩm</nav>
            <h1 class="mt-2 text-2xl font-extrabold tracking-tight sm:text-3xl">Tra cứu danh mục dược phẩm</h1>
            <p class="mt-1 text-sm text-slate-600">Tìm sản phẩm theo tên, nhóm dược phẩm và nhà cung cấp.</p>
        </div>
    </header>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6">
        <section aria-label="Bộ lọc danh mục">
            <form id="pharma-filters" action="{{ route('product.list') }}" method="GET" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex items-center justify-between gap-2"><h2 class="text-sm font-bold text-slate-900">Lọc sản phẩm</h2><a href="{{ route('product.list') }}" class="text-xs font-semibold text-teal-700 hover:underline">Xóa bộ lọc</a></div>
                @if($search !== '')
                    <input type="hidden" name="search" value="{{ $search }}">
                    <p class="mt-4 rounded-lg bg-teal-50 px-3 py-2 text-xs text-teal-800">Từ khóa từ thanh tìm kiếm: <strong>{{ $search }}</strong></p>
                @endif
                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
                @if(!empty($filterVisibility['category']))
                <div class="min-w-0"><label for="pharma-category" class="block text-xs font-semibold text-slate-700">Nhóm dược phẩm</label>
                <select id="pharma-category" name="category" onchange="this.form.requestSubmit()" class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-teal-500 focus:ring-teal-500">
                    <option value="">Tất cả danh mục</option>
                    @foreach($categories as $item)
                        <option value="{{ $item->slug }}" @selected($category === $item->slug)>{{ $item->name }}</option>
                    @endforeach
                </select>
                </div>
                @endif
                @if(!empty($filterVisibility['supplier']))
                    <div class="min-w-0"><label for="pharma-supplier" class="block text-xs font-semibold text-slate-700">Nhà cung cấp</label>
                    <x-select-search id="pharma-supplier" name="supplier" placeholder="Tìm nhà cung cấp..." onchange="this.form.requestSubmit()">
                        <option value="">Tất cả nhà cung cấp</option>
                        @foreach($suppliers as $name)
                            <option value="{{ $name }}" @selected($supplier === $name)>{{ $name }}</option>
                        @endforeach
                    </x-select-search>
                </div>
                @endif
                @if(!empty($filterVisibility['product_type']))
                <div class="min-w-0"><label for="pharma-product-type" class="block text-xs font-semibold text-slate-700">Loại sản phẩm</label>
                <select id="pharma-product-type" name="product_type" onchange="this.form.requestSubmit()" class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-teal-500 focus:ring-teal-500">
                    <option value="">Tất cả loại sản phẩm</option>
                    @foreach($productTypes as $value => $label)
                        <option value="{{ $value }}" @selected($productType === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                </div>
                @endif
                @if(!empty($filterVisibility['circular_group']))
                <div class="min-w-0"><label for="pharma-circular-group" class="block text-xs font-semibold text-slate-700">Nhóm thuốc theo thông tư</label>
                <select id="pharma-circular-group" name="circular_group" onchange="this.form.requestSubmit()" class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-teal-500 focus:ring-teal-500">
                    <option value="">Tất cả nhóm thuốc</option>
                    @foreach($circularGroups as $group)
                        <option value="{{ $group }}" @selected($circularGroup === $group)>{{ $group }}</option>
                    @endforeach
                </select>
                </div>
                @endif

                </div>
            </form>
        </section>
        <main class="min-w-0">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-3">
                <p class="text-sm text-slate-600">Tìm thấy <strong class="text-slate-900">{{ $products->total() }}</strong> sản phẩm</p>
                <form method="GET" action="{{ route('product.list') }}" class="flex items-center gap-2">
                    <input type="hidden" name="search" value="{{ $search }}"><input type="hidden" name="category" value="{{ !empty($filterVisibility['category']) ? $category : '' }}"><input type="hidden" name="supplier" value="{{ !empty($filterVisibility['supplier']) ? $supplier : '' }}">
                    <input type="hidden" name="product_type" value="{{ !empty($filterVisibility['product_type']) ? $productType : '' }}"><input type="hidden" name="circular_group" value="{{ !empty($filterVisibility['circular_group']) ? $circularGroup : '' }}">
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
                <style>
                    [data-product-card-grid][data-mobile="1"] { --pharma-product-cols: 1; }
                    [data-product-card-grid][data-mobile="2"] { --pharma-product-cols: 2; }
                    @media (min-width: 640px) {
                        [data-product-card-grid][data-tablet="1"] { --pharma-product-cols: 1; }
                        [data-product-card-grid][data-tablet="2"] { --pharma-product-cols: 2; }
                        [data-product-card-grid][data-tablet="3"] { --pharma-product-cols: 3; }
                    }
                    @media (min-width: 1024px) {
                        [data-product-card-grid][data-desktop="2"] { --pharma-product-cols: 2; }
                        [data-product-card-grid][data-desktop="3"] { --pharma-product-cols: 3; }
                        [data-product-card-grid][data-desktop="4"] { --pharma-product-cols: 4; }
                        [data-product-card-grid][data-desktop="5"] { --pharma-product-cols: 5; }
                    }
                </style>
                <div class="grid" data-product-card-grid data-mobile="{{ $catalogCardDesign['mobile_columns'] }}" data-tablet="{{ $catalogCardDesign['tablet_columns'] }}" data-desktop="{{ $catalogCardDesign['desktop_columns'] }}" style="gap:{{ $catalogCardDesign['gap'] }}px;grid-template-columns:repeat(var(--pharma-product-cols),minmax(0,1fr))">
                    @foreach($products as $product)
                        @include('Website::partials.pharma-product-card', ['cardDesign' => $catalogCardDesign, 'cardPreview' => false, 'cardShowPrice' => $authenticated])
                    @endforeach
                </div>
                <nav aria-label="Phân trang sản phẩm" class="mt-8 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <p class="text-sm text-slate-600">Hiển thị <strong>{{ $products->firstItem() }}–{{ $products->lastItem() }}</strong> / {{ $products->total() }} sản phẩm</p>
                    <div class="flex flex-wrap items-center gap-1.5">
                        @if($products->onFirstPage())
                            <span class="rounded-lg border border-slate-100 px-3 py-2 text-sm text-slate-400" aria-disabled="true">‹ Trước</span>
                        @else
                            <a href="{{ $products->previousPageUrl() }}" rel="prev" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium hover:bg-teal-50">‹ Trước</a>
                        @endif
                        @foreach($products->getUrlRange(max(1, $products->currentPage() - 2), min($products->lastPage(), $products->currentPage() + 2)) as $page => $url)
                            @if($page === $products->currentPage())
                                <span aria-current="page" class="rounded-lg bg-teal-700 px-3 py-2 text-sm font-bold text-white">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" aria-label="Trang {{ $page }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm hover:bg-teal-50">{{ $page }}</a>
                            @endif
                        @endforeach
                        @if($products->hasMorePages())
                            <a href="{{ $products->nextPageUrl() }}" rel="next" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium hover:bg-teal-50">Sau ›</a>
                        @else
                            <span class="rounded-lg border border-slate-100 px-3 py-2 text-sm text-slate-400" aria-disabled="true">Sau ›</span>
                        @endif
                    </div>
                </nav>
            @endif
        </main>
    </div>
</div>
@endsection
