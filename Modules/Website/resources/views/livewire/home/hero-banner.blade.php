<style>
    [x-cloak] { display: none !important; }
</style>

<div class="w-full mb-8 rounded-xl overflow-hidden shadow-lg group relative"
     x-data="{
        activeSlide: 0,
        totalSlides: {{ count($slides) }},
        timer: null,
        startAutoSlide() {
            this.timer = setInterval(() => {
                this.nextSlide();
            }, 5000);
        },
        stopAutoSlide() {
            clearInterval(this.timer);
        },
        nextSlide() {
            this.activeSlide = (this.activeSlide + 1) % this.totalSlides;
        },
        prevSlide() {
            this.activeSlide = (this.activeSlide - 1 + this.totalSlides) % this.totalSlides;
        }
     }"
     x-init="startAutoSlide()"
     @mouseenter="stopAutoSlide()"
     @mouseleave="startAutoSlide()"
>
    {{--
        CẬP NHẬT QUAN TRỌNG VỀ TỶ LỆ KHUNG HÌNH (ASPECT RATIO):
        - aspect-[3/4]: Mobile (Dọc, hiển thị rõ sản phẩm trên đt).
        - md:aspect-[21/9]: Desktop & Tablet (Ngang, dẹt chuẩn Banner quảng cáo).
        => Loại bỏ các tỷ lệ trung gian 16:9 để tránh ảnh bị quá cao trên Laptop.
    --}}
    <div class="relative w-full aspect-[3/4] md:aspect-[21/9] bg-gray-200">

        @if(count($slides) > 0)
            @foreach($slides as $index => $slide)
                <div class="absolute inset-0 w-full h-full"
                     x-show="activeSlide === {{ $index }}"
                     x-transition:enter="transition ease-out duration-1000"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-1000"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     style="{{ $index !== 0 ? 'display: none;' : '' }}"
                >
                    {{-- Thẻ Picture để tối ưu ảnh --}}
                    <picture class="w-full h-full block">
                        {{-- 1. Xử lý ảnh Mobile --}}
                        @if(!empty($slide['image_mobile']))
                            @php
                                // Kiểm tra xem link có phải là http/https không
                                $imgMobile = \Illuminate\Support\Str::startsWith($slide['image_mobile'], ['http://', 'https://'])
                                            ? $slide['image_mobile']
                                            : asset('storage/'.$slide['image_mobile']);
                            @endphp
                            <source media="(max-width: 767px)" srcset="{{ $imgMobile }}">
                        @endif

                        {{-- 2. Xử lý ảnh Desktop --}}
                        @php
                            $rawImg = $slide['image_desktop'] ?? $slide['image'] ?? '';
                            $imgDesktop = \Illuminate\Support\Str::startsWith($rawImg, ['http://', 'https://'])
                                        ? $rawImg
                                        : asset('storage/'.$rawImg);
                        @endphp

                        <img src="{{ $imgDesktop }}"
                             class="w-full h-full object-cover"
                             alt="{{ $slide['title'] ?? 'Banner' }}">
                    </picture>

                    {{-- Overlay & Text --}}
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent md:bg-gradient-to-r md:from-black/60 md:via-transparent md:to-transparent">
                        <div class="h-full flex items-end justify-center pb-12 md:items-center md:justify-start md:pb-0 md:pl-20">
                            <div class="max-w-xl text-white space-y-3 md:space-y-4 text-center md:text-left px-4">

                                <h2 class="text-3xl md:text-5xl lg:text-6xl font-bold leading-tight drop-shadow-lg animate-fadeInUp">
                                    {{ $slide['title'] ?? '' }}
                                </h2>

                                @if(!empty($slide['sub_title']))
                                    <p class="text-sm md:text-xl text-gray-100 font-light drop-shadow-md animate-fadeInUp delay-100 line-clamp-2 md:line-clamp-none">
                                        {{ $slide['sub_title'] }}
                                    </p>
                                @endif

                                @if(!empty($slide['btn_text']))
                                    <div class="pt-4 animate-fadeInUp delay-200">
                                        <a href="{{ $slide['link'] ?? '#' }}" class="inline-block px-8 py-3 bg-white text-gray-900 font-bold rounded-full hover:bg-green-500 hover:text-white transition shadow-lg transform hover:scale-105">
                                            {{ $slide['btn_text'] }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
             <div class="flex items-center justify-center h-full text-gray-500">
                Chưa có banner.
             </div>
        @endif
    </div>

    {{-- Nút điều hướng (Chỉ hiện trên Desktop/Tablet) --}}
    @if(count($slides) > 1)
        <div class="hidden md:block">
            <button @click="prevSlide()" class="absolute left-4 top-1/2 -translate-y-1/2 z-20 p-2 bg-white/20 hover:bg-white text-white hover:text-black rounded-full backdrop-blur-sm transition" x-cloak>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </button>
            <button @click="nextSlide()" class="absolute right-4 top-1/2 -translate-y-1/2 z-20 p-2 bg-white/20 hover:bg-white text-white hover:text-black rounded-full backdrop-blur-sm transition" x-cloak>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </button>
        </div>

        {{-- Dots --}}
        <div class="absolute bottom-4 left-0 right-0 z-20 flex justify-center gap-2" x-cloak>
            <template x-for="i in totalSlides">
                <button @click="activeSlide = i - 1"
                        class="h-2 rounded-full transition-all duration-300 shadow-sm"
                        :class="activeSlide === (i - 1) ? 'bg-white w-8' : 'bg-white/50 w-2 hover:bg-white'">
                </button>
            </template>
        </div>
    @endif
</div>
