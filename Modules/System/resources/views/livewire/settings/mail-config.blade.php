<div class="p-6 bg-white rounded-xl shadow-sm border border-gray-200 mt-8">
    <div class="mb-6 pb-4 border-b border-gray-100">
        <h3 class="text-lg font-bold text-gray-800">Cấu hình Email (SMTP)</h3>
        <p class="text-sm text-gray-500">Thiết lập máy chủ gửi mail hệ thống. Worker chạy lâu có thể cần restart sau khi đổi cấu hình.</p>
    </div>

    @unless($canUpdate)
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Bạn đang ở chế độ chỉ xem. Cần quyền <code>system.env.update</code> để lưu cấu hình hoặc gửi email kiểm tra.
        </div>
    @endunless

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Mailer</label>
                <select wire:model="form.MAIL_MAILER" @disabled(!$canUpdate)
                    class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 outline-none disabled:bg-gray-100">
                    <option value="smtp">SMTP</option>
                </select>
                @error('form.MAIL_MAILER') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Mail Host</label>
                <input type="text" wire:model="form.MAIL_HOST" @disabled(!$canUpdate)
                    class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 outline-none disabled:bg-gray-100">
                @error('form.MAIL_HOST') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Port</label>
                <input type="number" min="1" max="65535" wire:model="form.MAIL_PORT" @disabled(!$canUpdate)
                    class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 outline-none disabled:bg-gray-100">
                @error('form.MAIL_PORT') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Username</label>
                <input type="text" wire:model="form.MAIL_USERNAME" autocomplete="off" @disabled(!$canUpdate)
                    class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 outline-none disabled:bg-gray-100">
                @error('form.MAIL_USERNAME') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Password mới</label>
                <input type="password" wire:model="form.MAIL_PASSWORD" autocomplete="new-password" @disabled(!$canUpdate)
                    placeholder="Để trống để giữ mật khẩu SMTP hiện tại"
                    class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 outline-none disabled:bg-gray-100">
                <p class="mt-1 text-xs text-gray-500">Mật khẩu hiện tại không được tải về trình duyệt.</p>
                @error('form.MAIL_PASSWORD') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Encryption</label>
                <select wire:model="form.MAIL_ENCRYPTION" @disabled(!$canUpdate)
                    class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 outline-none disabled:bg-gray-100">
                    <option value="tls">TLS</option>
                    <option value="ssl">SSL</option>
                    <option value="none">Không mã hóa</option>
                </select>
                @error('form.MAIL_ENCRYPTION') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">From Email</label>
                <input type="email" wire:model="form.MAIL_FROM_ADDRESS" placeholder="name@example.com" @disabled(!$canUpdate)
                    class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 outline-none disabled:bg-gray-100">
                @error('form.MAIL_FROM_ADDRESS') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Tên người gửi</label>
                <input type="text" wire:model="form.MAIL_FROM_NAME" placeholder="Tên website hoặc doanh nghiệp" @disabled(!$canUpdate)
                    class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 outline-none disabled:bg-gray-100">
                @error('form.MAIL_FROM_NAME') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="bg-gray-50 p-5 rounded-lg border border-dashed border-gray-300">
            <h4 class="text-sm font-bold text-gray-700 mb-3">Kiểm tra gửi mail</h4>
            <p class="text-xs text-gray-500 mb-3">Thao tác này gửi một email thật ra bên ngoài và có chống gửi lặp trong vài giây.</p>
            <div class="space-y-3">
                <input type="email" wire:model="testEmail" placeholder="Email người nhận..." @disabled(!$canUpdate)
                    class="w-full px-3 py-2 text-sm rounded border border-gray-300 outline-none disabled:bg-gray-100">
                @error('testEmail') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                <button wire:click="sendTest" wire:loading.attr="disabled" wire:target="sendTest" @disabled(!$canUpdate)
                    class="w-full py-2 bg-gray-700 text-white text-xs font-bold rounded hover:bg-black transition flex justify-center items-center disabled:opacity-50 disabled:cursor-not-allowed">
                    <span wire:loading wire:target="sendTest" class="mr-2 animate-spin">🌀</span>
                    <span wire:loading.remove wire:target="sendTest">GỬI EMAIL KIỂM TRA</span>
                    <span wire:loading wire:target="sendTest">ĐANG GỬI...</span>
                </button>
            </div>
        </div>
    </div>

    <div class="mt-8 pt-6 border-t border-gray-100">
        <button wire:click="save"
            wire:confirm="Thay đổi SMTP có thể làm hệ thống không gửi được email. Bạn chắc chắn muốn lưu cấu hình này?"
            wire:loading.attr="disabled" wire:target="save" @disabled(!$canUpdate)
            class="px-8 py-2.5 bg-primary text-white font-bold rounded-lg shadow-lg hover:bg-primary/90 transition disabled:opacity-50 disabled:cursor-not-allowed">
            <span wire:loading.remove wire:target="save">LƯU CẤU HÌNH MAIL</span>
            <span wire:loading wire:target="save">ĐANG LƯU...</span>
        </button>
    </div>
</div>
