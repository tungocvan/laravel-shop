<div>
    {{-- Chỉ hiển thị nội dung khi có dữ liệu --}}
    @if($categories && $categories->isNotEmpty())
        <section class="bg-white rounded-xl shadow-sm border border-red-100 p-6 mb-8 relative overflow-hidden">
            <div class="container mx-auto px-4">

            {{-- Header Section: Minimalist & Clean --}}
            <div class="flex items-end justify-between mb-10 border-b border-gray-100 pb-4">
                <div>
                    <h3 class="text-2xl md:text-3xl font-bold text-gray-900 tracking-tight">
                        Khám Phá Danh Mục
                    </h3>
                    <p class="text-gray-500 text-sm mt-2 font-medium">
                        Lựa chọn xu hướng dành riêng cho bạn
                    </p>
                </div>

                <a
                    href="{{ Route::has('product.list') ? route('product.list') : '#' }}"
                    class="group flex items-center gap-1.5 px-4 py-2 rounded-full bg-gray-50 text-sm font-bold text-gray-900 hover:bg-green-600 hover:text-white transition-all duration-300"
                >
                    Xem tất cả

                    <svg
                        class="w-4 h-4 transition-transform group-hover:translate-x-1"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17 8l4 4m0 0l-4 4m4-4H3"
                        ></path>
                    </svg>
                </a>
            </div>

            {{-- Grid Layout --}}
            <div class="grid grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-y-10 gap-x-6">
                @foreach($categories as $category)
                    <a
                        href="{{ Route::has('product.list') ? route('product.list', ['category' => $category->slug]) : '#' }}"
                        class="group flex flex-col items-center cursor-pointer"
                    >
                        <div class="relative w-20 h-20 md:w-24 md:h-24 mb-4">
                            <div class="w-full h-full rounded-full bg-white border-2 border-gray-100 shadow-[0_8px_30px_rgb(0,0,0,0.06)] flex items-center justify-center overflow-hidden transition-all duration-300 ease-out group-hover:border-green-500 group-hover:shadow-[0_10px_40px_rgba(34,197,94,0.2)] group-hover:-translate-y-2">

                                @if($category->image)
                                    @php
                                        $imgSrc = \Illuminate\Support\Str::startsWith(
                                            $category->image,
                                            ['http://', 'https://']
                                        )
                                            ? $category->image
                                            : asset('storage/'.$category->image);
                                    @endphp

                                    <img
                                        src="{{ $imgSrc }}"
                                        alt="{{ $category->name }}"
                                        class="w-full h-full object-cover p-1 rounded-full transform transition duration-500 group-hover:scale-110"
                                    >
                                @elseif($category->icon)
                                    <div class="text-3xl text-gray-400 group-hover:text-green-600 transition-colors duration-300 transform group-hover:scale-110">
                                        <i class="{{ $category->icon }}"></i>
                                    </div>
                                @else
                                    <div class="text-3xl font-black text-gray-300 group-hover:text-green-600 transition-colors duration-300 select-none">
                                        {{ substr($category->name, 0, 1) }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <span class="text-sm font-bold text-gray-700 text-center px-1 leading-snug transition-colors duration-300 group-hover:text-green-600 line-clamp-2">
                            {{ $category->name }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif

</div>
