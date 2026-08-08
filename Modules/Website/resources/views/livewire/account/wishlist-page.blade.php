<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-900 border-b pb-4">Sản phẩm yêu thích</h1>

    @if($products->count() > 0)
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @foreach($products as $product)
                <div class="relative">
                    {{-- Nút xóa nhanh (Dấu X) --}}
                    <button wire:click="remove({{ $product->id }})"
                            wire:loading.attr="disabled"
                            class="absolute -top-2 -right-2 z-20 bg-white text-gray-400 hover:text-red-500 rounded-full p-1 shadow-md border border-gray-100 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>

                    {{-- Tái sử dụng Product Card --}}
                    {{-- Vì đang ở trang Wishlist nên auto là đã Like -> Truyền vào Middleware Global Id hoặc set cứng logic --}}
                    <x-website::product-card :product="$product" />
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $products->links() }}
        </div>
    @else
        <div class="text-center py-12">
            <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
            </div>
            <p class="text-gray-500">Bạn chưa lưu sản phẩm nào.</p>
            <a href="{{ route('home') }}" class="inline-block mt-4 px-6 py-2 bg-black text-white rounded-lg hover:bg-gray-800 transition">Khám phá ngay</a>
        </div>
    @endif
</div>
