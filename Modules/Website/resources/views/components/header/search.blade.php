@if(($mode ?? 'desktop') === 'mobile')
    <div x-show="mobileSearchOpen" x-transition class="lg:hidden pb-4" style="display:none">
        <form action="{{ Route::has('product.list') ? route('product.list') : '#' }}" method="GET">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Tìm kiếm sản phẩm..." class="w-full bg-gray-100 border-none rounded-xl py-3 px-4 focus:ring-2" style="--tw-ring-color: var(--header-accent);">
        </form>
    </div>
@else
    <div class="hidden lg:flex flex-1 relative" style="max-width: var(--header-search-max);">
        <form action="{{ Route::has('product.list') ? route('product.list') : '#' }}" method="GET" class="w-full relative">
            <label for="desktop-product-search" class="sr-only">Tìm kiếm sản phẩm</label>
            <input id="desktop-product-search" type="search" name="search" value="{{ request('search') }}" placeholder="Tìm kiếm sản phẩm, thương hiệu..." class="w-full bg-gray-100 border-none rounded-full py-2.5 pl-5 pr-12 text-sm focus:ring-2" style="--tw-ring-color: var(--header-accent);">
            <button type="submit" aria-label="Tìm kiếm" class="absolute right-2 top-1/2 -translate-y-1/2 opacity-60 hover:opacity-100" style="color: var(--header-accent);">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </button>
        </form>
    </div>
@endif
