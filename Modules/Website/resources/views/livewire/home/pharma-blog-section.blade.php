<div>
    <p class="text-sm font-bold uppercase tracking-widest" style="color: {{ $primary }}">{{ $config['eyebrow'] }}</p>
    <h2 class="mt-2 text-3xl font-bold">{{ $config['title'] }}</h2>
    @if($config['description'])<p class="mt-3 text-slate-600">{{ $config['description'] }}</p>@endif
    <div class="mt-8 grid gap-5 {{ $config['card_style'] === 'list' ? 'grid-cols-1 lg:grid-cols-2' : 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4' }}">
        @forelse($posts as $post)
            <a wire:key="pharma-blog-{{ $sectionKey }}-{{ $post->id }}" href="{{ route('blog.detail', $post->slug) }}" class="pharma-card group overflow-hidden rounded-2xl border border-slate-200 bg-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 {{ $config['card_style'] === 'list' ? 'sm:flex' : '' }}">
                @php
                    $thumbnail = trim((string) $post->thumbnail);
                    $thumbnailUrl = $thumbnail === '' ? null
                        : (preg_match('~^https?://~i', $thumbnail) ? $thumbnail
                            : (str_starts_with($thumbnail, '/') ? asset(ltrim($thumbnail, '/'))
                                : (str_starts_with($thumbnail, 'storage/') ? asset($thumbnail) : asset('storage/'.$thumbnail))));
                @endphp
                <div class="relative flex aspect-[4/3] items-center justify-center overflow-hidden bg-emerald-50 {{ $config['card_style'] === 'list' ? 'sm:w-2/5 sm:shrink-0' : '' }}">
                    <div class="absolute inset-0 flex flex-col items-center justify-center gap-3 text-slate-500" aria-hidden="true">
                        <span class="flex h-16 w-16 items-center justify-center rounded-2xl border border-teal-200 bg-white text-4xl font-light text-teal-600">+</span>
                        <span class="text-xs font-medium">Chưa có ảnh bài viết</span>
                    </div>
                    @if($thumbnailUrl)
                        <img src="{{ $thumbnailUrl }}" alt="" loading="lazy"
                             class="absolute inset-0 h-full w-full object-cover"
                             onerror="this.hidden=true;this.style.display='none'">
                    @endif
                </div>
                <div class="p-5">
                    <p class="text-xs font-semibold uppercase" style="color: {{ $primary }}">{{ $post->categories->first()?->name ?? 'Tin tức' }}</p>
                    <h3 class="mt-2 font-bold leading-6 text-slate-900">{{ $post->name }}</h3>
                    @if($post->published_at)<time datetime="{{ $post->published_at->toDateString() }}" class="mt-2 block text-xs text-slate-500">{{ $post->published_at->format('d/m/Y') }}</time>@endif
                    @if($post->summary)<p class="mt-3 line-clamp-2 text-sm text-slate-600">{{ strip_tags($post->summary) }}</p>@endif
                    <span class="mt-4 inline-block text-sm font-semibold" style="color: {{ $primary }}">Đọc tiếp →</span>
                </div>
            </a>
        @empty
            <p class="text-sm text-slate-600">Chưa có bài viết được xuất bản.</p>
        @endforelse
    </div>
    @if($config['display_mode'] === 'load_more' && $hasMore)
        <div class="mt-8 flex justify-center">
            <button type="button" wire:click="loadMore" wire:loading.attr="disabled" wire:target="loadMore" class="rounded-xl border border-emerald-300 bg-white px-6 py-3 text-sm font-semibold text-emerald-800 hover:bg-emerald-50 disabled:opacity-50">
                <span wire:loading.remove wire:target="loadMore">Xem thêm bài viết ↓</span><span wire:loading wire:target="loadMore">Đang tải…</span>
            </button>
        </div>
    @endif
</div>
