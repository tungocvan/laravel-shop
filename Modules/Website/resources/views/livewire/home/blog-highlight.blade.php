<section class="container mx-auto px-4 mb-20">
    @if($posts && count($posts) > 0)

        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row justify-between items-end mb-10 gap-4 border-b border-gray-100 pb-4">
            <div>
                <h2 class="text-3xl font-bold text-gray-900 tracking-tight mb-2">
                    Tin Tức & Sự Kiện
                </h2>
                <p class="text-gray-500 text-sm">Cập nhật thông tin mới nhất từ chúng tôi</p>
            </div>

            {{-- Link tới trang danh sách tin tức (Bạn cần thay route đúng) --}}
            <a href="{{ Route::has('blog.index') ? route('blog.index') : '#' }}" class="group flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-indigo-600 transition-colors">
                Xem tất cả bài viết
                <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </a>
        </div>

        {{-- Blog Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach($posts as $post)
                <article class="group flex flex-col h-full bg-white rounded-2xl hover:shadow-xl transition-shadow duration-300 overflow-hidden border border-transparent hover:border-gray-100">

                    {{-- 1. ẢNH ĐẠI DIỆN --}}
                    <a href="{{ Route::has('blog.detail') ? route('blog.detail', $post->slug) : '#' }}"
                       class="block overflow-hidden relative aspect-video bg-gray-100">

                        {{-- Xử lý ảnh (thumbnail) --}}
                        @php
                            $imgUrl = $post->thumbnail
                                ? (\Illuminate\Support\Str::startsWith($post->thumbnail, ['http', 'https'])
                                    ? $post->thumbnail
                                    : asset('storage/' . $post->thumbnail))
                                : 'https://placehold.co/600x400?text=No+Image';
                        @endphp

                        <img src="{{ $imgUrl }}"
                             alt="{{ $post->name }}"
                             loading="lazy"
                             class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">

                        {{-- Badge Ngày tháng (Góc trên trái) --}}
                        <div class="absolute top-3 left-3 bg-white/90 backdrop-blur text-gray-900 text-xs font-bold px-3 py-1.5 rounded-lg shadow-sm z-10">
                            {{ $post->published_at ? $post->published_at->format('d/m/Y') : '' }}
                        </div>
                    </a>

                    {{-- 2. NỘI DUNG --}}
                    <div class="flex-1 flex flex-col p-5">
                        {{-- Danh mục (Nếu có load relation categories) --}}
                        @if($post->categories->isNotEmpty())
                            <div class="text-xs font-bold text-indigo-600 uppercase tracking-wider mb-2">
                                {{ $post->categories->first()->name }}
                            </div>
                        @endif

                        {{-- Tiêu đề (name) --}}
                        <h3 class="text-xl font-bold text-gray-900 mb-3 line-clamp-2 leading-snug group-hover:text-indigo-600 transition-colors">
                            <a href="{{ Route::has('blog.detail') ? route('blog.detail', $post->slug) : '#' }}">
                                {{ $post->name }}
                            </a>
                        </h3>

                        {{-- Mô tả ngắn (summary) --}}
                        <p class="text-gray-500 text-sm line-clamp-3 mb-4 flex-1">
                            {{ $post->summary ?? \Illuminate\Support\Str::limit(strip_tags($post->content), 120) }}
                        </p>

                        {{-- Footer: Tác giả & Link --}}
                        <div class="mt-auto flex items-center justify-between pt-4 border-t border-gray-50">
                            {{-- Tác giả --}}
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-500">
                                    {{ substr($post->author->name ?? 'Admin', 0, 1) }}
                                </div>
                                <span class="text-xs text-gray-500 font-medium">
                                    {{ $post->author->name ?? 'Quản trị viên' }}
                                </span>
                            </div>

                            {{-- Read More --}}
                            <a href="{{ Route::has('blog.detail') ? route('blog.detail', $post->slug) : '#' }}"
                               class="inline-flex items-center text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
                                Đọc tiếp
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

    @endif
</section>
