<div class="flex items-center gap-1 md:gap-6">
    <button type="button" @click="mobileSearchOpen=!mobileSearchOpen" aria-label="Mở tìm kiếm" class="lg:hidden p-2 text-gray-600 hover:bg-gray-100 rounded-full">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
    </button>

    @include('Website::components.header.navigation')

    <div class="flex items-center gap-2 md:border-l md:border-gray-200 md:pl-6">
        @livewire('website.wishlist.wishlist-icon')
        @if(class_exists(\Modules\Website\Livewire\Cart\CartIcon::class))
            @livewire('website.cart.cart-icon')
        @endif

        @include('Website::components.header.account')
    </div>
</div>
