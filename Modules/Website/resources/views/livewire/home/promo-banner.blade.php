<div class="container mx-auto px-4 mb-20">
    <div class="group relative w-full aspect-[4/5] sm:aspect-[16/9] md:aspect-[3/1] rounded-2xl overflow-hidden shadow-2xl">

        {{-- 1. ẢNH NỀN --}}
        <a href="{{ $banner['link'] ?? '#' }}" class="absolute inset-0 z-0 block w-full h-full">
            {{-- Xử lý Logic Ảnh --}}
            @php
                $imgUrl = \Illuminate\Support\Str::startsWith($banner['image'], ['http', 'https'])
                        ? $banner['image']
                        : asset('storage/' . $banner['image']);
            @endphp
 
            <img src="{{ $imgUrl }}"
                 alt="{{ $banner['title'] ?? 'Promotion' }}"
                 loading="lazy"
                 class="w-full h-full object-cover transition-transform duration-700 ease-in-out group-hover:scale-105">

            {{-- Gradient tối ưu: Mobile tối hơn --}}
            <div class="absolute inset-0 bg-gradient-to-t md:bg-gradient-to-r from-black/80 via-black/40 to-transparent"></div>
        </a>

        {{-- 2. NỘI DUNG --}}
        <div class="absolute inset-0 flex items-center px-6 md:px-16 pointer-events-none">
            <div class="max-w-2xl relative z-10 pointer-events-auto w-full">

                {{-- Badge (Cố định) --}}
                <span class="inline-block py-1 px-3 rounded-full bg-white/20 backdrop-blur-md border border-white/30 text-white text-[10px] md:text-xs font-bold tracking-wider mb-3 md:mb-4 uppercase animate-fade-in-down">
                    Limited Offer
                </span>

                {{-- Heading --}}
                @if(!empty($banner['title']))
                    <h2 class="text-3xl md:text-5xl font-black text-white leading-tight mb-3 md:mb-4 drop-shadow-lg tracking-tight">
                        {{ $banner['title'] }}
                    </h2>
                @endif

                {{-- Subtitle --}}
                @if(!empty($banner['sub_title']))
                    <p class="text-sm md:text-lg text-gray-200 mb-6 md:mb-8 font-light max-w-lg line-clamp-3 md:line-clamp-none">
                        {{ $banner['sub_title'] }}
                    </p>
                @endif

                {{-- Actions --}}
                <div class="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-6">
                    {{-- Button 1: Mua ngay --}}
                    @if(!empty($banner['btn_text']))
                        <a href="{{ $banner['link'] ?? '#' }}"
                           class="inline-flex justify-center items-center px-6 py-3 md:px-8 md:py-3 bg-white text-gray-900 font-bold rounded-lg hover:bg-green-400 hover:text-white transition-all duration-300 shadow-lg transform hover:-translate-y-1 text-sm md:text-base">
                            {{ $banner['btn_text'] }}
                        </a>
                    @endif

                    {{-- Link 2: Chi tiết --}}
                    @if(!empty($banner['details_link']))
                        <a href="{{ $banner['details_link'] }}" class="text-white/90 text-sm font-medium hover:text-white hover:underline transition-all flex items-center gap-1 group/link w-fit">
                            Xem chi tiết
                            <svg class="w-4 h-4 transition-transform group-hover/link:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
