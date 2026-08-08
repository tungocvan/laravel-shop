<div class="sticky top-6">
    <div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-100 relative overflow-hidden">

        {{-- Decorative Top Line --}}
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 to-purple-600"></div>

        <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
            Đơn hàng của bạn <span class="text-gray-400 text-sm font-normal">({{ $this->summary['items']->count() }} sản phẩm)</span>
        </h3>

        {{-- Product List --}}
        <div class="space-y-4 mb-6 max-h-[400px] overflow-y-auto pr-2 scrollbar-thin scrollbar-thumb-gray-200">
            @foreach($this->summary['items'] as $item)
                <div class="flex gap-4 py-2 group">
                    <div class="w-16 h-16 bg-gray-50 rounded-lg border border-gray-200 overflow-hidden shrink-0 relative">
                        <img src="{{ $item->product->image_url ?? 'https://placehold.co/400x400' }}" class="w-full h-full object-cover">
                        <span class="absolute top-0 right-0 bg-gray-900 text-white text-[10px] w-5 h-5 flex items-center justify-center rounded-bl-lg font-bold">
                            {{ $item->quantity }}
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-medium text-gray-800 line-clamp-2 leading-snug group-hover:text-blue-600 transition-colors">
                            {{ $item->product->title }}
                        </h4>
                        <div class="mt-1">
                            <span class="font-bold text-gray-900 text-sm">{{ number_format($item->total) }}đ</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Coupon Input --}}
        <div class="mb-6 pt-6 border-t border-gray-100 border-dashed">
            <label class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 block">Mã giảm giá</label>
            <div class="flex gap-2">
                <input type="text"
                       wire:model="couponCode"
                       wire:keydown.enter="applyCoupon"
                       placeholder="Nhập mã..."
                       class="flex-1 bg-gray-50 border border-gray-200 rounded-lg text-sm px-3 py-2.5 focus:ring-black focus:border-black transition">

                <button wire:click="applyCoupon"
                        wire:loading.attr="disabled"
                        class="px-4 py-2 bg-gray-200 text-gray-700 font-bold rounded-lg text-sm hover:bg-gray-300 transition">
                    <span wire:loading.remove wire:target="applyCoupon">Áp dụng</span>
                    <span wire:loading wire:target="applyCoupon">...</span>
                </button>
            </div>
            @error('coupon') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
        </div>

        {{-- Calculation --}}
        <div class="space-y-3 border-t border-gray-100 border-dashed pt-6">
            {{-- Tạm tính --}}
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Tạm tính</span>
                <span class="font-medium text-gray-900">{{ number_format($this->summary['subtotal']) }}đ</span>
            </div>

            {{-- Discount --}}
            @if($this->summary['discount'] > 0)
                <div class="flex justify-between items-center text-sm text-green-600 bg-green-50 p-2 rounded-lg">
                    <div class="flex flex-col">
                        <span class="font-bold flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                            {{ $this->summary['coupon_code'] }}
                        </span>
                        <button wire:click="removeCoupon" class="text-xs underline text-red-500 text-left mt-0.5 hover:text-red-700">Gỡ bỏ</button>
                    </div>
                    <span class="font-bold">-{{ number_format($this->summary['discount']) }}đ</span>
                </div>
            @endif

            {{-- Shipping --}}
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Phí vận chuyển</span>
                <span class="font-bold text-green-600">Miễn phí</span>
            </div>

            {{-- Total --}}
            <div class="border-t border-gray-100 pt-4 mt-2 flex justify-between items-end">
                <span class="font-bold text-gray-900">Tổng thanh toán</span>
                <div class="text-right">
                    <span class="block text-2xl font-black text-blue-600 tracking-tight">{{ number_format($this->summary['total']) }}đ</span>
                    <span class="text-[10px] text-gray-400 font-medium">Đã bao gồm VAT</span>
                </div>
            </div>
        </div>

        {{-- Trust Signals --}}
        <div class="mt-8 bg-gray-50 p-4 rounded-xl flex flex-col items-center gap-3">
            <div class="flex items-center gap-2 text-gray-500 text-xs font-medium">
                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                Bảo mật SSL 256-bit
            </div>
            <div class="flex gap-2 grayscale opacity-60">
                <img src="https://upload.wikimedia.org/wikipedia/commons/5/5e/Visa_Inc._logo.svg" class="h-4" alt="Visa">
                <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg" class="h-4" alt="Mastercard">
                <img src="https://upload.wikimedia.org/wikipedia/commons/b/b5/PayPal.svg" class="h-4" alt="Paypal">
            </div>
        </div>

    </div>
</div>
