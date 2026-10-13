<div>
    <p class="text-sm font-bold uppercase tracking-widest" style="color: {{ $primary }}">{{ $config['eyebrow'] }}</p>
    <h2 class="mt-2 text-3xl font-bold">{{ $config['title'] }}</h2>
    <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        @forelse($products as $product)
            <a wire:key="pharma-product-{{ $sectionKey }}-{{ $product->id }}" href="{{ route('product.detail', $product->slug) }}" class="pharma-card overflow-hidden rounded-2xl border border-slate-200 bg-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2">
                <div class="flex aspect-[4/3] items-center justify-center bg-emerald-50">
                    @if($product->image)
                        <img src="{{ $product->image_url }}" alt="{{ $product->title }}" class="h-full w-full object-contain" loading="lazy">
                    @else
                        <div class="flex flex-col items-center gap-3 text-slate-500"><span class="flex h-16 w-16 items-center justify-center rounded-2xl border border-teal-200 bg-white text-4xl font-light text-teal-600" aria-hidden="true">+</span><span class="text-xs font-medium">Chưa có ảnh sản phẩm</span></div>
                    @endif
                </div>
                <div class="p-5">
                    <p class="text-xs font-semibold uppercase" style="color: {{ $primary }}">{{ $product->categories->first()?->name ?? 'Dược phẩm' }}</p>
                    <h3 class="mt-2 min-h-14 font-bold leading-6">{{ $product->title }}</h3>
                    <span class="mt-3 inline-block text-sm font-semibold" style="color: {{ $primary }}">Xem thông tin →</span>
                </div>
            </a>
        @empty
            <p class="text-slate-600">Chưa có sản phẩm được công bố.</p>
        @endforelse
    </div>
    @if($mode === 'load_more' && $hasMore)
        <div class="mt-8 flex justify-center">
            <button type="button" wire:click="loadMore" wire:loading.attr="disabled" wire:target="loadMore" class="rounded-xl border border-emerald-300 bg-white px-6 py-3 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-50 disabled:opacity-50">
                <span wire:loading.remove wire:target="loadMore">Xem thêm sản phẩm ↓</span>
                <span wire:loading wire:target="loadMore">Đang tải…</span>
            </button>
        </div>
    @elseif($mode === 'pagination' && ($page > 1 || $hasMore))
        <nav class="mt-8 flex items-center justify-center gap-3" aria-label="Phân trang sản phẩm">
            <button type="button" wire:click="previousPage" @disabled($page <= 1) class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm disabled:opacity-40">← Trước</button>
            <span class="text-sm text-slate-600">Trang {{ $page }}</span>
            <button type="button" wire:click="nextPage" @disabled(!$hasMore) class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm disabled:opacity-40">Tiếp →</button>
        </nav>
    @endif
</div>
