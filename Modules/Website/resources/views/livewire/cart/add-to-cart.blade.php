<div>
    {{-- ==========================================
         STYLE 1: CIRCLE ORANGE (Nút tròn nhỏ)
         ========================================== --}}
    @if($style === 'circle-orange')
        @if($productStock > 0)
            {{-- CÒN HÀNG --}}
            <button wire:click.prevent.stop="addToCart"
                    wire:loading.attr="disabled"
                    class="w-10 h-10 md:w-12 md:h-12 bg-orange-600 hover:bg-orange-700 text-white rounded-full shadow-lg flex items-center justify-center transform hover:scale-110 transition-transform focus:outline-none ring-2 ring-white ring-offset-2 ring-offset-orange-100 cursor-pointer group"
                    title="Thêm vào giỏ hàng">
                <svg wire:loading.remove wire:target="addToCart" class="w-5 h-5 md:w-6 md:h-6 transition-transform group-hover:-translate-y-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <svg wire:loading wire:target="addToCart" class="animate-spin w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </button>
        @else
            {{-- HẾT HÀNG (Nút xám) --}}
            <button disabled class="w-10 h-10 md:w-12 md:h-12 bg-gray-400 text-white rounded-full shadow-none flex items-center justify-center cursor-not-allowed opacity-70" title="Hết hàng">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
            </button>
        @endif

    {{-- ==========================================
         STYLE 2: DEFAULT (Trang chi tiết)
         ========================================== --}}
    @else
        <div class="mt-6">
            @if($productStock > 0)
                {{-- CÒN HÀNG --}}
                <div class="flex items-center gap-4">
                    <div class="w-20">
                        <input type="number" wire:model="quantity" min="1" max="{{ $productStock }}"
                               class="w-full border border-gray-300 rounded-lg p-3 text-center focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition shadow-sm font-bold text-gray-700">
                    </div>

                    <button wire:click="addToCart"
                            wire:loading.attr="disabled"
                            class="flex-1 bg-blue-600 text-white font-bold py-3 px-8 rounded-lg hover:bg-blue-700 transition-all shadow-md hover:shadow-lg transform active:scale-95 cursor-pointer disabled:opacity-75 disabled:cursor-not-allowed flex items-center justify-center gap-2">

                        <span wire:loading.remove wire:target="addToCart">Thêm vào giỏ hàng</span>
                        <span wire:loading wire:target="addToCart" class="flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Đang xử lý...
                        </span>
                    </button>
                </div>
                {{-- Hiển thị số lượng kho còn lại (Optional) --}}
                <p class="text-xs text-gray-500 mt-2 ml-1">Còn lại: {{ $productStock }} sản phẩm</p>
            @else
                {{-- HẾT HÀNG --}}
                <button disabled class="w-full bg-gray-300 text-gray-500 font-bold py-3 px-8 rounded-lg cursor-not-allowed flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Tạm hết hàng
                </button>
            @endif
        </div>
    @endif
</div>
