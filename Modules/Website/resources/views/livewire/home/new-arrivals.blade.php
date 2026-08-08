<div class="mb-20 container mx-auto px-4"
     x-data="{
        scrollLeft() {
            $refs.scrollContainer.scrollBy({ left: -300, behavior: 'smooth' });
        },
        scrollRight() {
            $refs.scrollContainer.scrollBy({ left: 300, behavior: 'smooth' });
        }
     }">

    {{-- Header Section --}}
    <div class="flex items-center justify-between mb-8 border-b border-gray-100 pb-4">
        <div class="flex items-center gap-3">
            <div class="bg-blue-100 p-2 rounded-lg text-blue-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <h3 class="text-2xl font-bold text-gray-900 uppercase tracking-tight">Hàng Mới Về</h3>
                <p class="text-xs text-gray-400 hidden md:block">Cập nhật xu hướng mỗi ngày</p>
            </div>
        </div>

        <div class="flex items-center gap-4">
            {{-- Nút điều hướng Desktop --}}
            <div class="hidden md:flex gap-2">
                <button @click="scrollLeft()" class="p-2 rounded-full border border-gray-200 hover:bg-gray-900 hover:text-white hover:border-gray-900 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </button>
                <button @click="scrollRight()" class="p-2 rounded-full border border-gray-200 hover:bg-gray-900 hover:text-white hover:border-gray-900 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </button>
            </div>

            <a href="{{ Route::has('product.list') ? route('product.list') : '#' }}" class="text-sm font-semibold text-gray-500 hover:text-blue-600 transition whitespace-nowrap">
                Xem tất cả
            </a>
        </div>
    </div>

    {{-- Horizontal Scroll Container --}}
    <div class="relative group/slider">
        <div x-ref="scrollContainer" class="flex overflow-x-auto gap-6 pb-6 scrollbar-hide snap-x scroll-smooth">

            @foreach($products as $product)
                {{-- Product Card Wrapper: Set width cố định cho slider --}}
                <div class="min-w-[180px] w-[180px] md:min-w-[240px] md:w-[240px] flex-shrink-0 snap-start">

                    {{-- GỌI COMPONENT DÙNG CHUNG (Tái sử dụng logic ảnh & giá) --}}
                    <x-website::product-card :product="$product" />

                </div>
            @endforeach

            {{-- "See More" Card (Thẻ cuối cùng) --}}
            <div class="min-w-[150px] flex-shrink-0 snap-start flex items-center justify-center">
                <a href="{{ Route::has('product.list') ? route('product.list') : '#' }}" class="group/more flex flex-col items-center gap-2 text-gray-400 hover:text-blue-600 transition">
                    <div class="w-12 h-12 rounded-full border-2 border-gray-200 group-hover/more:border-blue-600 flex items-center justify-center transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </div>
                    <span class="text-sm font-medium">Xem tất cả</span>
                </a>
            </div>

        </div>

        {{-- Gradient che mờ 2 bên (Optional) --}}
        <div class="absolute inset-y-0 right-0 w-16 bg-gradient-to-l from-white to-transparent pointer-events-none md:hidden"></div>
    </div>
</div>
