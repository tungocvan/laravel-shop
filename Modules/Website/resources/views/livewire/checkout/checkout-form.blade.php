<div class="space-y-8">

    {{-- Hiển thị lỗi hệ thống (System Error) --}}
    @if($errors->has('system'))
        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg animate-pulse">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700 font-bold">{{ $errors->first('system') }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Guest Login Suggestion --}}
    @guest
        <div class="bg-blue-50/50 border border-blue-100 p-4 rounded-xl flex items-start gap-3">
            <div class="p-2 bg-blue-100 rounded-lg text-blue-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            </div>
            <div>
                <h3 class="text-sm font-bold text-gray-900">Bạn đã có tài khoản?</h3>
                <p class="text-sm text-gray-500 mt-1">
                    <a href="{{ route('login') }}?redirect=checkout" class="text-blue-600 font-bold hover:underline">Đăng nhập ngay</a>
                    để tích điểm và theo dõi đơn hàng dễ dàng hơn.
                </p>
            </div>
        </div>
    @endguest

    <form wire:submit="placeOrder" class="space-y-8">

        {{-- STEP 1: THÔNG TIN GIAO HÀNG --}}
        <section class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-gray-100">
            <div class="flex items-center gap-3 mb-6 border-b border-gray-100 pb-4">
                <span class="flex items-center justify-center w-8 h-8 rounded-full bg-black text-white font-bold text-sm">1</span>
                <h2 class="text-xl font-bold text-gray-900">Thông tin nhận hàng</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Họ tên --}}
                <div class="space-y-2">
                    <label class="text-sm font-bold text-gray-700">Họ và tên <span class="text-red-500">*</span></label>
                    <input type="text" wire:model.blur="customer_name"
                           class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-3 transition-colors @error('customer_name') border-red-500 bg-red-50 @enderror"
                           placeholder="Ví dụ: Nguyễn Văn A">
                    @error('customer_name') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                </div>

                {{-- SĐT --}}
                <div class="space-y-2">
                    <label class="text-sm font-bold text-gray-700">Số điện thoại <span class="text-red-500">*</span></label>
                    <input type="text" wire:model.blur="customer_phone"
                           class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-3 transition-colors @error('customer_phone') border-red-500 bg-red-50 @enderror"
                           placeholder="Ví dụ: 0903..." maxlength="15">
                    @error('customer_phone') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                </div>

                {{-- Email --}}
                <div class="space-y-2 md:col-span-2">
                    <label class="text-sm font-bold text-gray-700">Email (Để nhận hóa đơn)</label>
                    <input type="email" wire:model.blur="customer_email"
                           class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-3 transition-colors @error('customer_email') border-red-500 bg-red-50 @enderror"
                           placeholder="email@example.com">
                    @error('customer_email') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                </div>

                {{-- Địa chỉ --}}
                <div class="space-y-2 md:col-span-2">
                    <label class="text-sm font-bold text-gray-700">Địa chỉ chi tiết <span class="text-red-500">*</span></label>
                    <input type="text" wire:model.blur="customer_address"
                           class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-3 transition-colors @error('customer_address') border-red-500 bg-red-50 @enderror"
                           placeholder="Số nhà, tên đường, phường/xã, quận/huyện...">
                    @error('customer_address') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                </div>

                {{-- Ghi chú --}}
                <div class="space-y-2 md:col-span-2">
                    <label class="text-sm font-bold text-gray-700">Ghi chú giao hàng</label>
                    <textarea wire:model="note" rows="3"
                              class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-3 transition-colors"
                              placeholder="Ví dụ: Giao giờ hành chính, gọi trước khi giao..."></textarea>
                </div>
            </div>
        </section>

        {{-- STEP 2: PHƯƠNG THỨC THANH TOÁN --}}
        <section class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-gray-100">
            <div class="flex items-center gap-3 mb-6 border-b border-gray-100 pb-4">
                <span class="flex items-center justify-center w-8 h-8 rounded-full bg-black text-white font-bold text-sm">2</span>
                <h2 class="text-xl font-bold text-gray-900">Thanh toán</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                {{-- Card COD --}}
                <label class="relative flex items-center p-4 border rounded-xl cursor-pointer hover:bg-gray-50 transition-all duration-200 group {{ $payment_method === 'cod' ? 'border-blue-600 bg-blue-50/30 ring-1 ring-blue-600' : 'border-gray-200' }}">
                    <input wire:model.live="payment_method" value="cod" type="radio" class="sr-only">
                    <div class="flex items-center gap-4 w-full">
                        <div class="w-10 h-10 rounded-full bg-green-100 text-green-600 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                        <div>
                            <span class="block text-sm font-bold text-gray-900">COD</span>
                            <span class="block text-xs text-gray-500">Thanh toán khi nhận hàng</span>
                        </div>
                    </div>
                    @if($payment_method === 'cod')
                        <div class="absolute top-4 right-4 text-blue-600">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        </div>
                    @endif
                </label>

                {{-- Card Momo --}}
                <label class="relative flex items-center p-4 border rounded-xl cursor-pointer hover:bg-gray-50 transition-all duration-200 group {{ $payment_method === 'momo' ? 'border-pink-600 bg-pink-50/30 ring-1 ring-pink-600' : 'border-gray-200' }}">
                    <input wire:model.live="payment_method" value="momo" type="radio" class="sr-only">
                    <div class="flex items-center gap-4 w-full">
                        <div class="w-10 h-10 rounded-lg overflow-hidden shrink-0 border border-gray-100">
                            <img src="https://developers.momo.vn/v3/img/logo.svg" alt="Momo" class="w-full h-full object-cover p-1">
                        </div>
                        <div>
                            <span class="block text-sm font-bold text-gray-900">Ví MoMo</span>
                            <span class="block text-xs text-gray-500">Quét mã QR cực nhanh</span>
                        </div>
                    </div>
                    @if($payment_method === 'momo')
                        <div class="absolute top-4 right-4 text-pink-600">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        </div>
                    @endif
                </label>

            </div>
            @error('payment_method') <span class="text-red-500 text-xs mt-2 block">{{ $message }}</span> @enderror
        </section>

        {{-- CHECKOUT BUTTON --}}
        <button type="submit"
                wire:loading.attr="disabled"
                class="w-full bg-black text-white font-bold py-5 rounded-xl hover:bg-gray-800 transition-all shadow-xl hover:shadow-2xl text-lg uppercase tracking-wide relative overflow-hidden group">

            <div wire:loading.remove wire:target="placeOrder" class="flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                Xác nhận đặt hàng
            </div>

            {{-- Loading State --}}
            <div wire:loading wire:target="placeOrder" class="absolute inset-0 flex items-center justify-center bg-gray-800">
                <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </button>

        <p class="text-center text-xs text-gray-500 mt-4">
            Bằng việc đặt hàng, bạn đồng ý với <a href="#" class="underline hover:text-black">Điều khoản dịch vụ</a> và <a href="#" class="underline hover:text-black">Chính sách bảo mật</a> của chúng tôi.
        </p>
    </form>
</div>
