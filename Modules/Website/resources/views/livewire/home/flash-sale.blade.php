<div class="bg-white rounded-xl shadow-sm border border-red-100 p-6 mb-8 relative overflow-hidden">
    {{-- Hiệu ứng tia sét trang trí --}}
    <div class="absolute top-0 right-0 -mr-10 -mt-10 w-32 h-32 bg-yellow-300 rounded-full blur-3xl opacity-20"></div>

    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4 relative z-10">
        <div class="flex items-center gap-4">
            <h3 class="text-2xl font-black text-red-600 italic uppercase flex items-center gap-2">
                <svg class="w-8 h-8 animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                {{ $flashSale->title ?? 'Flash Sale' }}
            </h3>

            {{-- COUNTDOWN --}}
            <div
                x-data="{
                    expiry: {{ $endTimeJs }},
                    hours: '00',
                    minutes: '00',
                    seconds: '00',
                    interval: null,
                    init() {
                        this.updateTimer();
                        this.interval = setInterval(() => {
                            this.updateTimer();
                        }, 1000);
                    },
                    updateTimer() {
                        const now = new Date().getTime();
                        let distance = this.expiry - now;

                        if (distance <= 0) {
                            clearInterval(this.interval);
                            this.hours = '00';
                            this.minutes = '00';
                            this.seconds = '00';
                            return;
                        }

                        // 👉 TỔNG GIỜ (KHÔNG MOD 24H)
                        const totalHours = Math.floor(distance / (1000 * 60 * 60));
                        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                        this.hours = totalHours.toString().padStart(2, '0');
                        this.minutes = minutes.toString().padStart(2, '0');
                        this.seconds = seconds.toString().padStart(2, '0');
                    }
                }"
                class="flex gap-2 text-sm font-bold text-white"
            >
                <div class="bg-gray-900 p-1.5 rounded min-w-[36px] text-center shadow-sm">
                    <span x-text="hours">00</span>
                </div>
                <span class="text-gray-900 font-bold self-center animate-pulse">:</span>
                <div class="bg-gray-900 p-1.5 rounded min-w-[36px] text-center shadow-sm">
                    <span x-text="minutes">00</span>
                </div>
                <span class="text-gray-900 font-bold self-center animate-pulse">:</span>
                <div class="bg-red-600 p-1.5 rounded min-w-[36px] text-center shadow-sm">
                    <span x-text="seconds">00</span>
                </div>
            </div>
        </div>

        {{-- Link xem tất cả --}}
        <a href="{{ Route::has('flash-sale.list') ? route('flash-sale.list') : '#' }}"
           class="text-gray-500 hover:text-red-600 text-sm font-medium flex items-center gap-1 transition">
            Xem tất cả
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>

    {{-- Product Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        @foreach ($products as $product)
            <div class="group relative bg-white border border-gray-100 hover:border-red-200 hover:shadow-lg rounded-lg transition p-3 h-full flex flex-col">
                <div class="relative aspect-[4/5] mb-3 overflow-hidden rounded bg-gray-100">
                    <a href="{{ Route::has('product.detail') ? route('product.detail', ['slug' => $product->slug]) : '#' }}">
                        <img src="{{ $product->image_url }}"
                             alt="{{ $product->title }}"
                             class="object-cover w-full h-full group-hover:scale-110 transition duration-500">
                    </a>

                    @if ($product->discount_percent > 0)
                        <span class="absolute top-0 left-0 bg-red-600 text-white text-[10px] font-bold px-2 py-1 rounded-br-lg shadow-md z-10">
                            -{{ $product->discount_percent }}%
                        </span>
                    @endif

                    <div class="absolute bottom-2 right-2 text-red-600 bg-white/90 p-1 rounded-full shadow-sm text-xs font-bold flex items-center gap-1 z-10">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                  d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03z"
                                  clip-rule="evenodd"/>
                        </svg>
                    </div>

                     {{-- 🔴 NÚT ADD TO CART (MỚI THÊM) --}}
                     <div class="absolute bottom-3 right-3 z-30 translate-y-2 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-300">
                        @livewire(
                            'website.cart.add-to-cart',
                            [
                                'productId' => $product->id,
                                'style' => 'circle-orange',
                            ],
                            key('flash-add-' . $product->id)
                        )
                    </div>

                </div>

                <h4 class="text-xs md:text-sm font-medium text-gray-800 line-clamp-2 mb-2 flex-1 min-h-[40px]">
                    <a href="{{ Route::has('product.detail') ? route('product.detail', ['slug' => $product->slug]) : '#' }}"
                       class="hover:text-red-600 transition">
                        {{ $product->title }}
                    </a>
                </h4>

                <div class="flex flex-col mt-auto">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-red-600 font-bold text-sm md:text-base">
                            {{ number_format($product->sale_price) }}đ
                        </span>
                        @if ($product->sale_price < $product->regular_price)
                            <span class="text-gray-400 text-xs line-through">
                                {{ number_format($product->regular_price) }}đ
                            </span>
                        @endif
                    </div>

                    {{-- Progress --}}
                    <div class="relative w-full h-4 bg-red-100 rounded-full overflow-hidden mt-2">
                        <div class="absolute top-0 left-0 h-full bg-gradient-to-r from-orange-400 to-red-600 rounded-full transition-all duration-500"
                             style="width: {{ $product->sold_percent }}%"></div>

                        <div class="absolute inset-0 flex items-center justify-center z-10">
                            <span class="text-[10px] font-bold text-white uppercase flex items-center gap-1">
                                <svg class="w-3 h-3 text-yellow-300 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                          d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03z"
                                          clip-rule="evenodd"/>
                                </svg>
                                @if($product->sold_percent > 90)
                                    SẮP CHÁY HÀNG
                                @else
                                    ĐÃ BÁN {{ $product->sold }}
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
