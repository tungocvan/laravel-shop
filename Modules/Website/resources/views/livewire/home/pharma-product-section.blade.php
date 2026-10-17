<div>
    <style>
        @media(min-width:640px){[data-product-card-grid]{--pharma-product-cols:attr(data-tablet number, 2)}}
        @media(min-width:640px){[data-product-card-grid][data-tablet="1"]{--pharma-product-cols:1}[data-product-card-grid][data-tablet="2"]{--pharma-product-cols:2}[data-product-card-grid][data-tablet="3"]{--pharma-product-cols:3}}
        @media(min-width:1024px){[data-product-card-grid][data-desktop="2"]{--pharma-product-cols:2}[data-product-card-grid][data-desktop="3"]{--pharma-product-cols:3}[data-product-card-grid][data-desktop="4"]{--pharma-product-cols:4}[data-product-card-grid][data-desktop="5"]{--pharma-product-cols:5}}
    </style>
    <p class="text-sm font-bold uppercase tracking-widest" style="color: {{ $primary }}">{{ $config['eyebrow'] }}</p>
    <h2 class="mt-2 text-3xl font-bold">{{ $config['title'] }}</h2>
    @php($cardDesign = app(\Modules\Website\Services\PharmaProductCardStyle::class)->normalize($config['card_design'] ?? []))
    <div class="mt-8 grid" style="gap:{{ $cardDesign['gap'] }}px;grid-template-columns:repeat(var(--pharma-product-cols),minmax(0,1fr));--pharma-product-cols:{{ $cardDesign['mobile_columns'] }}" data-product-card-grid data-mobile="{{ $cardDesign['mobile_columns'] }}" data-tablet="{{ $cardDesign['tablet_columns'] }}" data-desktop="{{ $cardDesign['desktop_columns'] }}">
        @forelse($products as $product)
            <div wire:key="pharma-product-{{ $sectionKey }}-{{ $product->id }}">
                @include('Website::partials.pharma-product-card', ['cardDesign' => $cardDesign, 'cardPreview' => false])
            </div>
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
