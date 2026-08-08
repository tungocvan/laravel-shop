<section class="container mx-auto px-4 mb-8">
    <div class="bg-white rounded-xl shadow-[0_2px_15px_rgb(0,0,0,0.05)] border border-gray-100 p-6 md:p-8">

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 lg:divide-x lg:divide-gray-100">
            @foreach($badges as $badge)
                <div class="group flex items-center gap-4 px-4 transition-transform hover:-translate-y-1 duration-300">

                    {{-- 1. XỬ LÝ ICON (Ảnh hoặc Class Font) --}}
                    <div class="flex-shrink-0 w-12 h-12 md:w-14 md:h-14 flex items-center justify-center rounded-full bg-green-50 text-green-600 transition-colors group-hover:bg-green-600 group-hover:text-white">
                        @if(isset($badge['icon']) && (str_contains($badge['icon'], 'http') || str_contains($badge['icon'], 'storage')))
                            {{-- Trường hợp là Ảnh (Upload hoặc Link online) --}}
                            <img src="{{ str_starts_with($badge['icon'], 'http') ? $badge['icon'] : asset('storage/'.$badge['icon']) }}"
                                 class="w-8 h-8 object-contain"
                                 alt="{{ $badge['title'] ?? '' }}">
                        @else
                            {{-- Trường hợp là Font Icon (fa-solid...) --}}
                            <i class="{{ $badge['icon'] ?? 'fa-solid fa-check' }} text-xl md:text-2xl"></i>
                        @endif
                    </div>

                    {{-- 2. TEXT CONTENT --}}
                    <div class="flex-1">
                        <h4 class="text-sm md:text-base font-bold text-gray-900 group-hover:text-green-600 transition-colors uppercase leading-tight">
                            {{ $badge['title'] ?? 'Tiêu đề' }}
                        </h4>
                        @if(!empty($badge['sub_title']))
                            <p class="text-xs md:text-sm text-gray-500 mt-1 line-clamp-1">
                                {{ $badge['sub_title'] }}
                            </p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

    </div>
</section>
