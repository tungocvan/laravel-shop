<div class="space-y-8">
    @if($errors->has('system'))
        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
            <p class="text-sm text-red-700 font-bold">{{ $errors->first('system') }}</p>
        </div>
    @endif

    @guest
        <div class="bg-blue-50/50 border border-blue-100 p-4 rounded-xl">
            <h3 class="text-sm font-bold text-gray-900">Bạn đã có tài khoản?</h3>
            <p class="text-sm text-gray-500 mt-1">
                <a href="{{ route('login') }}?redirect=checkout" class="text-blue-600 font-bold hover:underline">Đăng nhập ngay</a>
                để theo dõi đơn hàng dễ dàng hơn.
            </p>
        </div>
    @endguest

    <form wire:submit="placeOrder" class="space-y-8">
        <section class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-gray-100">
            <div class="flex items-center gap-3 mb-6 border-b border-gray-100 pb-4">
                <span class="flex items-center justify-center w-8 h-8 rounded-full bg-black text-white font-bold text-sm">1</span>
                <h2 class="text-xl font-bold text-gray-900">Thông tin nhận hàng</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-sm font-bold text-gray-700">Họ và tên <span class="text-red-500">*</span></label>
                    <input type="text" wire:model.blur="customer_name" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-3 @error('customer_name') border-red-500 @enderror" placeholder="Ví dụ: Nguyễn Văn A">
                    @error('customer_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-bold text-gray-700">Số điện thoại <span class="text-red-500">*</span></label>
                    <input type="text" wire:model.blur="customer_phone" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-3 @error('customer_phone') border-red-500 @enderror" placeholder="Ví dụ: 0903..." maxlength="20">
                    @error('customer_phone') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="space-y-2 md:col-span-2">
                    <label class="text-sm font-bold text-gray-700">Email</label>
                    <input type="email" wire:model.blur="customer_email" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-3 @error('customer_email') border-red-500 @enderror" placeholder="email@example.com">
                    @error('customer_email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="space-y-2 md:col-span-2">
                    <label class="text-sm font-bold text-gray-700">Địa chỉ chi tiết <span class="text-red-500">*</span></label>
                    <input type="text" wire:model.blur="customer_address" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-3 @error('customer_address') border-red-500 @enderror" placeholder="Số nhà, đường, phường/xã, quận/huyện...">
                    @error('customer_address') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="space-y-2 md:col-span-2">
                    <label class="text-sm font-bold text-gray-700">Ghi chú giao hàng</label>
                    <textarea wire:model="note" rows="3" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-3" placeholder="Ví dụ: Giao giờ hành chính..."></textarea>
                    @error('note') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>
        </section>

        <section class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-gray-100">
            <div class="flex items-center gap-3 mb-6 border-b border-gray-100 pb-4">
                <span class="flex items-center justify-center w-8 h-8 rounded-full bg-black text-white font-bold text-sm">2</span>
                <h2 class="text-xl font-bold text-gray-900">Thanh toán</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <label class="relative flex items-center p-4 border rounded-xl cursor-pointer hover:bg-gray-50 {{ $payment_method === 'cod' ? 'border-blue-600 bg-blue-50/30 ring-1 ring-blue-600' : 'border-gray-200' }}">
                    <input wire:model.live="payment_method" value="cod" type="radio" class="sr-only">
                    <div>
                        <span class="block text-sm font-bold text-gray-900">Tiền mặt / COD</span>
                        <span class="block text-xs text-gray-500 mt-1">Thanh toán khi nhận hàng</span>
                    </div>
                </label>

                <label class="relative flex items-center p-4 border rounded-xl cursor-pointer hover:bg-gray-50 {{ $payment_method === 'bank_transfer' ? 'border-emerald-600 bg-emerald-50/30 ring-1 ring-emerald-600' : 'border-gray-200' }}">
                    <input wire:model.live="payment_method" value="bank_transfer" type="radio" class="sr-only">
                    <div>
                        <span class="block text-sm font-bold text-gray-900">Chuyển khoản</span>
                        <span class="block text-xs text-gray-500 mt-1">Nhận thông tin tài khoản sau khi đặt hàng</span>
                    </div>
                </label>

                <label class="relative flex items-center p-4 border rounded-xl cursor-pointer hover:bg-gray-50 {{ $payment_method === 'momo' ? 'border-pink-600 bg-pink-50/30 ring-1 ring-pink-600' : 'border-gray-200' }}">
                    <input wire:model.live="payment_method" value="momo" type="radio" class="sr-only">
                    <div class="flex items-center gap-3">
                        <img src="https://developers.momo.vn/v3/img/logo.svg" alt="MoMo" class="w-9 h-9 object-contain">
                        <div>
                            <span class="block text-sm font-bold text-gray-900">Ví MoMo</span>
                            <span class="block text-xs text-gray-500 mt-1">Thanh toán qua cổng MoMo</span>
                        </div>
                    </div>
                </label>
            </div>

            @error('payment_method') <span class="text-red-500 text-xs mt-2 block">{{ $message }}</span> @enderror
        </section>

        <button type="submit" wire:loading.attr="disabled" wire:target="placeOrder"
                class="w-full bg-black text-white font-bold py-5 rounded-xl hover:bg-gray-800 disabled:opacity-60 disabled:cursor-not-allowed transition-all shadow-xl text-lg uppercase tracking-wide">
            <span wire:loading.remove wire:target="placeOrder">Xác nhận đặt hàng</span>
            <span wire:loading wire:target="placeOrder">Đang xử lý...</span>
        </button>

        <p class="text-center text-xs text-gray-500 mt-4">
            Bằng việc đặt hàng, bạn đồng ý với Điều khoản dịch vụ và Chính sách bảo mật của chúng tôi.
        </p>
    </form>
</div>
