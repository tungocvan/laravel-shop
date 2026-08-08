<section class="py-12 bg-gradient-to-b from-orange-50 to-white mb-16">
    <div class="container mx-auto px-4">
        @if ($products && count($products) > 0)

            {{-- HEADER: Hiệu ứng rực lửa --}}
            <div class="text-center mb-12 relative py-4">
                {{-- Chữ nền trang trí (Làm mờ đi và dùng font Stroke nếu muốn) --}}
                <span class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-7xl md:text-9xl font-black text-gray-50 uppercase tracking-widest whitespace-nowrap pointer-events-none select-none z-0 opacity-60">
                    BEST SELLER
                </span>
            
                <div class="relative z-10">
                    {{-- Badge nhỏ phía trên --}}
                    <span class="inline-block py-1 px-3 rounded-full bg-orange-50 text-orange-600 text-xs font-bold tracking-wider mb-2 border border-orange-100">
                        HOT COLLECTION
                    </span>
            
                    {{-- Tiêu đề chính --}}
                    <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 uppercase tracking-tight flex items-center justify-center gap-3">
                        {{-- Icon SVG thay cho Emoji --}}
                        <svg class="w-8 h-8 text-orange-500 drop-shadow-sm" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 15h-2v-6h2v6zm4 0h-2v-8h2v8zm-8 0H5v-4h2v4z"/>
                        </svg>
                        <span class="bg-clip-text text-transparent bg-gradient-to-r from-gray-900 via-gray-700 to-gray-900">
                            TOP BÁN CHẠY
                        </span>
                    </h2>
            
                    {{-- Gạch chân trang trí --}}
                    <div class="w-16 h-1 bg-orange-500 mx-auto mt-4 rounded-full"></div>
                    
                    <p class="text-gray-500 text-sm mt-4 font-medium max-w-lg mx-auto">
                        Những sản phẩm được yêu thích nhất tuần qua. Săn ngay kẻo lỡ!
                    </p>
                </div>
            </div>

            {{-- PRODUCT GRID --}}
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-4 gap-4 md:gap-6">
                @foreach ($products as $index => $product)
                    <div
                        class="group bg-white rounded-xl border border-gray-100 hover:border-orange-300 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col overflow-hidden relative">

                        {{-- 1. HUY CHƯƠNG RANKING (Chỉ hiện Top 3) --}}
                        @if ($index < 3)
                            <div class="absolute top-0 left-0 z-20 pointer-events-none">
                                <div class="relative">
                                    @php
                                        $rankColor = match ($index) {
                                            0 => 'bg-yellow-400', // Vàng
                                            1 => 'bg-gray-300', // Bạc
                                            2 => 'bg-orange-400', // Đồng
                                            default => 'bg-blue-500',
                                        };
                                    @endphp
                                    <div
                                        class="{{ $rankColor }} text-white text-xs font-bold px-2 py-1 rounded-br-lg shadow-md flex items-center gap-1">
                                        <span>TOP {{ $index + 1 }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- 2. ẢNH SẢN PHẨM --}}
                        <div class="relative aspect-[1/1] overflow-hidden bg-gray-100">
                            <a href="{{ Route::has('product.detail') ? route('product.detail', ['slug' => $product->slug]) : '#' }}"
                                class="block w-full h-full">
                                <img src="{{ $product->image ? (\Illuminate\Support\Str::startsWith($product->image, ['http', 'https']) ? $product->image : asset('storage/' . $product->image)) : 'https://placehold.co/300x300' }}"
                                    alt="{{ $product->title }}"
                                    class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                            </a>

                            {{-- 🔴 GỌI COMPONENT ADD TO CART (STYLE TRÒN) --}}
                            <div
                                class="absolute bottom-3 right-3 z-30 translate-y-2 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-300">
                                @livewire(
                                    'website.cart.add-to-cart',
                                    [
                                        'productId' => $product->id,
                                        'style' => 'circle-orange',
                                    ],
                                    key('bs-add-' . $product->id)
                                )

                            </div>
                        </div>

                        {{-- 3. THÔNG TIN SẢN PHẨM --}}
                        <div class="p-3 flex flex-col flex-1">
                            {{-- Tên --}}
                            <h3 class="text-sm font-bold text-gray-800 line-clamp-2 mb-2 group-hover:text-orange-600 transition-colors min-h-[40px]"
                                title="{{ $product->title }}">
                                <a
                                    href="{{ Route::has('product.detail') ? route('product.detail', ['slug' => $product->slug]) : '#' }}">
                                    {{ $product->title }}
                                </a>
                            </h3>

                            {{-- Giá --}}
                            <div class="mt-auto mb-3">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="text-base font-extrabold text-red-600">{{ number_format($product->sale_price ?? $product->regular_price) }}đ</span>
                                    @if ($product->sale_price < $product->regular_price)
                                        <span
                                            class="text-xs text-gray-400 line-through">{{ number_format($product->regular_price) }}đ</span>
                                    @endif
                                </div>
                            </div>

                            {{-- 4. THANH TIẾN ĐỘ (PROGRESS BAR) --}}
                            <div class="relative w-full h-4 bg-orange-100 rounded-full overflow-hidden">
                                {{-- Tính % giả lập dựa trên thứ hạng cho đẹp (Top 1 full cây) --}}
                                @php $percent = max(30, 95 - ($index * 8)); @endphp

                                <div class="absolute top-0 left-0 h-full bg-gradient-to-r from-orange-500 to-red-600"
                                    style="width: {{ $percent }}%"></div>

                                <div class="absolute inset-0 flex items-center justify-center z-10">
                                    <span
                                        class="text-[10px] font-bold text-white uppercase drop-shadow-md leading-none flex items-center gap-1">
                                        <svg class="w-3 h-3 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                        Đã bán {{ number_format($product->sold_count) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Nút xem thêm --}}
            <div class="mt-8 text-center">
                <a href="{{ Route::has('product.list') ? route('product.list', ['sort' => 'best_selling']) : '#' }}"
                    class="inline-block px-8 py-3 border border-orange-500 text-orange-600 font-bold rounded-full hover:bg-orange-600 hover:text-white transition-all shadow-sm hover:shadow-orange-200">
                    Xem Bảng Xếp Hạng Đầy Đủ
                </a>
            </div>
        @endif
    </div>
</section>
