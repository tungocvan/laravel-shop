<section class="container mx-auto px-4 mb-20">
    @if($products && count($products) > 0)

        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-8 gap-4">
            <div>
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900 tracking-tight flex items-center gap-2">
                    <span class="bg-black text-white w-8 h-8 flex items-center justify-center rounded-lg text-lg">★</span>
                    Sản Phẩm Nổi Bật
                </h2>
                <p class="text-gray-500 text-sm mt-2">Tuyển tập những sản phẩm hot nhất tuần qua</p>
            </div>

            {{-- Link Xem tất cả --}}
            <a href="{{ Route::has('product.list') ? route('product.list') : '#' }}" class="group flex items-center gap-2 text-sm font-semibold text-gray-900 hover:text-green-600 transition-colors">
                Xem toàn bộ shop
                <span class="bg-gray-100 group-hover:bg-green-100 text-gray-600 group-hover:text-green-600 w-6 h-6 flex items-center justify-center rounded-full transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </span>
            </a>
        </div>

        {{-- Product Grid --}}
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-x-4 gap-y-8 md:gap-x-6">
            @foreach($products as $product)
                {{-- Gọi Component con, truyền biến product vào --}}
                <x-website::product-card :product="$product" />
            @endforeach
        </div>

        {{-- Nút xem thêm (Mobile Only) --}}
        <div class="mt-8 text-center md:hidden">
            <a href="{{ Route::has('product.list') ? route('product.list') : '#' }}" class="inline-block px-6 py-2 border border-gray-300 text-gray-700 font-medium rounded-full hover:bg-gray-50 transition">
                Xem thêm sản phẩm
            </a>
        </div>

    @endif
</section>
