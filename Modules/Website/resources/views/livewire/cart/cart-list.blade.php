<div class="bg-gray-50 min-h-screen py-12 font-sans">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header & Breadcrumb --}}
        <div class="flex items-end justify-between mb-8">
            <div>
                <h1 class="text-3xl md:text-4xl font-black text-gray-900 tracking-tight">Giỏ Hàng</h1>
                <p class="text-gray-500 mt-2">
                    Bạn đang có <span class="font-bold text-gray-900">{{ $this->cartData['items']->count() }}</span> sản phẩm trong giỏ.
                </p>
            </div>
            <a href="{{ route('home') }}" class="hidden md:flex items-center gap-1 text-sm font-bold text-blue-600 hover:text-blue-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Tiếp tục mua sắm
            </a>
        </div>

        {{-- Kiểm tra giỏ hàng có rỗng không --}}
        @if($this->cartData['items']->count() > 0)
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">

                {{-- COL 1: Product List --}}
                <div class="lg:col-span-8 space-y-6">

                    {{-- Free Shipping Progress (Logic hiển thị đơn giản, tính toán đã nằm ở Service nếu cần phức tạp hơn) --}}
                    @php
                        $subtotal = $this->cartData['subtotal'];
                        $threshold = 1000000; // Ngưỡng freeship (có thể đưa vào config)
                        $percent = min(100, ($subtotal / $threshold) * 100);
                        $remaining = $threshold - $subtotal;
                    @endphp
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        @if($remaining > 0)
                            <p class="text-sm text-gray-600 mb-2">
                                Mua thêm <span class="font-bold text-red-500">{{ number_format($remaining) }}đ</span> để được <span class="font-bold text-green-600 uppercase">Miễn phí vận chuyển</span>
                            </p>
                        @else
                            <p class="text-sm text-green-600 font-bold mb-2 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Chúc mừng! Đơn hàng của bạn được Miễn Phí Vận Chuyển.
                            </p>
                        @endif
                        <div class="w-full bg-gray-100 rounded-full h-2.5 overflow-hidden">
                            <div class="bg-gradient-to-r from-blue-500 to-green-400 h-2.5 rounded-full transition-all duration-1000 ease-out" style="width: {{ $percent }}%"></div>
                        </div>
                    </div>

                    {{-- Cart Items List --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 divide-y divide-gray-100 overflow-hidden">
                        @foreach($this->cartData['items'] as $item)
                            <div class="p-6 flex gap-6 group transition hover:bg-gray-50/50 relative" wire:key="item-{{ $item->id }}">

                                {{-- Loading Overlay cho từng Item --}}
                                <div wire:loading wire:target="increment({{ $item->id }}), decrement({{ $item->id }}), remove({{ $item->id }})"
                                     class="absolute inset-0 bg-white/60 backdrop-blur-[1px] flex items-center justify-center z-20 rounded-2xl">
                                    <svg class="animate-spin h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>

                                {{-- Image --}}
                                <div class="w-24 h-24 md:w-32 md:h-32 flex-shrink-0 bg-gray-100 rounded-xl overflow-hidden border border-gray-200">
                                    <img src="{{ $item->product->image_url ?? 'https://placehold.co/400x400?text=No+Image' }}"
                                         alt="{{ $item->product->title ?? 'Product' }}"
                                         class="w-full h-full object-cover mix-blend-multiply">
                                </div>

                                {{-- Details --}}
                                <div class="flex-1 flex flex-col justify-between">
                                    <div class="flex justify-between items-start gap-4">
                                        <div>
                                            <h3 class="font-bold text-gray-900 text-lg leading-snug hover:text-blue-600 transition">
                                                <a href="{{ route('product.detail', $item->product->slug ?? '#') }}">
                                                    {{ $item->product->title ?? 'Sản phẩm không tồn tại' }}
                                                </a>
                                            </h3>
                                            {{-- Variants placeholder --}}
                                            <p class="text-sm text-gray-500 mt-1">Phân loại: Mặc định</p>
                                        </div>

                                        {{-- Remove Button --}}
                                        <button wire:click="remove({{ $item->id }})"
                                                class="text-gray-400 hover:text-red-500 transition p-1 rounded-full hover:bg-red-50"
                                                title="Xóa sản phẩm">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>

                                    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mt-4">
                                        {{-- Quantity Control --}}
                                        <div class="flex items-center bg-white border border-gray-300 rounded-lg w-fit shadow-sm">
                                            <button wire:click="decrement({{ $item->id }})" class="w-8 h-8 flex items-center justify-center text-gray-500 hover:bg-gray-100 hover:text-gray-900 rounded-l-lg transition disabled:opacity-50" @if($item->quantity <= 1) disabled @endif>-</button>
                                            <input type="text" value="{{ $item->quantity }}" readonly class="w-10 text-center text-sm font-bold text-gray-900 border-none p-0 focus:ring-0">
                                            <button wire:click="increment({{ $item->id }})" class="w-8 h-8 flex items-center justify-center text-gray-500 hover:bg-gray-100 hover:text-gray-900 rounded-r-lg transition">+</button>
                                        </div>

                                        {{-- Price --}}
                                        <div class="text-right">
                                            <p class="text-lg font-black text-gray-900">
                                                {{ number_format($item->total) }}đ
                                            </p>
                                            @if($item->quantity > 1)
                                                <p class="text-xs text-gray-500">{{ number_format($item->price) }}đ / cái</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- COL 2: Summary (Sticky) --}}
                <div class="lg:col-span-4 lg:sticky lg:top-8">
                    <div class="bg-white p-6 rounded-2xl shadow-lg shadow-gray-200/50 border border-gray-100">
                        <h3 class="text-xl font-bold text-gray-900 mb-6">Tóm tắt đơn hàng</h3>

                        <div class="space-y-4 text-sm">
                            {{-- Tạm tính --}}
                            <div class="flex justify-between text-gray-600">
                                <span>Tạm tính</span>
                                <span class="font-medium text-gray-900">{{ number_format($this->cartData['subtotal']) }}đ</span>
                            </div>

                            {{-- Giảm giá (Hiển thị có điều kiện) --}}
                            @if($this->cartData['discount'] > 0)
                                <div class="flex justify-between items-start text-green-600 bg-green-50 p-2 rounded-lg border border-green-100">
                                    <div class="flex flex-col">
                                        <span class="font-bold flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                                            {{ $this->cartData['coupon_code'] }}
                                        </span>
                                        <button wire:click="removeCoupon" wire:loading.attr="disabled" class="text-xs text-red-500 underline text-left mt-1 hover:text-red-700">
                                            [Gỡ bỏ]
                                        </button>
                                    </div>
                                    <span class="font-bold">-{{ number_format($this->cartData['discount']) }}đ</span>
                                </div>
                            @else
                                <div class="flex justify-between text-gray-600">
                                    <span>Giảm giá</span>
                                    <span class="font-medium text-gray-900">0đ</span>
                                </div>
                            @endif

                            {{-- Phí ship --}}
                            <div class="flex justify-between text-gray-600">
                                <span>Phí vận chuyển</span>
                                <span class="text-green-600 font-bold">Tính khi thanh toán</span>
                            </div>

                            {{-- Divider --}}
                            <div class="border-t border-gray-100 my-4"></div>

                            {{-- Tổng cộng --}}
                            <div class="flex justify-between items-end">
                                <span class="font-bold text-gray-900 text-base">Tổng cộng</span>
                                <span class="text-2xl font-black text-blue-600 tracking-tight">{{ number_format($this->cartData['total']) }}đ</span>
                            </div>

                            <p class="text-xs text-gray-400 text-right">(Đã bao gồm VAT nếu có)</p>
                        </div>

                        {{-- Checkout Button --}}
                        <div class="mt-8">
                            <a href="{{ route('checkout.index') }}"
                               class="block w-full bg-black text-white text-center font-bold py-4 rounded-xl hover:bg-gray-800 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 relative overflow-hidden group">
                                <span class="relative z-10 flex items-center justify-center gap-2">
                                    Thanh toán ngay
                                    <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                </span>
                            </a>
                        </div>

                        {{-- Coupon Input --}}
                        <div class="mt-6 pt-6 border-t border-gray-100">
                            <div class="relative">
                                <input wire:model="couponCodeInput"
                                       wire:keydown.enter="applyCoupon"
                                       type="text"
                                       placeholder="Mã giảm giá"
                                       class="w-full bg-gray-50 border border-gray-200 rounded-lg py-2.5 pl-4 pr-20 text-sm focus:ring-2 focus:ring-blue-500 focus:bg-white transition @error('couponCodeInput') border-red-500 @enderror">

                                <button wire:click="applyCoupon"
                                        wire:loading.attr="disabled"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 text-xs font-bold text-blue-600 hover:text-blue-700 px-2 disabled:opacity-50">
                                    <span wire:loading.remove wire:target="applyCoupon">Áp dụng</span>
                                    <span wire:loading wire:target="applyCoupon">...</span>
                                </button>
                            </div>
                            @error('couponCodeInput')
                                <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Trust Badges --}}
                        <div class="mt-6 flex flex-col items-center gap-3">
                            <p class="text-xs text-gray-400 font-medium">Bảo mật thanh toán 100%</p>
                            <div class="flex gap-2 opacity-60 grayscale hover:grayscale-0 transition duration-500">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/5/5e/Visa_Inc._logo.svg" class="h-5" alt="Visa">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg" class="h-5" alt="Mastercard">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            {{-- EMPTY STATE (Không thay đổi nhiều, giữ nguyên design đẹp cũ) --}}
            <div class="flex flex-col items-center justify-center py-20 bg-white rounded-3xl shadow-sm border border-gray-100 text-center">
                <div class="w-40 h-40 bg-gray-50 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-20 h-20 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Giỏ hàng của bạn đang trống</h2>
                <p class="text-gray-500 max-w-md mx-auto mb-8">Có vẻ như bạn chưa thêm sản phẩm nào vào giỏ hàng. Hãy khám phá các sản phẩm tuyệt vời của chúng tôi nhé!</p>
                <a href="{{ route('home') }}" class="px-8 py-3 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-500/30 transition transform hover:-translate-y-1">
                    Mua sắm ngay
                </a>
            </div>
        @endif
    </div>
</div>
